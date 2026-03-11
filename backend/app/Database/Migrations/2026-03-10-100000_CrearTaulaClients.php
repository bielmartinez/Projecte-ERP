<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaClients extends Migration
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
            'cognoms' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
            ],
            'nom_empresa' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'default'    => null,
            ],
            'nif' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
            ],
            'telefon' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],
            'adreca' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'codi_postal' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'default'    => null,
            ],
            'poblacio' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'provincia' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'pais' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => 'Espanya',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('nif');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('clients');
    }

    public function down(): void
    {
        $this->forge->dropTable('clients', true);
    }
}
