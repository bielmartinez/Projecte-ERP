<?php

namespace App\Models;

use CodeIgniter\Model;

class RegistreVerifactuModel extends Model
{
    protected $table      = 'registres_verifactu';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $allowedFields = [
        'factura_id',
        'usuari_id',
        'tipus_registre',
        'subsanacio',
        'nif_emisor',
        'numero_factura',
        'data_emisio',
        'nom_rao_emisor',
        'tipus_factura',
        'quota_total',
        'import_total',
        'hash_registre',
        'hash_anterior',
        'data_hora_generacio',
        'nif_emisor_anterior',
        'numero_factura_anterior',
        'data_emisio_anterior',
        'dades_factura',
        'codi_qr',
    ];

    protected $validationRules = [
        'factura_id' => [
            'label' => 'Factura',
            'rules' => 'required|integer',
        ],
        'usuari_id' => [
            'label' => 'Usuari',
            'rules' => 'required|integer',
        ],
        'tipus_registre' => [
            'label' => 'Tipus de registre',
            'rules' => 'required|in_list[alta,anulacio]|max_length[20]',
        ],
        'subsanacio' => [
            'label' => 'Subsanació',
            'rules' => 'permit_empty',
        ],
        'nif_emisor' => [
            'label' => 'NIF emissor',
            'rules' => 'required|max_length[20]',
        ],
        'numero_factura' => [
            'label' => 'Número de factura',
            'rules' => 'required|max_length[60]',
        ],
        'data_emisio' => [
            'label' => 'Data emissió',
            'rules' => 'required|valid_date',
        ],
        'nom_rao_emisor' => [
            'label' => 'Nom o raó emissor',
            'rules' => 'required|max_length[255]',
        ],
        'tipus_factura' => [
            'label' => 'Tipus de factura',
            'rules' => 'required|in_list[F1,F2,R1,R2,R3,R4,R5]|max_length[5]',
        ],
        'quota_total' => [
            'label' => 'Quota total',
            'rules' => 'required|numeric',
        ],
        'import_total' => [
            'label' => 'Import total',
            'rules' => 'required|numeric',
        ],
        'hash_registre' => [
            'label' => 'Hash del registre',
            'rules' => 'required|exact_length[64]|regex_match[/^[A-F0-9]{64}$/]',
        ],
        'hash_anterior' => [
            'label' => 'Hash anterior',
            'rules' => 'permit_empty|exact_length[64]|regex_match[/^[A-F0-9]{64}$/]',
        ],
        'data_hora_generacio' => [
            'label' => 'Data i hora de generació',
            'rules' => 'required|max_length[35]',
        ],
        'nif_emisor_anterior' => [
            'label' => 'NIF emissor anterior',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'numero_factura_anterior' => [
            'label' => 'Número factura anterior',
            'rules' => 'permit_empty|max_length[60]',
        ],
        'data_emisio_anterior' => [
            'label' => 'Data emissió anterior',
            'rules' => 'permit_empty|valid_date',
        ],
        'dades_factura' => [
            'label' => 'Dades de factura',
            'rules' => 'required',
        ],
    ];

    protected $validationMessages = [
        'factura_id' => [
            'required' => 'La factura és obligatòria.',
            'integer' => 'La factura ha de ser un identificador numèric.',
        ],
        'usuari_id' => [
            'required' => 'L\'usuari és obligatori.',
            'integer' => 'L\'usuari ha de ser un identificador numèric.',
        ],
        'tipus_registre' => [
            'required' => 'El tipus de registre és obligatori.',
            'in_list' => 'El tipus de registre ha de ser alta o anulacio.',
            'max_length' => 'El tipus de registre és massa llarg.',
        ],
        'subsanacio' => [
            'required' => 'Cal indicar si és una subsanació.',
            'in_list' => 'El camp subsanació ha de ser 0 o 1.',
        ],
        'nif_emisor' => [
            'required' => 'El NIF de l\'emissor és obligatori.',
            'max_length' => 'El NIF de l\'emissor no pot superar 20 caràcters.',
        ],
        'numero_factura' => [
            'required' => 'El número de factura és obligatori.',
            'max_length' => 'El número de factura no pot superar 60 caràcters.',
        ],
        'data_emisio' => [
            'required' => 'La data d\'emissió és obligatòria.',
            'valid_date' => 'La data d\'emissió no és vàlida.',
        ],
        'nom_rao_emisor' => [
            'required' => 'El nom o raó social de l\'emissor és obligatori.',
            'max_length' => 'El nom o raó social no pot superar 255 caràcters.',
        ],
        'tipus_factura' => [
            'required' => 'El tipus de factura és obligatori.',
            'in_list' => 'El tipus de factura ha de ser F1, F2 o R1-R5.',
            'max_length' => 'El tipus de factura no pot superar 5 caràcters.',
        ],
        'quota_total' => [
            'required' => 'La quota total és obligatòria.',
            'numeric' => 'La quota total ha de ser numèrica.',
        ],
        'import_total' => [
            'required' => 'L\'import total és obligatori.',
            'numeric' => 'L\'import total ha de ser numèric.',
        ],
        'hash_registre' => [
            'required' => 'El hash del registre és obligatori.',
            'exact_length' => 'El hash del registre ha de tenir exactament 64 caràcters.',
            'regex_match' => 'El hash del registre ha de ser hexadecimal en majúscules.',
        ],
        'hash_anterior' => [
            'exact_length' => 'El hash anterior ha de tenir exactament 64 caràcters.',
            'regex_match' => 'El hash anterior ha de ser hexadecimal en majúscules.',
        ],
        'data_hora_generacio' => [
            'required' => 'La data i hora de generació és obligatòria.',
            'max_length' => 'La data i hora de generació és massa llarga.',
        ],
        'nif_emisor_anterior' => [
            'max_length' => 'El NIF emissor anterior no pot superar 20 caràcters.',
        ],
        'numero_factura_anterior' => [
            'max_length' => 'El número de factura anterior no pot superar 60 caràcters.',
        ],
        'data_emisio_anterior' => [
            'valid_date' => 'La data d\'emissió anterior no és vàlida.',
        ],
        'dades_factura' => [
            'required' => 'Les dades de factura són obligatòries.',
        ],
    ];
    /**
     * Obté ultim registre segons els filtres indicats.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @return ?array Registre trobat o null si no existeix.
     */
    public function obtenirUltimRegistre(int $usuariId): ?array
    {
        $registre = $this->where('usuari_id', $usuariId)
            ->orderBy('id', 'DESC')
            ->first();

        return is_array($registre) ? $registre : null;
    }
    /**
     * Obté registres per usuari segons els filtres indicats.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @param int $page Pàgina de resultats.
     * @param int $limit Nombre màxim d'elements per pàgina.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function obtenirRegistresPerUsuari(int $usuariId, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $total = (int) $this->builder()
            ->where('usuari_id', $usuariId)
            ->countAllResults();

        $data = $this->builder()
            ->select('registres_verifactu.*, factures.estat AS estat_factura_actual')
            ->join('factures', 'factures.id = registres_verifactu.factura_id', 'left')
            ->where('registres_verifactu.usuari_id', $usuariId)
            ->orderBy('registres_verifactu.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ];
    }
    /**
     * Obté registres per factura segons els filtres indicats.
     *
     * @param int $facturaId Identificador de la factura.
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function obtenirRegistresPerFactura(int $facturaId, int $usuariId): array
    {
        return $this->where('factura_id', $facturaId)
            ->where('usuari_id', $usuariId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
    /**
     * Obté tots registres segons els filtres indicats.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function obtenirTotsRegistres(int $usuariId): array
    {
        return $this->where('usuari_id', $usuariId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
    /**
     * Actualitza les dades d'un registre verifactu existent.
     *
     * @param mixed $id Identificador del recurs.
     * @param mixed $row Dades a persistir en l'actualització.
     * @return bool Indica si l'operació s'ha completat correctament.
     */
    public function update($id = null, $row = null): bool
    {
        throw new \RuntimeException('Els registres Verifactu són immutables i no es poden modificar.');
    }
    /**
     * Bloqueja les actualitzacions en lot perquè els registres són immutables.
     *
     * @param null|array $set Conjunt de dades per a l'actualització en lot.
     * @param null|string $index Camp clau utilitzat per actualitzar en lot.
     * @param int $batchSize Mida del lot d'actualització.
     * @param bool $returnSQL Indica si s'ha de retornar l'SQL generat.
     * @return mixed Resultat de l'operació executada pel mètode.
     */
    public function updateBatch(?array $set = null, ?string $index = null, int $batchSize = 100, bool $returnSQL = false)
    {
        throw new \RuntimeException('Els registres Verifactu són immutables i no es poden modificar.');
    }
    /**
     * Elimina un registre verifactu (soft delete).
     *
     * @param mixed $id Identificador del recurs.
     * @param bool $purge Indica si s'ha d'eliminar definitivament.
     * @return mixed Resultat de l'operació executada pel mètode.
     */
    public function delete($id = null, bool $purge = false)
    {
        throw new \RuntimeException('Els registres Verifactu són immutables i no es poden eliminar.');
    }
}
