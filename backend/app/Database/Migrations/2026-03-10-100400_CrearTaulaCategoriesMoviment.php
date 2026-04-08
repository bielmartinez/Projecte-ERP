<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaCategoriesMoviment extends Migration
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
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'tipus' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
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
        $this->forge->addKey('tipus');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('categories_moviment');

        $this->db->query("ALTER TABLE categories_moviment ADD CONSTRAINT chk_categories_moviment_tipus CHECK (tipus IN ('ingres', 'despesa'))");
        $this->db->query('CREATE UNIQUE INDEX uq_categories_moviment_usuari_tipus_nom ON categories_moviment (usuari_id, tipus, nom)');
    }

    public function down(): void
    {
        $this->forge->dropTable('categories_moviment', true);
    }
}
