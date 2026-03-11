<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaQuotes extends Migration
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
            'nom' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'descripcio' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],
            'import' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'periodicitat' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
            ],
            'data_inici' => [
                'type' => 'DATE',
            ],
            'data_fi' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
            ],
            'activa' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('usuari_id');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('quotes');

        $this->db->query("ALTER TABLE quotes ADD CONSTRAINT chk_quotes_periodicitat CHECK (periodicitat IN ('mensual', 'trimestral', 'anual'))");

        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'quota_id' => [
                'type' => 'INT',
            ],
            'moviment_id' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],
            'data_pagament' => [
                'type' => 'DATE',
            ],
            'import' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'estat' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'pendent',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('quota_id');
        $this->forge->addKey('moviment_id');
        $this->forge->addKey('estat');
        $this->forge->addForeignKey('quota_id', 'quotes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('moviment_id', 'moviments', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('pagaments_quotes');

        $this->db->query("ALTER TABLE pagaments_quotes ADD CONSTRAINT chk_pagaments_quotes_estat CHECK (estat IN ('pendent', 'pagat'))");
    }

    public function down(): void
    {
        $this->forge->dropTable('pagaments_quotes', true);
        $this->forge->dropTable('quotes', true);
    }
}
