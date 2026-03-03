<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaTokensAcces extends Migration
{
    public function up(): void
    {
        $this->forge->addField([

            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'usuari_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'unique'     => true,
            ],

            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],

            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');

        $this->forge->addUniqueKey('token');

        $this->forge->addKey('usuari_id');

        $this->forge->createTable('tokens_acces');

        $this->db->query('
            ALTER TABLE tokens_acces
            ADD CONSTRAINT fk_tokens_usuari
            FOREIGN KEY (usuari_id)
            REFERENCES usuaris(id)
            ON DELETE CASCADE
        ');
    }

    public function down(): void
    {
        $this->forge->dropTable('tokens_acces', true);
    }
}
