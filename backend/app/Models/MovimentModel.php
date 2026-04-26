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
        'iva_percentatge',
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
        'iva_percentatge' => [
            'label' => 'IVA',
            'rules' => 'permit_empty|decimal|in_list[0,4,10,21]',
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
    /**
     * Calcula el resum d'ingressos i despeses d'un mes concret.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @param string $mesPeriode Valor d'entrada del mètode.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function resumMensual(int $usuariId, string $mesPeriode): array
    {
        $sql = "SELECT tipus, COALESCE(SUM(import), 0) AS total
                FROM {$this->table}
                WHERE usuari_id = ?
                  AND deleted_at IS NULL
                  AND TO_CHAR(data, 'YYYY-MM') = ?
                GROUP BY tipus";

        $files = $this->db->query($sql, [$usuariId, $mesPeriode])->getResultArray();

        $resum = [
            'ingressos' => 0.0,
            'despeses' => 0.0,
        ];

        foreach ($files as $fila) {
            $tipus = (string) ($fila['tipus'] ?? '');
            $total = round((float) ($fila['total'] ?? 0), 2);

            if ($tipus === 'ingres') {
                $resum['ingressos'] = $total;
            }

            if ($tipus === 'despesa') {
                $resum['despeses'] = $total;
            }
        }

        return $resum;
    }
    /**
     * Calcula l'evolució mensual d'ingressos i despeses.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @param int $mesos Nombre de mesos a analitzar.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function evolucioMensual(int $usuariId, int $mesos = 12): array
    {
        $mesos = max(1, $mesos);
        $dataInici = date('Y-m-01', strtotime("-{$mesos} months"));

        $mesosComplets = [];
        $cursor = new \DateTime($dataInici);
        $avui = new \DateTime(date('Y-m-01'));

        while ($cursor <= $avui) {
            $clauMes = $cursor->format('Y-m');
            $mesosComplets[$clauMes] = [
                'mes' => $clauMes,
                'ingressos' => 0.0,
                'despeses' => 0.0,
            ];

            $cursor->modify('+1 month');
        }

        $sql = "SELECT TO_CHAR(data, 'YYYY-MM') AS mes, tipus, COALESCE(SUM(import), 0) AS total
                FROM {$this->table}
                WHERE usuari_id = ?
                  AND deleted_at IS NULL
                  AND data >= ?
                GROUP BY TO_CHAR(data, 'YYYY-MM'), tipus
                ORDER BY mes ASC";

        $files = $this->db->query($sql, [$usuariId, $dataInici])->getResultArray();

        foreach ($files as $fila) {
            $mes = (string) ($fila['mes'] ?? '');
            $tipus = (string) ($fila['tipus'] ?? '');
            $total = round((float) ($fila['total'] ?? 0), 2);

            if (!isset($mesosComplets[$mes])) {
                continue;
            }

            if ($tipus === 'ingres') {
                $mesosComplets[$mes]['ingressos'] = $total;
            }

            if ($tipus === 'despesa') {
                $mesosComplets[$mes]['despeses'] = $total;
            }
        }

        return array_values($mesosComplets);
    }
    /**
     * Retorna la distribució de despeses per categories del mes actual.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function distribucioCategoriesMes(int $usuariId): array
    {
        $mesActual = date('Y-m');

        $sql = "SELECT categories_moviment.nom AS categoria, COALESCE(SUM(moviments.import), 0) AS total
                FROM {$this->table} AS moviments
                INNER JOIN categories_moviment ON categories_moviment.id = moviments.categoria_id
                WHERE moviments.usuari_id = ?
                  AND moviments.tipus = 'despesa'
                  AND moviments.deleted_at IS NULL
                  AND TO_CHAR(moviments.data, 'YYYY-MM') = ?
                GROUP BY categories_moviment.nom
                ORDER BY total DESC";

        $files = $this->db->query($sql, [$usuariId, $mesActual])->getResultArray();

        $resultat = [];
        foreach ($files as $fila) {
            $resultat[] = [
                'categoria' => (string) ($fila['categoria'] ?? ''),
                'total' => round((float) ($fila['total'] ?? 0), 2),            
];
        }

        return $resultat;
    }
    /**
     * Calcula el resum d'ingressos i despeses d'un període.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @param string $dataInici Data d'inici del període.
     * @param string $dataFi Data de fi del període.
     * @return array Conjunt de dades retornat pel mètode.
     */
    public function resumPerPeriode(int $usuariId, string $dataInici, string $dataFi): array
    {
        $sql = "SELECT tipus, COALESCE(SUM(import), 0) AS total
                FROM {$this->table}
                WHERE usuari_id = ?
                  AND deleted_at IS NULL
                  AND data >= ?
                  AND data <= ?
                GROUP BY tipus";

        $files = $this->db->query($sql, [$usuariId, $dataInici, $dataFi])->getResultArray();

        $resum = ['ingressos' => 0.0, 'despeses' => 0.0];

        foreach ($files as $fila) {
            if ($fila['tipus'] === 'ingres') {
                $resum['ingressos'] = round((float) $fila['total'], 2);
            }
            if ($fila['tipus'] === 'despesa') {
                $resum['despeses'] = round((float) $fila['total'], 2);
            }
        }

        return $resum;
    }
    /**
     * Calcula l'IVA suportat estimat dins un període.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @param string $dataInici Data d'inici del període.
     * @param string $dataFi Data de fi del període.
     * @return float Valor numèric calculat pel mètode.
     */
    public function ivaSuportatPeriode(int $usuariId, string $dataInici, string $dataFi): float
    {
        $sql = "SELECT COALESCE(SUM(
                CASE WHEN iva_percentatge > 0
                    THEN import - (import / (1 + iva_percentatge / 100))
                    ELSE 0
                END
            ), 0) AS iva_suportat
            FROM {$this->table}
            WHERE usuari_id = ?
              AND deleted_at IS NULL
              AND tipus = 'despesa'
              AND data >= ?
              AND data <= ?";

        $result = $this->db->query($sql, [$usuariId, $dataInici, $dataFi])->getRowArray();

        return round((float) ($result['iva_suportat'] ?? 0), 2);
    }
}
