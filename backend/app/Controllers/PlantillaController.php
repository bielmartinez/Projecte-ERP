<?php

namespace App\Controllers;

use App\Models\LiniaPlantillaModel;
use App\Models\PlantillaFacturaModel;
use CodeIgniter\HTTP\ResponseInterface;

class PlantillaController extends BaseController
{
    protected PlantillaFacturaModel $plantillaModel;
    protected LiniaPlantillaModel $liniaPlantillaModel;

    public function __construct()
    {
        $this->plantillaModel = new PlantillaFacturaModel();
        $this->liniaPlantillaModel = new LiniaPlantillaModel();
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

            $builder = $this->plantillaModel->builder();
            $builder->where('usuari_id', $usuariId);

            if ($search !== '') {
                $builder->groupStart()
                    ->like('nom', $search)
                    ->orLike('descripcio', $search)
                    ->groupEnd();
            }

            $total = (int) $builder->countAllResults(false);
            $plantilles = $builder
                ->orderBy('created_at', 'DESC')
                ->limit($limit, ($page - 1) * $limit)
                ->get()
                ->getResultArray();

            $plantillaIds = array_map(static fn ($item) => (int) $item['id'], $plantilles);
            $liniesByPlantilla = $this->obtenirLiniesPerPlantilles($plantillaIds);

            foreach ($plantilles as &$plantilla) {
                $id = (int) $plantilla['id'];
                $plantilla['linies'] = $liniesByPlantilla[$id] ?? [];
            }
            unset($plantilla);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $plantilles,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                    'search' => $search,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles index: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al llistar plantilles',
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

            $plantilla = $this->plantillaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$plantilla) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Plantilla no trobada',
                ]);
            }

            $linies = $this->liniaPlantillaModel->obtenirLiniesPlantilla($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => [
                    'plantilla' => $plantilla,
                    'linies' => $linies,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles show: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar la plantilla',
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
                    'message' => 'La plantilla ha de tenir almenys una línia',
                ]);
            }

            $payload = $this->filtrarPayloadPlantilla($data);
            $payload['usuari_id'] = $usuariId;
            $payload['iva_percentatge'] = (float) ($payload['iva_percentatge'] ?? 21);
            $payload['irpf_percentatge'] = (float) ($payload['irpf_percentatge'] ?? 0);

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->plantillaModel->insert($payload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades de plantilla no vàlides',
                    'errors' => $this->plantillaModel->errors(),
                ]);
            }

            $plantillaId = (int) $this->plantillaModel->getInsertID();

            foreach (array_values($linies) as $index => $linia) {
                if (!is_array($linia)) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'Format de línies no vàlid',
                    ]);
                }

                $payloadLinia = $this->filtrarPayloadLinia($linia);
                $payloadLinia['plantilla_id'] = $plantillaId;
                $payloadLinia['ordre'] = $index;
                $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $payload['iva_percentatge']);

                if (!$this->liniaPlantillaModel->insert($payloadLinia)) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'Dades de línia no vàlides',
                        'errors' => $this->liniaPlantillaModel->errors(),
                    ]);
                }
            }

            $db->transCommit();

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Plantilla creada correctament',
                'data' => [
                    'plantilla' => $this->plantillaModel->find($plantillaId),
                    'linies' => $this->liniaPlantillaModel->obtenirLiniesPlantilla($plantillaId),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles create: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al crear la plantilla',
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

            $plantilla = $this->plantillaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$plantilla) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Plantilla no trobada',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $payload = $this->filtrarPayloadPlantilla($data);
            unset($payload['usuari_id']);

            $db = \Config\Database::connect();
            $db->transBegin();

            if ($payload !== [] && !$this->plantillaModel->update($id, $payload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades de plantilla no vàlides',
                    'errors' => $this->plantillaModel->errors(),
                ]);
            }

            if (array_key_exists('linies', $data)) {
                $linies = $data['linies'];
                if (!is_array($linies) || $linies === []) {
                    $db->transRollback();

                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'La plantilla ha de tenir almenys una línia',
                    ]);
                }

                $this->liniaPlantillaModel->eliminarLiniesPlantilla($id);

                $ivaPlantilla = (float) ($payload['iva_percentatge'] ?? $plantilla['iva_percentatge'] ?? 21);

                foreach (array_values($linies) as $index => $linia) {
                    if (!is_array($linia)) {
                        $db->transRollback();

                        return $this->response->setStatusCode(422)->setJSON([
                            'status' => 'error',
                            'message' => 'Format de línies no vàlid',
                        ]);
                    }

                    $payloadLinia = $this->filtrarPayloadLinia($linia);
                    $payloadLinia['plantilla_id'] = $id;
                    $payloadLinia['ordre'] = $index;
                    $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $ivaPlantilla);

                    if (!$this->liniaPlantillaModel->insert($payloadLinia)) {
                        $db->transRollback();

                        return $this->response->setStatusCode(422)->setJSON([
                            'status' => 'error',
                            'message' => 'Dades de línia no vàlides',
                            'errors' => $this->liniaPlantillaModel->errors(),
                        ]);
                    }
                }
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Plantilla actualitzada correctament',
                'data' => [
                    'plantilla' => $this->plantillaModel->find($id),
                    'linies' => $this->liniaPlantillaModel->obtenirLiniesPlantilla($id),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles update: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en actualitzar la plantilla',
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

            $plantilla = $this->plantillaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$plantilla) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Plantilla no trobada',
                ]);
            }

            if (!$this->plantillaModel->delete($id)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut eliminar la plantilla',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Plantilla eliminada correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles delete: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en eliminar la plantilla',
            ]);
        }
    }

    private function filtrarPayloadPlantilla(array $data): array
    {
        $campsPermesos = [
            'nom',
            'descripcio',
            'iva_percentatge',
            'irpf_percentatge',
            'metode_pagament',
            'notes_plantilla',
        ];

        $payload = [];

        foreach ($campsPermesos as $camp) {
            if (array_key_exists($camp, $data)) {
                $payload[$camp] = $data[$camp];
            }
        }

        if (array_key_exists('notes', $data) && !array_key_exists('notes_plantilla', $payload)) {
            $payload['notes_plantilla'] = $data['notes'];
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

    private function obtenirLiniesPerPlantilles(array $plantillaIds): array
    {
        if ($plantillaIds === []) {
            return [];
        }

        $linies = $this->liniaPlantillaModel
            ->whereIn('plantilla_id', $plantillaIds)
            ->orderBy('ordre', 'ASC')
            ->findAll();

        $resultat = [];
        foreach ($linies as $linia) {
            $plantillaId = (int) $linia['plantilla_id'];
            if (!isset($resultat[$plantillaId])) {
                $resultat[$plantillaId] = [];
            }

            $resultat[$plantillaId][] = $linia;
        }

        return $resultat;
    }

}
