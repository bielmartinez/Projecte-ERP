<?php

namespace App\Controllers;

use App\Libraries\PdfFactura;
use App\Libraries\VerifactuService;
use App\Models\ClientModel;
use App\Models\FacturaModel;
use App\Models\LiniaFacturaModel;
use App\Models\RegistreVerifactuModel;
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
            $usuariId = $this->usuariId();

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

            return $this->jsonOk([
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

            return $this->jsonError('Error al llistar les factures', 500);
        }
    }

    public function show(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $linies = $this->liniaModel->obtenirLiniesFactura($id);

            $client = $this->clientModel
                ->where('id', (int) $factura['client_id'])
                ->where('usuari_id', $usuariId)
                ->first();

            return $this->jsonOk([
                'data' => [
                    'factura' => $factura,
                    'linies' => $linies,
                    'client' => $client,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures show: ' . $e->getMessage());

            return $this->jsonError('Error al carregar la factura', 500);
        }
    }

    public function pdf(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $linies = $this->liniaModel->obtenirLiniesFactura($id);
            $client = $this->clientModel
                ->where('id', (int) $factura['client_id'])
                ->where('usuari_id', $usuariId)
                ->first();

            $usuari = $this->usuariModel->find($usuariId);

            // Buscar la URL del QR Verifactu (si existeix registre d'alta per aquesta factura)
            $urlQR = null;
            $registreVerifactuModel = new RegistreVerifactuModel();
            $registreVerifactu = $registreVerifactuModel
                ->where('factura_id', $id)
                ->where('usuari_id', $usuariId)
                ->where('tipus_registre', 'alta')
                ->orderBy('id', 'DESC')
                ->first();

            if ($registreVerifactu && !empty($registreVerifactu['codi_qr'])) {
                $urlQR = (string) $registreVerifactu['codi_qr'];
            }

            $pdfContent = $this->pdfFactura->generar($factura, $linies, $client, $usuari, $urlQR);
            $nomArxiu = 'factura-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', (string) ($factura['numero_factura'] ?? $id)) . '.pdf';

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $nomArxiu . '"')
                ->setBody($pdfContent);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures pdf: ' . $e->getMessage());

            return $this->jsonError('Error en generar el PDF de la factura', 500);
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $linies = $data['linies'] ?? [];
            if (!is_array($linies) || $linies === []) {
                return $this->jsonError('La factura ha de tenir almenys una línia', 422);
            }

            $payload = $this->filtrarPayloadFactura($data);
            $payload['usuari_id'] = $usuariId;

            if (!$this->clientEsDelUsuari((int) ($payload['client_id'] ?? 0), $usuariId)) {
                return $this->jsonError('Client no vàlid per a aquest usuari', 422);
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

                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->facturaModel->errors()]);
            }

            $facturaId = (int) $this->facturaModel->getInsertID();

            foreach (array_values($linies) as $index => $linia) {
                if (!is_array($linia)) {
                    $db->transRollback();

                    return $this->jsonError('Format de línies no vàlid', 422);
                }

                $payloadLinia = $this->filtrarPayloadLinia($linia);
                $payloadLinia['factura_id'] = $facturaId;
                $payloadLinia['ordre'] = $index;
                $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $payload['iva_percentatge']);

                if (!$this->liniaModel->crearLinia($payloadLinia)) {
                    $db->transRollback();

                    return $this->jsonError('Dades de línia no vàlides', 422, ['errors' => $this->liniaModel->errors()]);
                }
            }

            if (!$this->facturaModel->actualitzarTotals($facturaId)) {
                $db->transRollback();

                return $this->jsonError('No s\'han pogut actualitzar els totals', 422);
            }

            $db->transCommit();

            $facturaCreada = $this->facturaModel->find($facturaId);
            $liniesCreades = $this->liniaModel->obtenirLiniesFactura($facturaId);

            return $this->jsonOk([
                'message' => 'Factura creada correctament',
                'data' => [
                    'factura' => $facturaCreada,
                    'linies' => $liniesCreades,
                ],
            ], 201);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures create: ' . $e->getMessage());

            return $this->jsonError('Error al crear la factura', 500);
        }
    }

    public function update(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $estatsNoEditables = ['emesa', 'cobrada', 'parcialment_cobrada', 'cancel·lada'];
            if (in_array($factura['estat'], $estatsNoEditables, true)) {
                return $this->jsonError('No es pot editar una factura amb estat \'' . $factura['estat'] . '\'. Només es poden editar esborranys.', 403);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payload = $this->filtrarPayloadFactura($data);
            unset($payload['usuari_id'], $payload['numero_factura'], $payload['serie'], $payload['client_id']);

            if ($payload === [] && !array_key_exists('linies', $data)) {
                return $this->jsonError('No hi ha camps vàlids per actualitzar', 400);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            if ($payload !== [] && !$this->facturaModel->update($id, $payload)) {
                $db->transRollback();

                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->facturaModel->errors()]);
            }

            if (array_key_exists('linies', $data)) {
                $linies = $data['linies'];
                $ivaFactura = (float) ($payload['iva_percentatge'] ?? $factura['iva_percentatge'] ?? 21);

                if (!is_array($linies) || $linies === []) {
                    $db->transRollback();

                    return $this->jsonError('La factura ha de tenir almenys una línia', 422);
                }

                $this->liniaModel->eliminarLiniesFactura($id);

                foreach (array_values($linies) as $index => $linia) {
                    if (!is_array($linia)) {
                        $db->transRollback();

                        return $this->jsonError('Format de línies no vàlid', 422);
                    }

                    $payloadLinia = $this->filtrarPayloadLinia($linia);
                    $payloadLinia['factura_id'] = $id;
                    $payloadLinia['ordre'] = $index;
                    $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $ivaFactura);

                    if (!$this->liniaModel->crearLinia($payloadLinia)) {
                        $db->transRollback();

                        return $this->jsonError('Dades de línia no vàlides', 422, ['errors' => $this->liniaModel->errors()]);
                    }
                }
            }

            if (!$this->facturaModel->actualitzarTotals($id)) {
                $db->transRollback();

                return $this->jsonError('No s\'han pogut actualitzar els totals', 422);
            }

            $db->transCommit();

            $facturaActualitzada = $this->facturaModel->find($id);
            $liniesActualitzades = $this->liniaModel->obtenirLiniesFactura($id);

            return $this->jsonOk([
                'message' => 'Factura actualitzada correctament',
                'data' => [
                    'factura' => $facturaActualitzada,
                    'linies' => $liniesActualitzades,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures update: ' . $e->getMessage());

            return $this->jsonError('Error en actualitzar la factura', 500);
        }
    }

    public function delete(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            if ($factura['estat'] !== 'esborrany') {
                return $this->jsonError('Només es poden eliminar factures en estat esborrany.', 403);
            }

            if (!$this->facturaModel->delete($id)) {
                return $this->jsonError('No s\'ha pogut eliminar la factura', 422);
            }

            return $this->jsonOk(['message' => 'Factura eliminada correctament']);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures delete: ' . $e->getMessage());

            return $this->jsonError('Error en eliminar la factura', 500);
        }
    }

    public function updateLinia(int $id, int $liniaId): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $linia = $this->liniaModel->find($liniaId);
            if (!$linia || (int) $linia['factura_id'] !== $id) {
                return $this->jsonError('Línia no trobada', 404);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payloadLinia = $this->filtrarPayloadLinia($data);
            unset($payloadLinia['factura_id'], $payloadLinia['ordre']);

            if ($payloadLinia === []) {
                return $this->jsonError('No hi ha camps de línia per actualitzar', 400);
            }

            if (!$this->liniaModel->actualitzarLinia($liniaId, $payloadLinia)) {
                return $this->jsonError('Dades de línia no vàlides', 422, ['errors' => $this->liniaModel->errors()]);
            }

            $this->facturaModel->actualitzarTotals($id);

            return $this->jsonOk([
                'message' => 'Línia actualitzada correctament',
                'data' => [
                    'linia' => $this->liniaModel->find($liniaId),
                    'factura' => $this->facturaModel->find($id),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures updateLinia: ' . $e->getMessage());

            return $this->jsonError('Error en actualitzar la línia', 500);
        }
    }

    public function deleteLinia(int $id, int $liniaId): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $linia = $this->liniaModel->find($liniaId);
            if (!$linia || (int) $linia['factura_id'] !== $id) {
                return $this->jsonError('Línia no trobada', 404);
            }

            if (!$this->liniaModel->delete($liniaId)) {
                return $this->jsonError('No s\'ha pogut eliminar la línia', 422);
            }

            $this->facturaModel->actualitzarTotals($id);

            return $this->jsonOk([
                'message' => 'Línia eliminada correctament',
                'data' => [
                    'factura' => $this->facturaModel->find($id),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures deleteLinia: ' . $e->getMessage());

            return $this->jsonError('Error en eliminar la línia', 500);
        }
    }

    public function canviarEstat(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $factura = $this->facturaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$factura) {
                return $this->jsonError('Factura no trobada', 404);
            }

            $data = $this->request->getJSON(true) ?? [];
            $nouEstat = trim((string) ($data['estat'] ?? ''));

            if ($nouEstat === '') {
                return $this->jsonError('L\'estat és obligatori', 400);
            }

            $transicionsPermeses = [
                'esborrany' => ['emesa'],
                'emesa' => ['cancel·lada'],
                'parcialment_cobrada' => ['cancel·lada'],
            ];

            $estatActual = (string) ($factura['estat'] ?? '');

            if (!isset($transicionsPermeses[$estatActual]) || !in_array($nouEstat, $transicionsPermeses[$estatActual], true)) {
                return $this->jsonError('No es pot canviar l\'estat de \'' . $estatActual . '\' a \'' . $nouEstat . '\'.', 422);
            }

            if (!$this->facturaModel->canviarEstat($id, $nouEstat)) {
                return $this->jsonError('Estat no vàlid', 422);
            }

            // --- Inici Verifactu ---
            if (in_array($nouEstat, ['emesa', 'cancel·lada'], true)) {
                try {
                    $usuari = $this->usuariModel->find($usuariId);
                    $facturaActualitzada = $this->facturaModel->find($id);
                    $verifactuService = new VerifactuService();

                    if ($nouEstat === 'emesa') {
                        $verifactuService->generarRegistreAlta($facturaActualitzada, $usuari);
                    } elseif ($nouEstat === 'cancel·lada') {
                        $verifactuService->generarRegistreAnulacio($facturaActualitzada, $usuari);
                    }
                } catch (\RuntimeException $e) {
                    // Si falla Verifactu per NIF no informat, informem però NO revertim el canvi d'estat
                    log_message('warning', 'Verifactu no generat: ' . $e->getMessage());
                } catch (\Exception $e) {
                    log_message('error', 'Error Verifactu en canviarEstat: ' . $e->getMessage());
                }
            }
            // --- Fi Verifactu ---

            return $this->jsonOk([
                'message' => 'Estat actualitzat correctament',
                'data' => $this->facturaModel->find($id),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en factures canviarEstat: ' . $e->getMessage());

            return $this->jsonError('Error en canviar l\'estat', 500);
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
