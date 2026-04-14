<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotaModel extends Model
{
    protected $table      = 'quotes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $allowedFields = [
        'usuari_id',
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

    protected $validationRules = [
        'usuari_id' => [
            'label' => 'Usuari',
            'rules' => 'required|integer',
        ],
        'nom' => [
            'label' => 'Nom',
            'rules' => 'required|max_length[255]',
        ],
        'descripcio' => [
            'label' => 'Descripció',
            'rules' => 'permit_empty',
        ],
        'import' => [
            'label' => 'Import',
            'rules' => 'required|decimal|greater_than[0]',
        ],
        'periodicitat' => [
            'label' => 'Periodicitat',
            'rules' => 'required|in_list[mensual,trimestral,anual]',
        ],
        'dia_pagament' => [
            'label' => 'Dia de pagament',
            'rules' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[31]',
        ],
        'data_inici' => [
            'label' => 'Data inici',
            'rules' => 'required|valid_date',
        ],
        'data_fi' => [
            'label' => 'Data fi',
            'rules' => 'permit_empty|valid_date',
        ],
        'categoria_id' => [
            'label' => 'Categoria',
            'rules' => 'required|integer',
        ],
        'activa' => [
            'label' => 'Activa',
            'rules' => 'permit_empty',
        ],
    ];

    protected $validationMessages = [
        'nom' => [
            'required' => 'El nom de la quota és obligatori.',
            'max_length' => 'El nom de la quota no pot superar els 255 caràcters.',
        ],
        'import' => [
            'required' => 'L\'import és obligatori.',
            'decimal' => 'L\'import ha de ser un valor decimal vàlid.',
            'greater_than' => 'L\'import ha de ser major que 0.',
        ],
        'periodicitat' => [
            'required' => 'La periodicitat és obligatòria.',
            'in_list' => 'La periodicitat només pot ser mensual, trimestral o anual.',
        ],
        'dia_pagament' => [
            'required' => 'El dia de pagament és obligatori.',
            'integer' => 'El dia de pagament ha de ser un número enter.',
            'greater_than_equal_to' => 'El dia de pagament ha de ser com a mínim 1.',
            'less_than_equal_to' => 'El dia de pagament ha de ser com a màxim 31.',
        ],
        'data_inici' => [
            'required' => 'La data d\'inici és obligatòria.',
            'valid_date' => 'La data d\'inici no és vàlida.',
        ],
        'data_fi' => [
            'valid_date' => 'La data de fi no és vàlida.',
        ],
        'categoria_id' => [
            'required' => 'La categoria és obligatòria.',
            'integer' => 'La categoria ha de ser un número vàlid.',
        ],
        'activa' => [
            'in_list' => 'El valor d\'activa no és vàlid.',
        ],
    ];

    public function calcularPeriodesPendents(array $quota): array
    {
        $quotaId = (int) ($quota['id'] ?? 0);
        if ($quotaId <= 0) {
            return [];
        }

        $dataIniciRaw = (string) ($quota['data_inici'] ?? '');
        if ($dataIniciRaw === '' || strtotime($dataIniciRaw) === false) {
            return [];
        }

        $periodicitat = (string) ($quota['periodicitat'] ?? 'mensual');
        $pasMesos = $this->pasMesosPeriodicitat($periodicitat);
        $import = round((float) ($quota['import'] ?? 0), 2);
        $diaPagament = max(1, min(31, (int) ($quota['dia_pagament'] ?? 1)));

        $avui = new \DateTime(date('Y-m-d'));
        $dataLimit = clone $avui;

        $dataFiRaw = (string) ($quota['data_fi'] ?? '');
        if ($dataFiRaw !== '' && strtotime($dataFiRaw) !== false) {
            $dataFi = new \DateTime($dataFiRaw);
            if ($dataFi < $dataLimit) {
                $dataLimit = $dataFi;
            }
        }

        $periodeActual = new \DateTime(date('Y-m-01', strtotime($dataIniciRaw)));
        if ($periodeActual > $dataLimit) {
            return [];
        }

        $pagaments = \Config\Database::connect()->table('pagaments_quotes')
            ->select('periode_corresponent')
            ->where('quota_id', $quotaId)
            ->where('estat', 'pagat')
            ->where('periode_corresponent IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $periodesPagats = [];
        foreach ($pagaments as $pagament) {
            $periodeCorresponent = (string) ($pagament['periode_corresponent'] ?? '');
            if ($periodeCorresponent !== '') {
                $periodesPagats[$periodeCorresponent] = true;
            }
        }

        $pendents = [];

        while ($periodeActual <= $dataLimit) {
            $periode = $periodeActual->format('Y-m-d');

            if (!isset($periodesPagats[$periode]) && $this->periodeJaHaVencut($periodeActual, $diaPagament, $avui)) {
                $pendents[] = [
                    'periode' => $periode,
                    'import' => $import,
                ];
            }

            $periodeActual->modify('+' . $pasMesos . ' months');
        }

        return $pendents;
    }

    public function obtenirAmbPendents(int $usuariId): array
    {
        $quotes = $this->builder()
            ->select('quotes.*, categories_moviment.nom AS categoria_nom')
            ->join('categories_moviment', 'categories_moviment.id = quotes.categoria_id', 'left')
            ->where('quotes.usuari_id', $usuariId)
            ->where('quotes.activa', true)
            ->where('quotes.deleted_at', null)
            ->orderBy('quotes.id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($quotes as &$quota) {
            $periodesPendents = $this->calcularPeriodesPendents($quota);
            $quota['periodes_pendents_count'] = count($periodesPendents);
            $quota['proper_venciment'] = $periodesPendents[0]['periode'] ?? null;
        }
        unset($quota);

        return $quotes;
    }

    private function pasMesosPeriodicitat(string $periodicitat): int
    {
        if ($periodicitat === 'trimestral') {
            return 3;
        }

        if ($periodicitat === 'anual') {
            return 12;
        }

        return 1;
    }

    private function periodeJaHaVencut(\DateTime $iniciPeriode, int $diaPagament, \DateTime $avui): bool
    {
        $dataVenciment = new \DateTime($iniciPeriode->format('Y-m-01'));
        $diesMes = (int) $dataVenciment->format('t');
        $diaAjustat = min(max($diaPagament, 1), $diesMes);

        $dataVenciment->setDate(
            (int) $dataVenciment->format('Y'),
            (int) $dataVenciment->format('m'),
            $diaAjustat
        );

        return $avui >= $dataVenciment;
    }
}
