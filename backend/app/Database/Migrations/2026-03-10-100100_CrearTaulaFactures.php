<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaFactures extends Migration
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
            'client_id' => [
                'type' => 'INT',
            ],
            'serie' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'F',
            ],
            'numero_factura' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'data_emisio' => [
                'type'    => 'DATE',
            ],
            'data_venciment' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
            ],
            'estat' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'esborrany',
            ],
            'subtotal' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'iva_percentatge' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 21.00,
            ],
            'iva_import' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'irpf_percentatge' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0,
            ],
            'irpf_import' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'metode_pagament' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'data_cobrament' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
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
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('usuari_id');
        $this->forge->addKey('client_id');
        $this->forge->addKey('estat');
        $this->forge->addKey('data_emisio');
        $this->forge->addUniqueKey(['usuari_id', 'serie', 'numero_factura'], 'uq_factura_numero');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('client_id', 'clients', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('factures');

        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'factura_id' => [
                'type' => 'INT',
            ],
            'descripcio' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'quantitat' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,3',
                'default'    => 1,
            ],
            'preu_unitari' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'iva_percentatge' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 21.00,
            ],
            'descompte' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0,
            ],
            'total_linia' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'ordre' => [
                'type'    => 'INT',
                'default' => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('factura_id');
        $this->forge->addForeignKey('factura_id', 'factures', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('linies_factura');
    }

    public function down(): void
    {
        $this->forge->dropTable('linies_factura', true);
        $this->forge->dropTable('factures', true);
    }
}
