<?php

namespace App\Models;

use CodeIgniter\Model;

class FacturaModel extends Model
{
    protected $table      = 'factures';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $allowedFields = [
        'usuari_id',
        'client_id',
        'serie',
        'numero_factura',
        'data_emisio',
        'data_venciment',
        'estat',
        'subtotal',
        'iva_percentatge',
        'iva_import',
        'irpf_percentatge',
        'irpf_import',
        'total',
        'metode_pagament',
        'data_cobrament',
        'notes',
    ];

    protected $validationRules = [
        'usuari_id' => [
            'label' => 'Usuari',
            'rules' => 'required|integer',
        ],
        'client_id' => [
            'label' => 'Client',
            'rules' => 'required|integer',
        ],
        'serie' => [
            'label' => 'Sèrie',
            'rules' => 'required|max_length[10]',
        ],
        'numero_factura' => [
            'label' => 'Número de factura',
            'rules' => 'required|max_length[30]',
        ],
        'data_emisio' => [
            'label' => 'Data emissió',
            'rules' => 'required|valid_date',
        ],
        'data_venciment' => [
            'label' => 'Data venciment',
            'rules' => 'permit_empty|valid_date',
        ],
        'estat' => [
            'label' => 'Estat',
            'rules' => 'required|in_list[esborrany,emesa,cancel·lada,cobrada]',
        ],
        'subtotal' => [
            'label' => 'Subtotal',
            'rules' => 'required|numeric|greater_than_equal_to[0]',
        ],
        'iva_percentatge' => [
            'label' => 'IVA (%)',
            'rules' => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ],
        'iva_import' => [
            'label' => 'Import IVA',
            'rules' => 'required|numeric|greater_than_equal_to[0]',
        ],
        'irpf_percentatge' => [
            'label' => 'IRPF (%)',
            'rules' => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ],
        'irpf_import' => [
            'label' => 'Import IRPF',
            'rules' => 'required|numeric|greater_than_equal_to[0]',
        ],
        'total' => [
            'label' => 'Total',
            'rules' => 'required|numeric|greater_than_equal_to[0]',
        ],
        'metode_pagament' => [
            'label' => 'Mètode de pagament',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'data_cobrament' => [
            'label' => 'Data cobrament',
            'rules' => 'permit_empty|valid_date',
        ],
        'notes' => [
            'label' => 'Notes',
            'rules' => 'permit_empty',
        ],
    ];

    protected $validationMessages = [
        'usuari_id' => [
            'required'      => 'L\'usuari és obligatori.',
            'integer'       => 'L\'usuari ha de ser un número vàlid.',
        ],
        'client_id' => [
            'required'      => 'El client és obligatori.',
            'integer'       => 'El client ha de ser un número vàlid.',
        ],
        'serie' => [
            'required'      => 'La sèrie de factura és obligatòria.',
            'max_length'    => 'La sèrie no pot excedir 10 caràcters.',
        ],
        'numero_factura' => [
            'required'      => 'El número de factura és obligatori.',
            'is_unique'     => 'Ja existeix una factura amb aquest número.',
            'max_length'    => 'El número de factura no pot excedir 30 caràcters.',
        ],
        'data_emisio' => [
            'required'      => 'La data d\'emissió és obligatòria.',
            'valid_date'    => 'La data d\'emissió no és válida.',
        ],
        'data_venciment' => [
            'valid_date'    => 'La data de venciment no és válida.',
        ],
        'estat' => [
            'required'      => 'L\'estat de la factura és obligatori.',
            'in_list'       => 'L\'estat ha de ser: esborrany, emesa, cancel·lada o cobrada.',
        ],
        'subtotal' => [
            'required'      => 'El subtotal és obligatori.',
            'numeric'       => 'El subtotal ha de ser un número.',
            'greater_than_equal_to' => 'El subtotal no pot ser negatiu.',
        ],
        'iva_percentatge' => [
            'required'      => 'El percentatge d\'IVA és obligatori.',
            'numeric'       => 'El percentatge d\'IVA ha de ser un número.',
            'greater_than_equal_to' => 'El percentatge d\'IVA no pot ser negatiu.',
            'less_than_equal_to'    => 'El percentatge d\'IVA no pot excedir 100.',
        ],
        'iva_import' => [
            'required'      => 'L\'import d\'IVA és obligatori.',
            'numeric'       => 'L\'import d\'IVA ha de ser un número.',
            'greater_than_equal_to' => 'L\'import d\'IVA no pot ser negatiu.',
        ],
        'total' => [
            'required'      => 'El total és obligatori.',
            'numeric'       => 'El total ha de ser un número.',
            'greater_than_equal_to' => 'El total no pot ser negatiu.',
        ],
    ];

    public function generarNumeroFactura(int $usuariId, string $serie = 'F'): string
    {
        $ultimaFactura = $this->where('usuari_id', $usuariId)
                              ->where('serie', $serie)
                              ->orderBy('id', 'DESC')
                              ->first();

        $seguentNumero = 1;

        if ($ultimaFactura) {
            $parts = explode('-', (string) ($ultimaFactura['numero_factura'] ?? ''));
            if (count($parts) === 2 && ctype_digit($parts[1])) {
                $seguentNumero = (int) $parts[1] + 1;
            }
        }

        return $serie . '-' . str_pad((string) $seguentNumero, 3, '0', STR_PAD_LEFT);
    }

    public function calcularTotals(int $facturaId): array
    {
        $factura = $this->find($facturaId);
        if (!$factura) {
            return [
                'subtotal' => 0,
                'iva_import' => 0,
                'irpf_import' => 0,
                'total' => 0,
            ];
        }

        $ivaPercentatgeFactura = (float) ($factura['iva_percentatge'] ?? 21);
        $irpfPercentatge = (float) ($factura['irpf_percentatge'] ?? 0);

        $liniaModel = new LiniaFacturaModel();
        $subtotal = 0;
        $ivaImport = 0;

        $linies = $liniaModel->where('factura_id', $facturaId)->findAll();
        foreach ($linies as $linia) {
            $baseLinia = (float) ($linia['total_linia'] ?? 0);
            $ivaPercentatgeLinia = (float) ($linia['iva_percentatge'] ?? $ivaPercentatgeFactura);

            $subtotal += $baseLinia;
            $ivaImport += $baseLinia * ($ivaPercentatgeLinia / 100);
        }

        $irpfImport = $subtotal * ($irpfPercentatge / 100);
        $total = $subtotal + $ivaImport - $irpfImport;
        $ivaPercentatgeEfectiu = $subtotal > 0
            ? round(($ivaImport / $subtotal) * 100, 2)
            : $ivaPercentatgeFactura;

        return [
            'subtotal' => round($subtotal, 2),
            'iva_percentatge' => $ivaPercentatgeEfectiu,
            'iva_import' => round($ivaImport, 2),
            'irpf_import' => round($irpfImport, 2),
            'total' => round($total, 2),
        ];
    }

    public function actualitzarTotals(int $facturaId): bool
    {
        if (!$this->find($facturaId)) {
            return false;
        }

        $totals = $this->calcularTotals($facturaId);

        return $this->update($facturaId, $totals);
    }

    public function canviarEstat(int $facturaId, string $nouEstat): bool
    {
        $estatsValids = ['esborrany', 'emesa', 'cancel·lada', 'cobrada'];

        if (!in_array($nouEstat, $estatsValids, true)) {
            return false;
        }

        return $this->update($facturaId, ['estat' => $nouEstat]);
    }
}
