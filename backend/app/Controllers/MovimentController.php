<?php

namespace App\Controllers;

use App\Models\CategoriaMovimentModel;
use App\Models\MovimentModel;
use CodeIgniter\HTTP\ResponseInterface;

class MovimentController extends BaseController
{
    protected MovimentModel $movimentModel;
    protected CategoriaMovimentModel $categoriaModel;

    public function __construct()
    {
        $this->movimentModel = new MovimentModel();
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
            $categoriaId = (int) ($this->request->getGet('categoria_id') ?? 0);
            $dataDesde = trim((string) ($this->request->getGet('data_desde') ?? ''));
            $dataFins = trim((string) ($this->request->getGet('data_fins') ?? ''));

            $builder = $this->movimentModel->builder();
            $builder->select('moviments.*, categories_moviment.nom AS categoria_nom');
            $builder->join('categories_moviment', 'categories_moviment.id = moviments.categoria_id', 'left');
            $builder->where('moviments.usuari_id', $usuariId);
            $builder->where('moviments.deleted_at', null);

            if ($search !== '') {
                $builder->groupStart()
                    ->like('moviments.descripcio', $search)
                    ->orLike('categories_moviment.nom', $search)
                    ->groupEnd();
            }

            if (in_array($tipus, ['ingres', 'despesa'], true)) {
                $builder->where('moviments.tipus', $tipus);
            }

            if ($categoriaId > 0) {
                $builder->where('moviments.categoria_id', $categoriaId);
            }

            if ($dataDesde !== '' && strtotime($dataDesde) !== false) {
                $builder->where('moviments.data >=', $dataDesde);
            }

            if ($dataFins !== '' && strtotime($dataFins) !== false) {
                $builder->where('moviments.data <=', $dataFins);
            }

            $total = (int) $builder->countAllResults(false);

            $moviments = $builder
                ->orderBy('moviments.data', 'DESC')
                ->orderBy('moviments.id', 'DESC')
                ->limit($limit, ($page - 1) * $limit)
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $moviments,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                    'search' => $search,
                    'tipus' => $tipus,
                    'categoria_id' => $categoriaId ?: null,
                    'data_desde' => $dataDesde,
                    'data_fins' => $dataFins,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en moviments index: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al llistar els moviments',
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

            $builder = $this->movimentModel->builder();
            $moviment = $builder
                ->select('moviments.*, categories_moviment.nom AS categoria_nom')
                ->join('categories_moviment', 'categories_moviment.id = moviments.categoria_id', 'left')
                ->where('moviments.id', $id)
                ->where('moviments.usuari_id', $usuariId)
                ->where('moviments.deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$moviment) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Moviment no trobat',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $moviment,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en moviments show: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar el moviment',
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

            $categoriaId = (int) ($payload['categoria_id'] ?? 0);
            $tipus = (string) ($payload['tipus'] ?? '');

            if (!$this->categoriaEsDelUsuari($categoriaId, $usuariId, $tipus)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La categoria indicada no és vàlida per aquest usuari o tipus.',
                ]);
            }

            if (isset($payload['factura_id']) && (int) $payload['factura_id'] > 0) {
                if (!$this->facturaEsDelUsuari((int) $payload['factura_id'], $usuariId)) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'La factura indicada no és vàlida per aquest usuari.',
                    ]);
                }
            }

            if (!$this->movimentModel->insert($payload)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->movimentModel->errors(),
                ]);
            }

            $movimentId = (int) $this->movimentModel->getInsertID();
            $moviment = $this->movimentModel->find($movimentId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Moviment creat correctament',
                'data' => $moviment,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en moviments create: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al crear el moviment',
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

            $moviment = $this->movimentModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$moviment) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Moviment no trobat',
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

            $categoriaId = (int) ($payload['categoria_id'] ?? $moviment['categoria_id']);
            $tipus = (string) ($payload['tipus'] ?? $moviment['tipus']);

            if (!$this->categoriaEsDelUsuari($categoriaId, $usuariId, $tipus)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La categoria indicada no és vàlida per aquest usuari o tipus.',
                ]);
            }

            if (array_key_exists('factura_id', $payload) && (int) $payload['factura_id'] > 0) {
                if (!$this->facturaEsDelUsuari((int) $payload['factura_id'], $usuariId)) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'La factura indicada no és vàlida per aquest usuari.',
                    ]);
                }
            }

            if (!$this->movimentModel->update($id, $payload)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->movimentModel->errors(),
                ]);
            }

            $movimentActualitzat = $this->movimentModel->find($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Moviment actualitzat correctament',
                'data' => $movimentActualitzat,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en moviments update: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al actualitzar el moviment',
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

            $moviment = $this->movimentModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$moviment) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Moviment no trobat',
                ]);
            }

            $this->movimentModel->delete($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Moviment eliminat correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en moviments delete: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al eliminar el moviment',
            ]);
        }
    }

    private function categoriaEsDelUsuari(int $categoriaId, int $usuariId, string $tipus): bool
    {
        if ($categoriaId <= 0 || !in_array($tipus, ['ingres', 'despesa'], true)) {
            return false;
        }

        $categoria = $this->categoriaModel
            ->where('id', $categoriaId)
            ->where('usuari_id', $usuariId)
            ->where('tipus', $tipus)
            ->first();

        return (bool) $categoria;
    }

    private function facturaEsDelUsuari(int $facturaId, int $usuariId): bool
    {
        if ($facturaId <= 0) {
            return false;
        }

        $factura = \Config\Database::connect()->table('factures')
            ->select('id')
            ->where('id', $facturaId)
            ->where('usuari_id', $usuariId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return (bool) $factura;
    }

    private function filtrarPayload(array $data): array
    {
        $fieldsPermesos = [
            'categoria_id',
            'factura_id',
            'tipus',
            'descripcio',
            'import',
            'data',
        ];

        $payload = [];
        foreach ($fieldsPermesos as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (in_array($field, ['categoria_id', 'factura_id'], true)) {
                $payload[$field] = $data[$field] === null || $data[$field] === '' ? null : (int) $data[$field];
                continue;
            }

            if ($field === 'import') {
                $payload[$field] = (float) $data[$field];
                continue;
            }

            $payload[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
        }

        return $payload;
    }
}
