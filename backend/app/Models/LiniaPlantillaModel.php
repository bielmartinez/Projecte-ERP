<?php

namespace App\Models;

use CodeIgniter\Model;

class LiniaPlantillaModel extends Model
{
    protected $table      = 'linies_plantilla';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'plantilla_id',
        'descripcio',
        'quantitat',
        'preu_unitari',
        'iva_percentatge',
        'descompte',
        'ordre',
    ];

    protected $validationRules = [
        'plantilla_id' => [
            'label' => 'Plantilla',
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
        'ordre' => [
            'label' => 'Ordre',
            'rules' => 'permit_empty|integer|greater_than_equal_to[0]',
        ],
    ];
    /**
     * Obté linies plantilla segons els filtres indicats.
     *
     * @param int $plantillaId Identificador de l'entitat relacionada.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function obtenirLiniesPlantilla(int $plantillaId): array
    {
        return $this->where('plantilla_id', $plantillaId)
            ->orderBy('ordre', 'ASC')
            ->findAll();
    }
    /**
     * Elimina linies plantilla.
     *
     * @param int $plantillaId Identificador de l'entitat relacionada.
     * @return bool Indica si l'operació s'ha completat correctament.
     */
    public function eliminarLiniesPlantilla(int $plantillaId): bool
    {
        return (bool) $this->where('plantilla_id', $plantillaId)->delete();
    }
}
