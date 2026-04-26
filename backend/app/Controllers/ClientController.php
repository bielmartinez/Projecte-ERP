<?php

namespace App\Controllers;

use App\Models\ClientModel;
use CodeIgniter\HTTP\ResponseInterface;

class ClientController extends BaseController
{
    protected ClientModel $clientModel;

    public function __construct()
    {
        $this->clientModel = new ClientModel();
    }

    public function index(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $limit = (int) ($this->request->getGet('limit') ?? 10);
            $limit = max(1, min($limit, 100));
            $search = trim((string) ($this->request->getGet('search') ?? ''));

            $builder = $this->clientModel->builder();
            $builder->where('usuari_id', $usuariId);
            $builder->where('deleted_at', null);

            if ($search !== '') {
                $builder->groupStart()
                    ->like('nom', $search)
                    ->orLike('cognoms', $search)
                    ->orLike('nom_empresa', $search)
                    ->orLike('nif', $search)
                    ->orLike('email', $search)
                    ->groupEnd();
            }

            $total = (int) $builder->countAllResults(false);

            $clients = $builder
                ->orderBy('created_at', 'DESC')
                ->limit($limit, ($page - 1) * $limit)
                ->get()
                ->getResultArray();

            return $this->jsonOk([
                'data' => $clients,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                    'search' => $search,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en clients index: ' . $e->getMessage());
            return $this->jsonError('Error al llistar els clients', 500);
        }
    }

    public function show(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $client = $this->clientModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$client) {
                return $this->jsonError('Client no trobat', 404);
            }

            $db = \Config\Database::connect();
            $factures = $db->table('factures')
                ->select('id, numero_factura, data_emisio, estat, total')
                ->where('usuari_id', $usuariId)
                ->where('client_id', $id)
                ->where('deleted_at', null)
                ->orderBy('data_emisio', 'DESC')
                ->get()
                ->getResultArray();

            return $this->jsonOk([
                'data' => [
                    'client' => $client,
                    'factures' => $factures,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en clients show: ' . $e->getMessage());
            return $this->jsonError('Error al carregar el client', 500);
        }
    }

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

            if (!$this->clientModel->insert($payload)) {
                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->clientModel->errors()]);
            }

            $clientId = (int) $this->clientModel->getInsertID();
            $client = $this->clientModel->find($clientId);

            return $this->jsonOk([
                'message' => 'Client creat correctament',
                'data' => $client,
            ], 201);
        } catch (\Exception $e) {
            log_message('error', 'Error en clients create: ' . $e->getMessage());
            return $this->jsonError('Error al crear el client', 500);
        }
    }

    public function update(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $client = $this->clientModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$client) {
                return $this->jsonError('Client no trobat', 404);
            }

            $data = $this->request->getJSON(true);
            if (!$data) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            $payload = $this->filtrarPayload($data);
            if (empty($payload)) {
                return $this->jsonError('No hi ha camps vàlids per actualitzar', 400);
            }

            if (!$this->clientModel->update($id, $payload)) {
                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->clientModel->errors()]);
            }

            $clientActualitzat = $this->clientModel->find($id);

            return $this->jsonOk([
                'message' => 'Client actualitzat correctament',
                'data' => $clientActualitzat,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en clients update: ' . $e->getMessage());
            return $this->jsonError('Error al actualitzar el client', 500);
        }
    }

    public function delete(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $client = $this->clientModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$client) {
                return $this->jsonError('Client no trobat', 404);
            }

            $this->clientModel->delete($id);

            return $this->jsonOk(['message' => 'Client eliminat correctament']);
        } catch (\Exception $e) {
            log_message('error', 'Error en clients delete: ' . $e->getMessage());
            return $this->jsonError('Error al eliminar el client', 500);
        }
    }

    private function filtrarPayload(array $data): array
    {
        $fieldsPermesos = [
            'nom',
            'cognoms',
            'nom_empresa',
            'nif',
            'email',
            'telefon',
            'adreca',
            'codi_postal',
            'poblacio',
            'provincia',
            'pais',
            'notes',
        ];

        $payload = [];
        foreach ($fieldsPermesos as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        return $payload;
    }
}
