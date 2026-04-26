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
    /**
     * Inicialitza els models i serveis: FacturaModel, CobramentFacturaModel, MovimentModel i CategoriaMovimentModel.
     */
    public function __construct()
    {
        $this->facturaModel = new FacturaModel();
        $this->cobramentModel = new CobramentFacturaModel();
        $this->movimentModel = new MovimentModel();
        $this->categoriaModel = new CategoriaMovimentModel();
    }
    /**
     * Llista cobrament disponibles per a l'usuari autenticat.
     *
     * @param int $facturaId Identificador de la factura.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function index(int $facturaId): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $facturaId)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $cobraments = $this->cobramentModel->obtenirCobramentsFactura($facturaId);
            $totalCobrat = $this->cobramentModel->totalCobrat($facturaId);
            $totalFactura = round((float) ($factura['total'] ?? 0), 2);
            $pendent = round(max($totalFactura - $totalCobrat, 0), 2);

            return $this->jsonOk([
                'data' => $cobraments,
                'meta' => [
                    'total_cobrat' => $totalCobrat,
                    'total_factura' => $totalFactura,
                    'pendent' => $pendent,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures cobramentsIndex: ' . $e->getMessage());

            return $this->jsonError('Error al carregar els cobraments de la factura', 500);
        }
    }
    /**
     * Crea un nou cobrament amb les dades rebudes.
     *
     * @param int $facturaId Identificador de la factura.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function create(int $facturaId): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $facturaId)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            if (!in_array((string) ($factura['estat'] ?? ''), ['emesa', 'parcialment_cobrada'], true)) {
                return $this->jsonError('Només es poden registrar cobraments per factures emeses o parcialment cobrades.', 409);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payload = $this->filtrarPayloadCobrament($data);
            $payload['factura_id'] = $facturaId;
            $payload['moviment_id'] = null;

            $totalFactura = round((float) ($factura['total'] ?? 0), 2);
            $totalCobratActual = $this->cobramentModel->totalCobrat($facturaId);
            $pendent = round($totalFactura - $totalCobratActual, 2);
            $importCobrament = round((float) ($payload['import'] ?? 0), 2);

            if ($pendent <= 0) {
                return $this->jsonError('La factura ja està cobrada i no admet més cobraments.', 409);
            }

            if ($importCobrament > $pendent + 0.00001) {
                return $this->jsonError('L\'import del cobrament excedeix l\'import pendent de la factura.', 409);
            }

            $categoriaIngres = $this->obtenirCategoriaIngresPerUsuari($usuariId);
            if (!$categoriaIngres) {
                return $this->jsonError('No hi ha cap categoria d\'ingrés disponible per registrar el cobrament.', 409);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->cobramentModel->insert($payload)) {
                $db->transRollback();

                return $this->jsonError('Dades de cobrament no vàlides', 422, ['errors' => $this->cobramentModel->errors()]);
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

                return $this->jsonError('No s\'ha pogut crear el moviment d\'ingrés del cobrament', 422, ['errors' => $this->movimentModel->errors()]);
            }

            $movimentId = (int) $this->movimentModel->getInsertID();

            if (!$this->cobramentModel->update($cobramentId, ['moviment_id' => $movimentId])) {
                $db->transRollback();

                return $this->jsonError('No s\'ha pogut vincular el moviment al cobrament', 422);
            }

            $totalCobratNou = $this->cobramentModel->totalCobrat($facturaId);
            if (!$this->recalcularEstatFacturaPerCobraments($factura, $totalCobratNou)) {
                $db->transRollback();

                return $this->jsonError('No s\'ha pogut actualitzar l\'estat de la factura', 422);
            }

            $db->transCommit();

            $cobramentCreat = $this->cobramentModel->find($cobramentId);

            return $this->jsonOk([
                'message' => 'Cobrament registrat correctament',
                'data' => $cobramentCreat,
            ], 201);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures crearCobrament: ' . $e->getMessage());

            return $this->jsonError('Error al registrar el cobrament de la factura', 500);
        }
    }
    /**
     * Elimina un cobrament (soft delete).
     *
     * @param int $facturaId Identificador de la factura.
     * @param int $cobramentId Identificador del cobrament.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function delete(int $facturaId, int $cobramentId): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $facturaId)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $cobrament = $this->cobramentModel
                ->where('id', $cobramentId)
                ->where('factura_id', $facturaId)
                ->first();

            if (!$cobrament) {
                return $this->jsonError('Cobrament no trobat', 404);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->cobramentModel->delete($cobramentId)) {
                $db->transRollback();

                return $this->jsonError('No s\'ha pogut eliminar el cobrament', 422);
            }

            $movimentId = (int) ($cobrament['moviment_id'] ?? 0);
            if ($movimentId > 0) {
                $moviment = $this->movimentModel
                    ->where('id', $movimentId)
                    ->where('usuari_id', $usuariId)
                    ->first();

                if ($moviment && !$this->movimentModel->delete($movimentId)) {
                    $db->transRollback();

                    return $this->jsonError('No s\'ha pogut eliminar el moviment associat al cobrament', 422);
                }
            }

            $totalCobratNou = $this->cobramentModel->totalCobrat($facturaId);
            if (!$this->recalcularEstatFacturaPerCobraments($factura, $totalCobratNou)) {
                $db->transRollback();

                return $this->jsonError('No s\'ha pogut recalcular l\'estat de la factura', 422);
            }

            $db->transCommit();

            return $this->jsonOk(['message' => 'Cobrament eliminat correctament']);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures eliminarCobrament: ' . $e->getMessage());

            return $this->jsonError('Error al eliminar el cobrament de la factura', 500);
        }
    }
    /**
     * Filtra i normalitza payload cobrament.
     *
     * @param array $data Dades d'entrada del procés.
     * @return array Conjunt de dades retornat pel mètode.
     */
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
    /**
     * Obté categoria ingres per usuari segons els filtres indicats.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @return ?array Registre trobat o null si no existeix.
     */
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
    /**
     * Recalcula estat factura per cobraments.
     *
     * @param array $factura Valor d'entrada del mètode.
     * @param float $totalCobrat Valor d'entrada del mètode.
     * @return bool Indica si l'operació s'ha completat correctament.
     */
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
