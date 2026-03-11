<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaMoviments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'usuari_id' => [
                'type' => 'INT',
            ],
            'factura_id' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],
            'tipus' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'descripcio' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ],
            'import' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'data' => [
                'type' => 'DATE',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('usuari_id');
        $this->forge->addKey('factura_id');
        $this->forge->addKey('tipus');
        $this->forge->addKey('data');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('factura_id', 'factures', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('moviments');

        $this->db->query("ALTER TABLE moviments ADD CONSTRAINT chk_moviments_tipus CHECK (tipus IN ('ingres', 'despesa'))");
    }

    public function down(): void
    {
        $this->forge->dropTable('moviments', true);
    }
}
