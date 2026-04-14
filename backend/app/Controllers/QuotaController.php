<?php

namespace App\Controllers;

use App\Models\CategoriaMovimentModel;
use App\Models\MovimentModel;
use App\Models\PagamentQuotaModel;
use App\Models\QuotaModel;
use CodeIgniter\HTTP\ResponseInterface;

class QuotaController extends BaseController
{
    protected QuotaModel $quotaModel;
    protected PagamentQuotaModel $pagamentModel;
    protected MovimentModel $movimentModel;
    protected CategoriaMovimentModel $categoriaModel;

    public function __construct()
    {
        $this->quotaModel = new QuotaModel();
        $this->pagamentModel = new PagamentQuotaModel();
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

            $activaParam = $this->request->getGet('activa');
            $activaNormalitzada = null;

            if ($activaParam !== null && $activaParam !== '') {
                $activaText = strtolower(trim((string) $activaParam));
                if (in_array($activaText, ['1', 'true'], true)) {
                    $activaNormalitzada = true;
                } elseif (in_array($activaText, ['0', 'false'], true)) {
                    $activaNormalitzada = false;
                } else {
                    return $this->response->setStatusCode(400)->setJSON([
                        'status' => 'error',
                        'message' => 'El filtre activa només pot ser 0 o 1.',
                    ]);
                }
            }

            if ($activaParam !== null && $activaParam !== '' && $activaNormalitzada === null) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'El filtre activa només pot ser 0 o 1.',
                ]);
            }

            if ($activaNormalitzada === true) {
                $quotes = $this->quotaModel->obtenirAmbPendents($usuariId);
            } else {
                $filtreActiva = $activaNormalitzada;
                $quotes = $this->obtenirQuotesAmbPendentsFiltre($usuariId, $filtreActiva);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $quotes,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes index: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al llistar les quotes',
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

            $quota = $this->quotaModel->builder()
                ->select('quotes.*, categories_moviment.nom AS categoria_nom')
                ->join('categories_moviment', 'categories_moviment.id = quotes.categoria_id', 'left')
                ->where('quotes.id', $id)
                ->where('quotes.usuari_id', $usuariId)
                ->where('quotes.deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$quota) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Quota no trobada',
                ]);
            }

            $quota['periodes_pendents'] = $this->quotaModel->calcularPeriodesPendents($quota);
            $quota['pagaments'] = $this->pagamentModel
                ->where('quota_id', $id)
                ->orderBy('periode_corresponent', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll();

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $quota,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes show: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar la quota',
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
            if (!$this->validarCategoriaDespesaUsuario($categoriaId, $usuariId)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La categoria indicada no és vàlida per aquest usuari o tipus.',
                ]);
            }

            if (!$this->quotaModel->insert($payload)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->quotaModel->errors(),
                ]);
            }

            $quotaId = (int) $this->quotaModel->getInsertID();
            $quota = $this->quotaModel->find($quotaId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Quota creada correctament',
                'data' => $quota,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes create: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al crear la quota',
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

            $quota = $this->quotaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$quota) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Quota no trobada',
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

            if (array_key_exists('categoria_id', $payload)) {
                $categoriaId = (int) ($payload['categoria_id'] ?? 0);
                if (!$this->validarCategoriaDespesaUsuario($categoriaId, $usuariId)) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'La categoria indicada no és vàlida per aquest usuari o tipus.',
                    ]);
                }
            }

            if (!$this->quotaModel->update($id, $payload)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->quotaModel->errors(),
                ]);
            }

            $quotaActualitzada = $this->quotaModel->find($id);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Quota actualitzada correctament',
                'data' => $quotaActualitzada,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes update: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al actualitzar la quota',
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

            $quota = $this->quotaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$quota) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Quota no trobada',
                ]);
            }

            if (!$this->quotaModel->delete($id)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut eliminar la quota',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Quota eliminada correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes delete: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al eliminar la quota',
            ]);
        }
    }

    public function pagar(int $id): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $quota = $this->quotaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->where('deleted_at', null)
                ->first();

            if (!$quota) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Quota no trobada',
                ]);
            }

            if (!$this->normalitzarBoolea($quota['activa'] ?? false)) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'La quota està inactiva i no es pot pagar.',
                ]);
            }

            $data = $this->request->getJSON(true) ?? [];
            if ($data === []) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
            }

            $periodeCorresponent = trim((string) ($data['periode_corresponent'] ?? ''));
            if ($periodeCorresponent === '' || strtotime($periodeCorresponent) === false) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'El període corresponent és obligatori i ha de ser una data vàlida.',
                ]);
            }

            $periodeCorresponent = date('Y-m-d', strtotime($periodeCorresponent));
            if (date('d', strtotime($periodeCorresponent)) !== '01') {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'El període corresponent ha de ser el primer dia del període.',
                ]);
            }

            $periodesPendents = $this->quotaModel->calcularPeriodesPendents($quota);
            $periodePendent = false;
            foreach ($periodesPendents as $pendent) {
                if (($pendent['periode'] ?? '') === $periodeCorresponent) {
                    $periodePendent = true;
                    break;
                }
            }

            if (!$periodePendent) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'El període indicat no està pendent de pagament.',
                ]);
            }

            $jaPagat = $this->pagamentModel
                ->where('quota_id', $id)
                ->where('periode_corresponent', $periodeCorresponent)
                ->where('estat', 'pagat')
                ->first();

            if ($jaPagat) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error',
                    'message' => 'Aquest període ja està pagat.',
                ]);
            }

            $categoriaId = (int) ($quota['categoria_id'] ?? 0);
            if (!$this->validarCategoriaDespesaUsuario($categoriaId, $usuariId)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La categoria de la quota no és vàlida per generar el moviment de despesa.',
                ]);
            }

            $importPagat = array_key_exists('import', $data)
                ? (float) $data['import']
                : (float) ($quota['import'] ?? 0);

            if ($importPagat <= 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'L\'import del pagament ha de ser major que 0.',
                ]);
            }

            $notes = null;
            if (array_key_exists('notes', $data)) {
                $notesValor = is_string($data['notes']) ? trim($data['notes']) : $data['notes'];
                $notes = $notesValor === '' ? null : $notesValor;
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            $movimentPayload = [
                'usuari_id' => $usuariId,
                'categoria_id' => $categoriaId,
                'tipus' => 'despesa',
                'descripcio' => 'Quota: ' . (string) ($quota['nom'] ?? 'Sense nom') . ' - ' . $this->nomMes($periodeCorresponent),
                'import' => round($importPagat, 2),
                'data' => date('Y-m-d'),
            ];

            if (!$this->movimentModel->insert($movimentPayload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut crear el moviment de despesa del pagament',
                    'errors' => $this->movimentModel->errors(),
                ]);
            }

            $movimentId = (int) $this->movimentModel->getInsertID();

            $pagamentPayload = [
                'quota_id' => $id,
                'moviment_id' => $movimentId,
                'data_pagament' => date('Y-m-d'),
                'import' => round($importPagat, 2),
                'estat' => 'pagat',
                'periode_corresponent' => $periodeCorresponent,
                'notes' => $notes,
            ];

            if (!$this->pagamentModel->insert($pagamentPayload)) {
                $db->transRollback();

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut registrar el pagament de la quota',
                    'errors' => $this->pagamentModel->errors(),
                ]);
            }

            $db->transCommit();

            $pagamentId = (int) $this->pagamentModel->getInsertID();
            $pagament = $this->pagamentModel->find($pagamentId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'message' => 'Pagament registrat correctament',
                'data' => $pagament,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes pagar: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al registrar el pagament de la quota',
            ]);
        }
    }

    public function pagaments(int $id): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $quota = $this->quotaModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->where('deleted_at', null)
                ->first();

            if (!$quota) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Quota no trobada',
                ]);
            }

            $pagaments = $this->pagamentModel
                ->where('quota_id', $id)
                ->orderBy('periode_corresponent', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll();

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $pagaments,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en quotes pagaments: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar els pagaments de la quota',
            ]);
        }
    }

    private function validarCategoriaDespesaUsuario(int $categoriaId, int $usuariId): bool
    {
        if ($categoriaId <= 0) {
            return false;
        }

        $categoria = $this->categoriaModel
            ->where('id', $categoriaId)
            ->where('usuari_id', $usuariId)
            ->where('tipus', 'despesa')
            ->first();

        return (bool) $categoria;
    }

    private function obtenirQuotesAmbPendentsFiltre(int $usuariId, ?bool $activa = null): array
    {
        $builder = $this->quotaModel->builder();
        $builder->select('quotes.*, categories_moviment.nom AS categoria_nom');
        $builder->join('categories_moviment', 'categories_moviment.id = quotes.categoria_id', 'left');
        $builder->where('quotes.usuari_id', $usuariId);
        $builder->where('quotes.deleted_at', null);

        if ($activa !== null) {
            $builder->where('quotes.activa', $activa);
        }

        $quotes = $builder
            ->orderBy('quotes.id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($quotes as &$quota) {
            $periodesPendents = $this->quotaModel->calcularPeriodesPendents($quota);
            $quota['periodes_pendents_count'] = count($periodesPendents);
            $quota['proper_venciment'] = $periodesPendents[0]['periode'] ?? null;
        }
        unset($quota);

        return $quotes;
    }

    private function filtrarPayload(array $data): array
    {
        $campsPermesos = [
            'nom',
            'descripcio',
            'import',
            'periodicitat',
            'dia_pagament',
            'data_inici',
            'data_fi',
            'categoria_id',
            'activa',
        ];

        $payload = [];

        foreach ($campsPermesos as $camp) {
            if (!array_key_exists($camp, $data)) {
                continue;
            }

            if (in_array($camp, ['categoria_id', 'dia_pagament'], true)) {
                $payload[$camp] = $data[$camp] === null || $data[$camp] === '' ? null : (int) $data[$camp];
                continue;
            }

            if ($camp === 'import') {
                $payload[$camp] = (float) $data[$camp];
                continue;
            }

            if ($camp === 'activa') {
                if (is_bool($data[$camp])) {
                    $payload[$camp] = $data[$camp];
                } elseif ($data[$camp] === null || $data[$camp] === '') {
                    $payload[$camp] = true;
                } else {
                    $payload[$camp] = in_array($data[$camp], [1, '1', 'true', 'on'], true);
                }
                continue;
            }
            
            $valor = is_string($data[$camp]) ? trim($data[$camp]) : $data[$camp];
            if (in_array($camp, ['descripcio', 'data_fi'], true) && $valor === '') {
                $payload[$camp] = null;
                continue;
            }

            $payload[$camp] = $valor;
        }

        return $payload;
    }

    private function nomMes(string $data): string
    {
        $mesos = [
            1 => 'Gener',
            2 => 'Febrer',
            3 => 'Març',
            4 => 'Abril',
            5 => 'Maig',
            6 => 'Juny',
            7 => 'Juliol',
            8 => 'Agost',
            9 => 'Setembre',
            10 => 'Octubre',
            11 => 'Novembre',
            12 => 'Desembre',
        ];

        $mes = (int) date('n', strtotime($data));
        $any = date('Y', strtotime($data));

        return ($mesos[$mes] ?? '') . ' ' . $any;
    }

    private function normalitzarBoolea(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_int($valor) || is_float($valor)) {
            return (int) $valor === 1;
        }

        if (is_string($valor)) {
            return in_array(strtolower(trim($valor)), ['1', 'true', 't', 'on', 'yes'], true);
        }

        return false;
    }
}
