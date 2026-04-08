<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaCobraments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'factura_id' => [
                'type' => 'INT',
            ],
            'moviment_id' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],
            'import' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'data_cobrament' => [
                'type' => 'DATE',
            ],
            'metode_pagament' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
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
        $this->forge->addKey('factura_id');
        $this->forge->addKey('moviment_id');
        $this->forge->addForeignKey('factura_id', 'factures', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('moviment_id', 'moviments', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('cobraments_factura');

        $this->db->query('ALTER TABLE cobraments_factura ADD CONSTRAINT chk_cobraments_import CHECK (import > 0)');
    }

    public function down(): void
    {
        $this->forge->dropTable('cobraments_factura', true);
    }
}
