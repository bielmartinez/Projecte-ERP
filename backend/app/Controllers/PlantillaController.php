<?php

namespace App\Controllers;

use App\Models\LiniaPlantillaModel;
use App\Models\PlantillaFacturaModel;
use CodeIgniter\HTTP\ResponseInterface;

class PlantillaController extends BaseController
{
    protected PlantillaFacturaModel $plantillaModel;
    protected LiniaPlantillaModel $liniaPlantillaModel;
    /**
     * Inicialitza els models i serveis: PlantillaFacturaModel i LiniaPlantillaModel.
     */
    public function __construct()
    {
        $this->plantillaModel = new PlantillaFacturaModel();
        $this->liniaPlantillaModel = new LiniaPlantillaModel();
    }
    /**
     * Llista plantilla disponibles per a l'usuari autenticat.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function index(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

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

            return $this->jsonOk([
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

            return $this->jsonError('Error al llistar plantilles', 500);
        }
    }
    /**
     * Recupera el detall d'un plantilla concret.
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $plantilla = $this->plantillaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$plantilla) {
                return $this->jsonError('Plantilla no trobada', 404);
            }

            $linies = $this->liniaPlantillaModel->obtenirLiniesPlantilla($id);

            return $this->jsonOk([
                'data' => [
                    'plantilla' => $plantilla,
                    'linies' => $linies,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles show: ' . $e->getMessage());

            return $this->jsonError('Error al carregar la plantilla', 500);
        }
    }
    /**
     * Crea un nou plantilla amb les dades rebudes.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
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
                return $this->jsonError('La plantilla ha de tenir almenys una línia', 422);
            }

            $payload = $this->filtrarPayloadPlantilla($data);
            $payload['usuari_id'] = $usuariId;
            $payload['iva_percentatge'] = (float) ($payload['iva_percentatge'] ?? 21);
            $payload['irpf_percentatge'] = (float) ($payload['irpf_percentatge'] ?? 0);

            $db = \Config\Database::connect();
            $db->transBegin();

            if (!$this->plantillaModel->insert($payload)) {
                $db->transRollback();

                return $this->jsonError('Dades de plantilla no vàlides', 422, ['errors' => $this->plantillaModel->errors()]);
            }

            $plantillaId = (int) $this->plantillaModel->getInsertID();

            foreach (array_values($linies) as $index => $linia) {
                if (!is_array($linia)) {
                    $db->transRollback();

                    return $this->jsonError('Format de línies no vàlid', 422);
                }

                $payloadLinia = $this->filtrarPayloadLinia($linia);
                $payloadLinia['plantilla_id'] = $plantillaId;
                $payloadLinia['ordre'] = $index;
                $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $payload['iva_percentatge']);

                if (!$this->liniaPlantillaModel->insert($payloadLinia)) {
                    $db->transRollback();

                    return $this->jsonError('Dades de línia no vàlides', 422, ['errors' => $this->liniaPlantillaModel->errors()]);
                }
            }

            $db->transCommit();

            return $this->jsonOk([
                'message' => 'Plantilla creada correctament',
                'data' => [
                    'plantilla' => $this->plantillaModel->find($plantillaId),
                    'linies' => $this->liniaPlantillaModel->obtenirLiniesPlantilla($plantillaId),
                ],
            ], 201);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles create: ' . $e->getMessage());

            return $this->jsonError('Error al crear la plantilla', 500);
        }
    }
    /**
     * Actualitza les dades d'un plantilla existent.
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $plantilla = $this->plantillaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$plantilla) {
                return $this->jsonError('Plantilla no trobada', 404);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payload = $this->filtrarPayloadPlantilla($data);
            unset($payload['usuari_id']);

            $db = \Config\Database::connect();
            $db->transBegin();

            if ($payload !== [] && !$this->plantillaModel->update($id, $payload)) {
                $db->transRollback();

                return $this->jsonError('Dades de plantilla no vàlides', 422, ['errors' => $this->plantillaModel->errors()]);
            }

            if (array_key_exists('linies', $data)) {
                $linies = $data['linies'];
                if (!is_array($linies) || $linies === []) {
                    $db->transRollback();

                    return $this->jsonError('La plantilla ha de tenir almenys una línia', 422);
                }

                $this->liniaPlantillaModel->eliminarLiniesPlantilla($id);

                $ivaPlantilla = (float) ($payload['iva_percentatge'] ?? $plantilla['iva_percentatge'] ?? 21);

                foreach (array_values($linies) as $index => $linia) {
                    if (!is_array($linia)) {
                        $db->transRollback();

                        return $this->jsonError('Format de línies no vàlid', 422);
                    }

                    $payloadLinia = $this->filtrarPayloadLinia($linia);
                    $payloadLinia['plantilla_id'] = $id;
                    $payloadLinia['ordre'] = $index;
                    $payloadLinia['iva_percentatge'] = (float) ($payloadLinia['iva_percentatge'] ?? $ivaPlantilla);

                    if (!$this->liniaPlantillaModel->insert($payloadLinia)) {
                        $db->transRollback();

                        return $this->jsonError('Dades de línia no vàlides', 422, ['errors' => $this->liniaPlantillaModel->errors()]);
                    }
                }
            }

            $db->transCommit();

            return $this->jsonOk([
                'message' => 'Plantilla actualitzada correctament',
                'data' => [
                    'plantilla' => $this->plantillaModel->find($id),
                    'linies' => $this->liniaPlantillaModel->obtenirLiniesPlantilla($id),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles update: ' . $e->getMessage());

            return $this->jsonError('Error en actualitzar la plantilla', 500);
        }
    }
    /**
     * Elimina un plantilla (soft delete).
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $plantilla = $this->plantillaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$plantilla) {
                return $this->jsonError('Plantilla no trobada', 404);
            }

            if (!$this->plantillaModel->delete($id)) {
                return $this->jsonError('No s\'ha pogut eliminar la plantilla', 422);
            }

            return $this->jsonOk(['message' => 'Plantilla eliminada correctament']);
        } catch (\Exception $e) {
            log_message('error', 'Error en plantilles delete: ' . $e->getMessage());

            return $this->jsonError('Error en eliminar la plantilla', 500);
        }
    }
    /**
     * Filtra i normalitza payload plantilla.
     *
     * @param array $data Dades d'entrada del procés.
     * @return array Conjunt de dades retornat pel mètode.
     */
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
    /**
     * Filtra i normalitza payload linia.
     *
     * @param array $data Dades d'entrada del procés.
     * @return array Conjunt de dades retornat pel mètode.
     */
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
    /**
     * Obté linies per plantilles segons els filtres indicats.
     *
     * @param array $plantillaIds Valor d'entrada del mètode.
     * @return array Conjunt de dades retornat pel mètode.
     */
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
