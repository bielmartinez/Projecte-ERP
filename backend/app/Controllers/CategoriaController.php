<?php

namespace App\Controllers;

use App\Models\CategoriaMovimentModel;
use CodeIgniter\HTTP\ResponseInterface;

class CategoriaController extends BaseController
{
    protected CategoriaMovimentModel $categoriaModel;
    /**
     * Inicialitza els models i serveis: CategoriaMovimentModel.
     */
    public function __construct()
    {
        $this->categoriaModel = new CategoriaMovimentModel();
    }
    /**
     * Llista categoria disponibles per a l'usuari autenticat.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function index(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $limit = (int) ($this->request->getGet('limit') ?? 10);
            $limit = max(1, min($limit, 100));
            $search = trim((string) ($this->request->getGet('search') ?? ''));
            $tipus = trim((string) ($this->request->getGet('tipus') ?? ''));

            $builder = $this->categoriaModel->builder();
            $builder->where('usuari_id', $usuariId);
            $builder->where('deleted_at', null);

            if ($search !== '') {
                $builder->like('nom', $search);
            }

            if (in_array($tipus, ['ingres', 'despesa'], true)) {
                $builder->where('tipus', $tipus);
            }

            $total = (int) $builder->countAllResults(false);

            $categories = $builder
                ->orderBy('tipus', 'ASC')
                ->orderBy('nom', 'ASC')
                ->limit($limit, ($page - 1) * $limit)
                ->get()
                ->getResultArray();

            return $this->jsonOk([
                'data' => $categories,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                    'search' => $search,
                    'tipus' => $tipus,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en categories index: ' . $e->getMessage());
            return $this->jsonError('Error al llistar les categories', 500);
        }
    }
    /**
     * Recupera el detall d'un categoria concret.
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $categoria = $this->categoriaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$categoria) {
                return $this->jsonError('Categoria no trobada', 404);
            }

            return $this->jsonOk(['data' => $categoria]);
        } catch (\Exception $e) {
            log_message('error', 'Error en categories show: ' . $e->getMessage());
            return $this->jsonError('Error al carregar la categoria', 500);
        }
    }
    /**
     * Crea un nou categoria amb les dades rebudes.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function create(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $data = $this->request->getJSON(true);
            if (!$data) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payload = $this->filtrarPayload($data);
            $payload['usuari_id'] = $usuariId;

            if (!$this->categoriaModel->insert($payload)) {
                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->categoriaModel->errors()]);
            }

            $categoriaId = (int) $this->categoriaModel->getInsertID();
            $categoria = $this->categoriaModel->find($categoriaId);

            return $this->jsonOk([
                'message' => 'Categoria creada correctament',
                'data' => $categoria,
            ], 201);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'uq_categories_moviment_usuari_tipus_nom')) {
                return $this->jsonError('Ja existeix una categoria amb aquest nom i tipus.', 409);
            }

            log_message('error', 'Error en categories create: ' . $message);
            return $this->jsonError('Error al crear la categoria', 500);
        }
    }
    /**
     * Actualitza les dades d'un categoria existent.
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $categoria = $this->categoriaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$categoria) {
                return $this->jsonError('Categoria no trobada', 404);
            }

            $data = $this->request->getJSON(true);
            if (!$data) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payload = $this->filtrarPayload($data);
            if (empty($payload)) {
                return $this->jsonError('No hi ha camps vàlids per actualitzar', 400);
            }

            if (!$this->categoriaModel->update($id, $payload)) {
                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->categoriaModel->errors()]);
            }

            $categoriaActualitzada = $this->categoriaModel->find($id);

            return $this->jsonOk([
                'message' => 'Categoria actualitzada correctament',
                'data' => $categoriaActualitzada,
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'uq_categories_moviment_usuari_tipus_nom')) {
                return $this->jsonError('Ja existeix una categoria amb aquest nom i tipus.', 409);
            }

            log_message('error', 'Error en categories update: ' . $message);
            return $this->jsonError('Error al actualitzar la categoria', 500);
        }
    }
    /**
     * Elimina un categoria (soft delete).
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $categoria = $this->categoriaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$categoria) {
                return $this->jsonError('Categoria no trobada', 404);
            }

            $movimentsActius = \Config\Database::connect()->table('moviments')
                ->where('categoria_id', $id)
                ->where('deleted_at', null)
                ->countAllResults();

            if ($movimentsActius > 0) {
                return $this->jsonError('No es pot eliminar la categoria perquè té moviments associats.', 409);
            }

            $this->categoriaModel->delete($id);

            return $this->jsonOk(['message' => 'Categoria eliminada correctament']);
        } catch (\Exception $e) {
            log_message('error', 'Error en categories delete: ' . $e->getMessage());
            return $this->jsonError('Error al eliminar la categoria', 500);
        }
    }
    /**
     * Filtra i normalitza payload.
     *
     * @param array $data Dades d'entrada del procés.
     * @return array Conjunt de dades retornat pel mètode.
     */
    private function filtrarPayload(array $data): array
    {
        $fieldsPermesos = [
            'nom',
            'tipus',
        ];

        $payload = [];
        foreach ($fieldsPermesos as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        return $payload;
    }
}
