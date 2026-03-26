<?php

namespace App\Models;

use CodeIgniter\Model;

class PlantillaFacturaModel extends Model
{
    protected $table      = 'plantilles_factura';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'usuari_id',
        'nom',
        'descripcio',
        'iva_percentatge',
        'irpf_percentatge',
        'metode_pagament',
        'notes_plantilla',
    ];

    protected $validationRules = [
        'usuari_id' => [
            'label' => 'Usuari',
            'rules' => 'required|integer',
        ],
        'nom' => [
            'label' => 'Nom de plantilla',
            'rules' => 'required|max_length[100]',
        ],
        'descripcio' => [
            'label' => 'Descripció',
            'rules' => 'permit_empty',
        ],
        'iva_percentatge' => [
            'label' => 'IVA (%)',
            'rules' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ],
        'irpf_percentatge' => [
            'label' => 'IRPF (%)',
            'rules' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ],
        'metode_pagament' => [
            'label' => 'Mètode de pagament',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'notes_plantilla' => [
            'label' => 'Notes plantilla',
            'rules' => 'permit_empty',
        ],
    ];
}
