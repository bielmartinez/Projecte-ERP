<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientModel extends Model
{
    protected $table      = 'clients';
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

    protected $validationRules = [
        'nom' => [
            'label' => 'Nom',
            'rules' => 'required|max_length[100]',
        ],
        'cognoms' => [
            'label' => 'Cognoms',
            'rules' => 'permit_empty|max_length[150]',
        ],
        'nom_empresa' => [
            'label' => 'Nom empresa',
            'rules' => 'permit_empty|max_length[200]',
        ],
        'nif' => [
            'label' => 'NIF',
            'rules' => 'required|max_length[20]',
        ],
        'email' => [
            'label' => 'Correu electrònic',
            'rules' => 'required|valid_email|max_length[150]',
        ],
        'telefon' => [
            'label' => 'Telèfon',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'adreca' => [
            'label' => 'Adreça',
            'rules' => 'permit_empty|max_length[255]',
        ],
        'codi_postal' => [
            'label' => 'Codi postal',
            'rules' => 'permit_empty|max_length[10]',
        ],
        'poblacio' => [
            'label' => 'Població',
            'rules' => 'permit_empty|max_length[100]',
        ],
        'provincia' => [
            'label' => 'Província',
            'rules' => 'permit_empty|max_length[100]',
        ],
        'pais' => [
            'label' => 'País',
            'rules' => 'permit_empty|max_length[100]',
        ],
        'notes' => [
            'label' => 'Notes',
            'rules' => 'permit_empty',
        ],
    ];
}
