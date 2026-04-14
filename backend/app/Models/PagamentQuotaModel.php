<?php

namespace App\Models;

use CodeIgniter\Model;

class PagamentQuotaModel extends Model
{
    protected $table      = 'pagaments_quotes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'quota_id',
        'moviment_id',
        'data_pagament',
        'import',
        'estat',
        'periode_corresponent',
        'notes',
    ];

    protected $validationRules = [
        'quota_id' => [
            'label' => 'Quota',
            'rules' => 'required|integer',
        ],
        'moviment_id' => [
            'label' => 'Moviment',
            'rules' => 'permit_empty|integer',
        ],
        'data_pagament' => [
            'label' => 'Data de pagament',
            'rules' => 'required|valid_date',
        ],
        'import' => [
            'label' => 'Import',
            'rules' => 'required|decimal|greater_than[0]',
        ],
        'estat' => [
            'label' => 'Estat',
            'rules' => 'permit_empty|in_list[pendent,pagat]',
        ],
        'periode_corresponent' => [
            'label' => 'Període corresponent',
            'rules' => 'required|valid_date',
        ],
        'notes' => [
            'label' => 'Notes',
            'rules' => 'permit_empty',
        ],
    ];

    protected $validationMessages = [
        'quota_id' => [
            'required' => 'La quota és obligatòria.',
            'integer' => 'La quota ha de ser un número vàlid.',
        ],
        'moviment_id' => [
            'integer' => 'El moviment ha de ser un número vàlid.',
        ],
        'data_pagament' => [
            'required' => 'La data de pagament és obligatòria.',
            'valid_date' => 'La data de pagament no és vàlida.',
        ],
        'import' => [
            'required' => 'L\'import és obligatori.',
            'decimal' => 'L\'import ha de ser un valor decimal vàlid.',
            'greater_than' => 'L\'import ha de ser major que 0.',
        ],
        'estat' => [
            'in_list' => 'L\'estat només pot ser pendent o pagat.',
        ],
        'periode_corresponent' => [
            'required' => 'El període corresponent és obligatori.',
            'valid_date' => 'El període corresponent no és vàlid.',
        ],
    ];
}
