<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuariModel extends Model
{
    protected $table      = 'usuaris';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $allowedFields = [
        'email',
        'password_hash',
        'remember_token',
        'nom',
        'cognoms',
        'nif',
        'telefon',
        'nom_empresa',
        'adreca',
        'codi_postal',
        'poblacio',
        'provincia',
        'pais',
        'compte_bancari',
        'logo',
        'role',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $validationRules = [
        'email' => [
            'label' => 'Correu electrònic',
            'rules' => 'required|valid_email|max_length[150]|is_unique[usuaris.email,id,{id}]',
        ],
        'password_hash' => [
            'label' => 'Contrasenya',
            'rules' => 'required|min_length[8]',
        ],
        'nom' => [
            'label' => 'Nom',
            'rules' => 'required|max_length[100]',
        ],
        'cognoms' => [
            'label' => 'Cognoms',
            'rules' => 'permit_empty|max_length[150]',
        ],
        'nif' => [
            'label' => 'NIF',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'telefon' => [
            'label' => 'Telèfon',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'remember_token' => [
            'label' => 'Token recordatori',
            'rules' => 'permit_empty|max_length[255]',
        ],
        'nom_empresa' => [
            'label' => 'Nom empresa',
            'rules' => 'permit_empty|max_length[200]',
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
        'compte_bancari' => [
            'label' => 'Compte bancari',
            'rules' => 'permit_empty|max_length[34]',
        ],
        'logo' => [
            'label' => 'Logo',
            'rules' => 'permit_empty|max_length[500]',
        ],
        'role' => [
            'label' => 'Rol',
            'rules' => 'required|max_length[20]',
        ],
        'is_active' => [
            'label' => 'Actiu',
            'rules' => 'required',
        ],
    ];

    protected $validationMessages = [
        'email' => [
            'required'    => 'El correu electrònic és obligatori.',
            'valid_email' => 'El format del correu no és vàlid.',
            'is_unique'   => 'Aquest correu ja està registrat.',
        ],
        'password_hash' => [
            'required'   => 'La contrasenya és obligatòria.',
            'min_length' => 'La contrasenya ha de tenir mínim 8 caràcters.',
        ],
        'nom' => [
            'required' => 'El nom és obligatori.',
        ],
        'codi_postal' => [
            'max_length' => 'El codi postal no pot excedir 10 caràcters.',
        ],
        'compte_bancari' => [
            'max_length' => 'El compte bancari no pot excedir 34 caràcters.',
        ],
        'is_active' => [
            'required' => 'L\'estat actiu és obligatori.',
        ],
    ];

    protected $afterFind = ['eliminarPasswordHash'];

    protected function eliminarPasswordHash(array $data): array
    {
        if (isset($data['data']['password_hash'])) {
            unset($data['data']['password_hash']);
        }

        if (is_array($data['data'])) {
            foreach ($data['data'] as &$usuari) {
                if (isset($usuari['password_hash'])) {
                    unset($usuari['password_hash']);
                }
            }
        }

        return $data;
    }

    public function findPerLogin(string $email): array|null
    {
        return $this->builder()
                    ->where('email', $email)
                    ->where('is_active', true)
                    ->where('deleted_at IS NULL')
                    ->get()
                    ->getRowArray();
    }

    public function findPerEmail(string $email): array|null
    {
        return $this->where('email', $email)
                    ->where('is_active', true)
                    ->first();
    }
}