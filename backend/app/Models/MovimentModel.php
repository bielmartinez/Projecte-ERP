<?php

namespace App\Models;

use CodeIgniter\Model;

class MovimentModel extends Model
{
    protected $table      = 'moviments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $allowedFields = [
        'usuari_id',
        'categoria_id',
        'factura_id',
        'tipus',
        'descripcio',
        'import',
        'data',
    ];

    protected $validationRules = [
        'usuari_id' => [
            'label' => 'Usuari',
            'rules' => 'required|integer',
        ],
        'categoria_id' => [
            'label' => 'Categoria',
            'rules' => 'required|integer',
        ],
        'factura_id' => [
            'label' => 'Factura',
            'rules' => 'permit_empty|integer',
        ],
        'tipus' => [
            'label' => 'Tipus',
            'rules' => 'required|in_list[ingres,despesa]',
        ],
        'descripcio' => [
            'label' => 'Descripció',
            'rules' => 'required|max_length[500]',
        ],
        'import' => [
            'label' => 'Import',
            'rules' => 'required|decimal|greater_than[0]',
        ],
        'data' => [
            'label' => 'Data',
            'rules' => 'required|valid_date',
        ],
    ];

    protected $validationMessages = [
        'categoria_id' => [
            'required' => 'La categoria és obligatòria.',
        ],
        'tipus' => [
            'required' => 'El tipus és obligatori.',
            'in_list' => 'El tipus només pot ser ingres o despesa.',
        ],
        'descripcio' => [
            'required' => 'La descripció és obligatòria.',
        ],
        'import' => [
            'required' => 'L\'import és obligatori.',
            'greater_than' => 'L\'import ha de ser major que 0.',
        ],
        'data' => [
            'required' => 'La data és obligatòria.',
            'valid_date' => 'La data no és vàlida.',
        ],
    ];
}
