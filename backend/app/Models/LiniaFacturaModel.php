<?php

namespace App\Models;

use CodeIgniter\Model;

class LiniaFacturaModel extends Model
{
    protected $table      = 'linies_factura';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'factura_id',
        'descripcio',
        'quantitat',
        'preu_unitari',
        'iva_percentatge',
        'descompte',
        'total_linia',
        'ordre',
    ];

    protected $validationRules = [
        'factura_id' => [
            'label' => 'Factura',
            'rules' => 'required|integer',
        ],
        'descripcio' => [
            'label' => 'Descripció',
            'rules' => 'required|max_length[500]',
        ],
        'quantitat' => [
            'label' => 'Quantitat',
            'rules' => 'required|numeric|greater_than[0]',
        ],
        'preu_unitari' => [
            'label' => 'Preu unitari',
            'rules' => 'required|numeric|greater_than_equal_to[0]',
        ],
        'iva_percentatge' => [
            'label' => 'IVA (%)',
            'rules' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ],
        'descompte' => [
            'label' => 'Descompte (%)',
            'rules' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ],
        'total_linia' => [
            'label' => 'Total línia',
            'rules' => 'permit_empty|numeric|greater_than_equal_to[0]',
        ],
        'ordre' => [
            'label' => 'Ordre',
            'rules' => 'permit_empty|integer|greater_than_equal_to[0]',
        ],
    ];

    protected $validationMessages = [
        'factura_id' => [
            'required'      => 'La factura és obligatòria.',
            'integer'       => 'La factura ha de ser un número vàlid.',
        ],
        'descripcio' => [
            'required'      => 'La descripció és obligatòria.',
            'max_length'    => 'La descripció no pot excedir 500 caràcters.',
        ],
        'quantitat' => [
            'required'      => 'La quantitat és obligatòria.',
            'numeric'       => 'La quantitat ha de ser un número.',
            'greater_than'  => 'La quantitat ha de ser major que 0.',
        ],
        'preu_unitari' => [
            'required'      => 'El preu unitari és obligatori.',
            'numeric'       => 'El preu unitari ha de ser un número.',
            'greater_than_equal_to' => 'El preu unitari no pot ser negatiu.',
        ],
        'iva_percentatge' => [
            'required'      => 'El percentatge d\'IVA és obligatori.',
            'numeric'       => 'El percentatge d\'IVA ha de ser un número.',
        ],
        'descompte' => [
            'required'      => 'El descompte és obligatori.',
            'numeric'       => 'El descompte ha de ser un número.',
            'greater_than_equal_to' => 'El descompte no pot ser negatiu.',
            'less_than_equal_to'    => 'El descompte no pot excedir 100.',
        ],
        'total_linia' => [
            'required'      => 'El total de la línea és obligatori.',
            'numeric'       => 'El total de la línea ha de ser un número.',
        ],
    ];

    public static function calcularTotalLinia(
        float $quantitat,
        float $preu_unitari,
        float $iva_percentatge = 21.00,
        float $descompte = 0
    ): float {
        $base = $quantitat * $preu_unitari;
        $baseAmbDescompte = $base * (1 - ($descompte / 100));

        return round($baseAmbDescompte, 2);
    }

    public function crearLinia(array $data)
    {
        if (isset($data['quantitat'], $data['preu_unitari'])) {
            $iva = $data['iva_percentatge'] ?? 21.00;
            $descompte = $data['descompte'] ?? 0;

            $data['total_linia'] = self::calcularTotalLinia(
                (float) $data['quantitat'],
                (float) $data['preu_unitari'],
                (float) $iva,
                (float) $descompte
            );
        }

        return $this->insert($data, true);
    }

    public function actualitzarLinia(int $liniaId, array $data): bool
    {
        if (isset($data['quantitat']) || isset($data['preu_unitari']) || isset($data['descompte']) || isset($data['iva_percentatge'])) {
            $liniaActual = $this->find($liniaId);

            if (!$liniaActual) {
                return false;
            }

            $quantitat = $data['quantitat'] ?? $liniaActual['quantitat'];
            $preu = $data['preu_unitari'] ?? $liniaActual['preu_unitari'];
            $iva = $data['iva_percentatge'] ?? $liniaActual['iva_percentatge'];
            $descompte = $data['descompte'] ?? $liniaActual['descompte'];

            $data['total_linia'] = self::calcularTotalLinia(
                (float) $quantitat,
                (float) $preu,
                (float) $iva,
                (float) $descompte
            );
        }

        return $this->update($liniaId, $data);
    }

    public function obtenirLiniesFactura(int $facturaId): array
    {
        return $this->where('factura_id', $facturaId)
                    ->orderBy('ordre', 'ASC')
                    ->findAll();
    }

    public function eliminarLiniesFactura(int $facturaId): bool
    {
        return (bool) $this->where('factura_id', $facturaId)->delete();
    }
}
