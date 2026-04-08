<?php

namespace App\Controllers;

use App\Models\CategoriaMovimentModel;
use App\Models\CobramentFacturaModel;
use App\Models\FacturaModel;
use App\Models\MovimentModel;
use CodeIgniter\HTTP\ResponseInterface;

class CobramentController extends BaseController
{
    protected FacturaModel $facturaModel;
    protected CobramentFacturaModel $cobramentModel;
    protected MovimentModel $movimentModel;
    protected CategoriaMovimentModel $categoriaModel;

    public function __construct()
    {
        $this->facturaModel = new FacturaModel();
        $this->cobramentModel = new CobramentFacturaModel();
        $this->movimentModel = new MovimentModel();
        $this->categoriaModel = new CategoriaMovimentModel();
    }

    public function index(int $facturaId): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $factura = $this->facturaModel
                ->where('id', $facturaId)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $cobraments = $this->cobramentModel->obtenirCobramentsFactura($facturaId);
            $totalCobrat = $this->cobramentModel->totalCobrat($facturaId);
            $totalFactura = round((float) ($factura['total'] ?? 0), 2);
            $pendent = round(max($totalFactura - $totalCobrat, 0), 2);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $cobraments,
                'meta' => [
                    'total_cobrat' => $totalCobrat,
                    'total_factura' => $totalFactura,
                    'pendent' => $pendent,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures cobramentsIndex: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar els cobraments de la factura',
            ]);
        }
    }

    public function create(int $facturaId): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $factura = $this->facturaModel
                ->where('id', $facturaId)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            if (!in_array((string) ($factura['estat'] ?? ''), ['emesa', 'parcialment_cobrada'], true)) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'Només es poden registrar cobraments per factures emeses o parcialment cobrades.',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $payload = $this->filtrarPayloadCobrament($data);
            $payload['factura_id'] = $facturaId;
            $payload['moviment_id'] = null;

            $totalFactura = round((float) ($factura['total'] ?? 0), 2);
            $totalCobratActual = $this->cobramentModel->totalCobrat($facturaId);
            $pendent = round($totalFactura - $totalCobratActual, 2);
            $importCobrament = round((float) ($payload['import'] ?? 0), 2);

            if ($pendent <= 0) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'La factura ja està cobrada i no admet més cobraments.',
                ]);
            }

            if ($importCobrament > $pendent + 0.00001) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'L\'import del cobrament excedeix l\'import pendent de la factura.',
                ]);
            }

            $categoriaIngres = $this->obtenirCategoriaIngresPerUsuari($usuariId);
            if (!$categoriaIngres) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'No hi ha cap categoria d\'ingrés disponible per registrar el cobrament.',
                ]);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->cobramentModel->insert($payload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades de cobrament no vàlides',
                    'errors' => $this->cobramentModel->errors(),
                ]);
            }

            $cobramentId = (int) $this->cobramentModel->getInsertID();
            $movimentPayload = [
                'usuari_id' => $usuariId,
                'categoria_id' => (int) $categoriaIngres['id'],
                'factura_id' => $facturaId,
                'tipus' => 'ingres',
                'descripcio' => 'Cobrament factura ' . (string) ($factura['numero_factura'] ?? $facturaId),
                'import' => $importCobrament,
                'data' => (string) ($payload['data_cobrament'] ?? date('Y-m-d')),
            ];

            if (!$this->movimentModel->insert($movimentPayload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut crear el moviment d\'ingrés del cobrament',
                    'errors' => $this->movimentModel->errors(),
                ]);
            }

            $movimentId = (int) $this->movimentModel->getInsertID();

            if (!$this->cobramentModel->update($cobramentId, ['moviment_id' => $movimentId])) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut vincular el moviment al cobrament',
                ]);
            }

            $totalCobratNou = $this->cobramentModel->totalCobrat($facturaId);
            if (!$this->recalcularEstatFacturaPerCobraments($factura, $totalCobratNou)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut actualitzar l\'estat de la factura',
                ]);
            }

            $db->transCommit();

            $cobramentCreat = $this->cobramentModel->find($cobramentId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Cobrament registrat correctament',
                'data' => $cobramentCreat,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures crearCobrament: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al registrar el cobrament de la factura',
            ]);
        }
    }

    public function delete(int $facturaId, int $cobramentId): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $factura = $this->facturaModel
                ->where('id', $facturaId)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $cobrament = $this->cobramentModel
                ->where('id', $cobramentId)
                ->where('factura_id', $facturaId)
                ->first();

            if (!$cobrament) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Cobrament no trobat',
                ]);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->cobramentModel->delete($cobramentId)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut eliminar el cobrament',
                ]);
            }

            $movimentId = (int) ($cobrament['moviment_id'] ?? 0);
            if ($movimentId > 0) {
                $moviment = $this->movimentModel
                    ->where('id', $movimentId)
                    ->where('usuari_id', $usuariId)
                    ->first();

                if ($moviment && !$this->movimentModel->delete($movimentId)) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'No s\'ha pogut eliminar el moviment associat al cobrament',
                    ]);
                }
            }

            $totalCobratNou = $this->cobramentModel->totalCobrat($facturaId);
            if (!$this->recalcularEstatFacturaPerCobraments($factura, $totalCobratNou)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut recalcular l\'estat de la factura',
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Cobrament eliminat correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures eliminarCobrament: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al eliminar el cobrament de la factura',
            ]);
        }
    }

    private function filtrarPayloadCobrament(array $data): array
    {
        $campsPermesos = [
            'import',
            'data_cobrament',
            'metode_pagament',
            'notes',
        ];

        $payload = [];
        foreach ($campsPermesos as $camp) {
            if (!array_key_exists($camp, $data)) {
                continue;
            }

            if ($camp === 'import') {
                $payload[$camp] = (float) $data[$camp];
                continue;
            }

            if (in_array($camp, ['metode_pagament', 'notes'], true)) {
                $valor = is_string($data[$camp]) ? trim($data[$camp]) : $data[$camp];
                $payload[$camp] = $valor === '' ? null : $valor;
                continue;
            }

            $payload[$camp] = is_string($data[$camp]) ? trim($data[$camp]) : $data[$camp];
        }

        return $payload;
    }

    private function obtenirCategoriaIngresPerUsuari(int $usuariId): ?array
    {
        $categoriaVendes = $this->categoriaModel
            ->where('usuari_id', $usuariId)
            ->where('tipus', 'ingres')
            ->where('nom', 'Vendes')
            ->first();

        if ($categoriaVendes) {
            return $categoriaVendes;
        }

        return $this->categoriaModel
            ->where('usuari_id', $usuariId)
            ->where('tipus', 'ingres')
            ->orderBy('id', 'ASC')
            ->first();
    }

    private function recalcularEstatFacturaPerCobraments(array $factura, float $totalCobrat): bool
    {
        $facturaId = (int) ($factura['id'] ?? 0);
        $totalFactura = round((float) ($factura['total'] ?? 0), 2);

        $nouEstat = 'emesa';
        $dataCobrament = null;

        if ($totalCobrat + 0.00001 >= $totalFactura && $totalFactura > 0) {
            $nouEstat = 'cobrada';
            $dataCobrament = date('Y-m-d');
        } elseif ($totalCobrat > 0) {
            $nouEstat = 'parcialment_cobrada';
        }

        return $this->facturaModel->update($facturaId, [
            'estat' => $nouEstat,
            'data_cobrament' => $dataCobrament,
        ]);
    }
}
