<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaPlantillesFactura extends Migration
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
            'iva_percentatge' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => 21.00,
            ],
            'irpf_percentatge' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => 0,
            ],
            'metode_pagament' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'notes_plantilla' => [
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('usuari_id');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('plantilles_factura');

        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'plantilla_id' => [
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
            'ordre' => [
                'type'    => 'INT',
                'default' => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('plantilla_id');
        $this->forge->addForeignKey('plantilla_id', 'plantilles_factura', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('linies_plantilla');
    }

    public function down(): void
    {
        $this->forge->dropTable('linies_plantilla', true);
        $this->forge->dropTable('plantilles_factura', true);
    }
}
