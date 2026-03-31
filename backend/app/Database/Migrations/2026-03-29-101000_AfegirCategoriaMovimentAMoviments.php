<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfegirCategoriaMovimentAMoviments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('moviments', [
            'categoria_id' => [
                'type' => 'INT',
                'null' => true,
                'default' => null,
                'after' => 'factura_id',
            ],
        ]);

        $this->db->query('CREATE INDEX idx_moviments_categoria ON moviments (categoria_id)');
        $this->db->query('ALTER TABLE moviments ADD CONSTRAINT fk_moviments_categoria_id FOREIGN KEY (categoria_id) REFERENCES categories_moviment(id) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE moviments DROP CONSTRAINT IF EXISTS fk_moviments_categoria_id');
        $this->db->query('DROP INDEX IF EXISTS idx_moviments_categoria');
        $this->forge->dropColumn('moviments', 'categoria_id');
    }
}
