<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaTokensAcces extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
            ],
            'usuari_id' => [
                'type'       => 'INT',
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'expires_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'last_used_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('usuari_id');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('tokens_acces');
    }

    public function down(): void
    {
        $this->forge->dropTable('tokens_acces', true);
    }
}
