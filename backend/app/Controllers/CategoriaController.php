<?php

namespace App\Controllers;

use App\Models\CategoriaMovimentModel;
use CodeIgniter\HTTP\ResponseInterface;

class CategoriaController extends BaseController
{
    protected CategoriaMovimentModel $categoriaModel;

    public function __construct()
    {
        $this->categoriaModel = new CategoriaMovimentModel();
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

            return $this->response->setJSON([
                'status' => 'ok',
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
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al llistar les categories',
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

            $categoria = $this->categoriaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$categoria) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Categoria no trobada',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $categoria,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en categories show: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar la categoria',
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

            $data = $this->request->getJSON(true);
            if (!$data) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $payload = $this->filtrarPayload($data);
            $payload['usuari_id'] = $usuariId;

            if (!$this->categoriaModel->insert($payload)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->categoriaModel->errors(),
                ]);
            }

            $categoriaId = (int) $this->categoriaModel->getInsertID();
            $categoria = $this->categoriaModel->find($categoriaId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Categoria creada correctament',
                'data' => $categoria,
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'uq_categories_moviment_usuari_tipus_nom')) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'Ja existeix una categoria amb aquest nom i tipus.',
                ]);
            }

            log_message('error', 'Error en categories create: ' . $message);
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al crear la categoria',
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

            $categoria = $this->categoriaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$categoria) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Categoria no trobada',
                ]);
            }

            $data = $this->request->getJSON(true);
            if (!$data) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $payload = $this->filtrarPayload($data);
            if (empty($payload)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No hi ha camps vàlids per actualitzar',
                ]);
            }

            if (!$this->categoriaModel->update($id, $payload)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->categoriaModel->errors(),
                ]);
            }

            $categoriaActualitzada = $this->categoriaModel->find($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Categoria actualitzada correctament',
                'data' => $categoriaActualitzada,
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'uq_categories_moviment_usuari_tipus_nom')) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'Ja existeix una categoria amb aquest nom i tipus.',
                ]);
            }

            log_message('error', 'Error en categories update: ' . $message);
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al actualitzar la categoria',
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

            $categoria = $this->categoriaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$categoria) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Categoria no trobada',
                ]);
            }

            $movimentsActius = \Config\Database::connect()->table('moviments')
                ->where('categoria_id', $id)
                ->where('deleted_at', null)
                ->countAllResults();

            if ($movimentsActius > 0) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'No es pot eliminar la categoria perquè té moviments associats.',
                ]);
            }

            $this->categoriaModel->delete($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Categoria eliminada correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en categories delete: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al eliminar la categoria',
            ]);
        }
    }

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
