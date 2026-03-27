<?php

namespace App\Controllers;

use App\Libraries\PdfFactura;
use App\Models\ClientModel;
use App\Models\FacturaModel;
use App\Models\LiniaFacturaModel;
use App\Models\UsuariModel;
use CodeIgniter\HTTP\ResponseInterface;

class FacturaController extends BaseController
{
    protected FacturaModel $facturaModel;
    protected LiniaFacturaModel $liniaModel;
    protected ClientModel $clientModel;
    protected UsuariModel $usuariModel;
    protected PdfFactura $pdfFactura;

    public function __construct()
    {
        $this->facturaModel = new FacturaModel();
        $this->liniaModel = new LiniaFacturaModel();
        $this->clientModel = new ClientModel();
        $this->usuariModel = new UsuariModel();
        $this->pdfFactura = new PdfFactura();
    }

    public function index(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $limit = max(1, min((int) ($this->request->getGet('limit') ?? 10), 100));

            $search = trim((string) ($this->request->getGet('search') ?? ''));
            $estat = trim((string) ($this->request->getGet('estat') ?? ''));
            $clientId = (int) ($this->request->getGet('client_id') ?? 0);
            $dataDesde = trim((string) ($this->request->getGet('data_desde') ?? ''));
            $dataFins = trim((string) ($this->request->getGet('data_fins') ?? ''));

            $builder = $this->facturaModel->builder();
            $builder->where('usuari_id', $usuariId)
                ->where('deleted_at', null);

            if ($search !== '') {
                $builder->groupStart()
                    ->like('numero_factura', $search)
                    ->orLike('notes', $search)
                    ->groupEnd();
            }

            if ($estat !== '') {
                $builder->where('estat', $estat);
            }

            if ($clientId > 0) {
                $builder->where('client_id', $clientId);
            }

            if ($dataDesde !== '' && strtotime($dataDesde) !== false) {
                $builder->where('data_emisio >=', $dataDesde);
            }

            if ($dataFins !== '' && strtotime($dataFins) !== false) {
                $builder->where('data_emisio <=', $dataFins);
            }

            $total = (int) $builder->countAllResults(false);

            $factures = $builder
                ->orderBy('data_emisio', 'DESC')
                ->limit($limit, ($page - 1) * $limit)
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $factures,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                    'search' => $search,
                    'estat' => $estat,
                    'client_id' => $clientId,
                    'data_desde' => $dataDesde,
                    'data_fins' => $dataFins,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures index: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al llistar les factures',
            ]);
        }
    }

    public function show(int $id): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $linies = $this->liniaModel->obtenirLiniesFactura($id);

            $client = $this->clientModel
                ->where('id', (int) $factura['client_id'])
                ->where('usuari_id', $usuariId)
                ->first();

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => [
                    'factura' => $factura,
                    'linies' => $linies,
                    'client' => $client,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures show: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar la factura',
            ]);
        }
    }

    public function pdf(int $id): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $linies = $this->liniaModel->obtenirLiniesFactura($id);
            $client = $this->clientModel
                ->where('id', (int) $factura['client_id'])
                ->where('usuari_id', $usuariId)
                ->first();

            $usuari = $this->usuariModel->find($usuariId);
            $pdfContent = $this->pdfFactura->generar($factura, $linies, $client, $usuari);
            $nomArxiu = 'factura-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', (string) ($factura['numero_factura'] ?? $id)) . '.pdf';

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $nomArxiu . '"')
                ->setBody($pdfContent);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures pdf: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en generar el PDF de la factura',
            ]);
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $linies = $data['linies'] ?? [];
            if (!is_array($linies) || $linies === []) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La factura ha de tenir almenys una línia',
                ]);
            }

            $payload = $this->filtrarPayloadFactura($data);
            $payload['usuari_id'] = $usuariId;

            if (!$this->clientEsDelUsuari((int) ($payload['client_id'] ?? 0), $usuariId)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Client no vàlid per a aquest usuari',
                ]);
            }

            $payload['serie'] = trim((string) ($payload['serie'] ?? 'F')) ?: 'F';
            $payload['numero_factura'] = $this->facturaModel->generarNumeroFactura($usuariId, $payload['serie']);
            $payload['estat'] = $payload['estat'] ?? 'esborrany';
            $payload['iva_percentatge'] = (float) ($payload['iva_percentatge'] ?? 21);
            $payload['irpf_percentatge'] = (float) ($payload['irpf_percentatge'] ?? 0);
            $payload['subtotal'] = 0;
            $payload['iva_import'] = 0;
            $payload['irpf_import'] = 0;
            $payload['total'] = 0;

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->facturaModel->insert($payload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->facturaModel->errors(),
                ]);
            }

            $facturaId = (int) $this->facturaModel->getInsertID();

            foreach (array_values($linies) as $index => $linia) {
                if (!is_array($linia)) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'Format de línies no vàlid',
                    ]);
                }

                $payloadLinia = $this->filtrarPayloadLinia($linia);
                $payloadLinia['factura_id'] = $facturaId;
                $payloadLinia['ordre'] = $index;
                $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $payload['iva_percentatge']);

                if (!$this->liniaModel->crearLinia($payloadLinia)) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'Dades de línia no vàlides',
                        'errors' => $this->liniaModel->errors(),
                    ]);
                }
            }

            if (!$this->facturaModel->actualitzarTotals($facturaId)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'han pogut actualitzar els totals',
                ]);
            }

            $db->transCommit();

            $facturaCreada = $this->facturaModel->find($facturaId);
            $liniesCreades = $this->liniaModel->obtenirLiniesFactura($facturaId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Factura creada correctament',
                'data' => [
                    'factura' => $facturaCreada,
                    'linies' => $liniesCreades,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures create: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al crear la factura',
            ]);
        }
    }

    public function update(int $id): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $payload = $this->filtrarPayloadFactura($data);
            unset($payload['usuari_id'], $payload['numero_factura'], $payload['serie'], $payload['client_id']);

            if ($payload === [] && !array_key_exists('linies', $data)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No hi ha camps vàlids per actualitzar',
                ]);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            if ($payload !== [] && !$this->facturaModel->update($id, $payload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->facturaModel->errors(),
                ]);
            }

            if (array_key_exists('linies', $data)) {
                $linies = $data['linies'];
                $ivaFactura = (float) ($payload['iva_percentatge'] ?? $factura['iva_percentatge'] ?? 21);

                if (!is_array($linies) || $linies === []) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'La factura ha de tenir almenys una línia',
                    ]);
                }

                $this->liniaModel->eliminarLiniesFactura($id);

                foreach (array_values($linies) as $index => $linia) {
                    if (!is_array($linia)) {
                        $db->transRollback();

                        return $this->response->setStatusCode(422)->setJSON([
                            'status' => 'error',
                            'message' => 'Format de línies no vàlid',
                        ]);
                    }

                    $payloadLinia = $this->filtrarPayloadLinia($linia);
                    $payloadLinia['factura_id'] = $id;
                    $payloadLinia['ordre'] = $index;
                    $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $ivaFactura);

                    if (!$this->liniaModel->crearLinia($payloadLinia)) {
                        $db->transRollback();

                        return $this->response->setStatusCode(422)->setJSON([
                            'status' => 'error',
                            'message' => 'Dades de línia no vàlides',
                            'errors' => $this->liniaModel->errors(),
                        ]);
                    }
                }
            }

            if (!$this->facturaModel->actualitzarTotals($id)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'han pogut actualitzar els totals',
                ]);
            }

            $db->transCommit();

            $facturaActualitzada = $this->facturaModel->find($id);
            $liniesActualitzades = $this->liniaModel->obtenirLiniesFactura($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Factura actualitzada correctament',
                'data' => [
                    'factura' => $facturaActualitzada,
                    'linies' => $liniesActualitzades,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures update: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en actualitzar la factura',
            ]);
        }
    }

    public function delete(int $id): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            if (in_array($factura['estat'], ['emesa', 'cobrada'], true)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'message' => 'No es pot eliminar una factura emesa o cobrada',
                ]);
            }

            if (!$this->facturaModel->delete($id)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut eliminar la factura',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Factura eliminada correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures delete: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en eliminar la factura',
            ]);
        }
    }

    public function updateLinia(int $id, int $liniaId): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $linia = $this->liniaModel->find($liniaId);
            if (!$linia || (int) $linia['factura_id'] !== $id) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Línia no trobada',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $payloadLinia = $this->filtrarPayloadLinia($data);
            unset($payloadLinia['factura_id'], $payloadLinia['ordre']);

            if ($payloadLinia === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No hi ha camps de línia per actualitzar',
                ]);
            }

            if (!$this->liniaModel->actualitzarLinia($liniaId, $payloadLinia)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades de línia no vàlides',
                    'errors' => $this->liniaModel->errors(),
                ]);
            }

            $this->facturaModel->actualitzarTotals($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Línia actualitzada correctament',
                'data' => [
                    'linia' => $this->liniaModel->find($liniaId),
                    'factura' => $this->facturaModel->find($id),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures updateLinia: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en actualitzar la línia',
            ]);
        }
    }

    public function deleteLinia(int $id, int $liniaId): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $linia = $this->liniaModel->find($liniaId);
            if (!$linia || (int) $linia['factura_id'] !== $id) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Línia no trobada',
                ]);
            }

            if (!$this->liniaModel->delete($liniaId)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut eliminar la línia',
                ]);
            }

            $this->facturaModel->actualitzarTotals($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Línia eliminada correctament',
                'data' => [
                    'factura' => $this->facturaModel->find($id),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures deleteLinia: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en eliminar la línia',
            ]);
        }
    }

    public function canviarEstat(int $id): ResponseInterface
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
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Factura no trobada',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            $nouEstat = trim((string) ($data['estat'] ?? ''));

            if ($nouEstat === '') {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'L\'estat és obligatori',
                ]);
            }

            if (!$this->facturaModel->canviarEstat($id, $nouEstat)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Estat no vàlid',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Estat actualitzat correctament',
                'data' => $this->facturaModel->find($id),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures canviarEstat: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en canviar l\'estat',
            ]);
        }
    }

    private function filtrarPayloadFactura(array $data): array
    {
        $campsPermesos = [
            'client_id',
            'serie',
            'numero_factura',
            'data_emisio',
            'data_venciment',
            'estat',
            'subtotal',
            'iva_percentatge',
            'iva_import',
            'irpf_percentatge',
            'irpf_import',
            'total',
            'metode_pagament',
            'data_cobrament',
            'notes',
        ];

        $payload = [];
        foreach ($campsPermesos as $camp) {
            if (array_key_exists($camp, $data)) {
                $payload[$camp] = $data[$camp];
            }
        }

        return $payload;
    }

    private function filtrarPayloadLinia(array $data): array
    {
        $campsPermesos = [
            'descripcio',
            'quantitat',
            'preu_unitari',
            'iva_percentatge',
            'descompte',
            'ordre',
        ];

        $payload = [];
        foreach ($campsPermesos as $camp) {
            if (array_key_exists($camp, $data)) {
                $payload[$camp] = $data[$camp];
            }
        }

        return $payload;
    }

    private function clientEsDelUsuari(int $clientId, int $usuariId): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        $client = $this->clientModel
            ->where('id', $clientId)
            ->where('usuari_id', $usuariId)
            ->first();

        return $client !== null;
    }
}
