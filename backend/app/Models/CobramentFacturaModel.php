<?php

namespace App\Models;

use CodeIgniter\Model;

class CobramentFacturaModel extends Model
{
    protected $table      = 'cobraments_factura';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $allowedFields = [
        'factura_id',
        'moviment_id',
        'import',
        'data_cobrament',
        'metode_pagament',
        'notes',
    ];

    protected $validationRules = [
        'factura_id' => [
            'label' => 'Factura',
            'rules' => 'required|integer',
        ],
        'moviment_id' => [
            'label' => 'Moviment',
            'rules' => 'permit_empty|integer',
        ],
        'import' => [
            'label' => 'Import',
            'rules' => 'required|numeric|greater_than[0]',
        ],
        'data_cobrament' => [
            'label' => 'Data cobrament',
            'rules' => 'required|valid_date',
        ],
        'metode_pagament' => [
            'label' => 'Mètode de pagament',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'notes' => [
            'label' => 'Notes',
            'rules' => 'permit_empty',
        ],
    ];

    protected $validationMessages = [
        'factura_id' => [
            'required' => 'La factura és obligatòria.',
            'integer' => 'La factura ha de ser un número vàlid.',
        ],
        'moviment_id' => [
            'integer' => 'El moviment ha de ser un número vàlid.',
        ],
        'import' => [
            'required' => 'L\'import és obligatori.',
            'numeric' => 'L\'import ha de ser numèric.',
            'greater_than' => 'L\'import ha de ser major que 0.',
        ],
        'data_cobrament' => [
            'required' => 'La data de cobrament és obligatòria.',
            'valid_date' => 'La data de cobrament no és vàlida.',
        ],
        'metode_pagament' => [
            'max_length' => 'El mètode de pagament no pot excedir 50 caràcters.',
        ],
    ];
    /**
     * Obté cobraments factura segons els filtres indicats.
     *
     * @param int $facturaId Identificador de la factura.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function obtenirCobramentsFactura(int $facturaId): array
    {
        return $this->where('factura_id', $facturaId)
            ->orderBy('data_cobrament', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
    /**
     * Calcula l'import total cobrat d'una factura.
     *
     * @param int $facturaId Identificador de la factura.
     * @return float Valor numèric calculat pel mètode.
     */
    public function totalCobrat(int $facturaId): float
    {
        $resultat = $this->builder()
            ->selectSum('import', 'total_cobrat')
            ->where('factura_id', $facturaId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return round((float) ($resultat['total_cobrat'] ?? 0), 2);
    }
}
