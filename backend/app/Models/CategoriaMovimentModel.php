<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaMovimentModel extends Model
{
    protected $table      = 'categories_moviment';
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
        'tipus',
    ];

    protected $validationRules = [
        'usuari_id' => [
            'label' => 'Usuari',
            'rules' => 'required|integer',
        ],
        'nom' => [
            'label' => 'Nom',
            'rules' => 'required|max_length[100]',
        ],
        'tipus' => [
            'label' => 'Tipus',
            'rules' => 'required|in_list[ingres,despesa]',
        ],
    ];

    protected $validationMessages = [
        'nom' => [
            'required' => 'El nom de la categoria és obligatori.',
        ],
        'tipus' => [
            'required' => 'El tipus és obligatori.',
            'in_list' => 'El tipus només pot ser ingres o despesa.',
        ],
    ];
}
