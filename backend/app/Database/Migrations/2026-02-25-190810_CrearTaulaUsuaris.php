<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaUsuaris extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150, 
                'unique'     => true,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255, 
            ],
            'remember_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
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

            'nif' => [
                'type'       => 'VARCHAR',
                'constraint' => 20, 
                'null'       => true,
                'default'    => null,
            ],
            'telefon' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],

            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'user'],
                'default'    => 'user',
            ],

            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],

            'email_verified_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'last_login_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],

            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],

            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],

            'deleted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true); 
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('is_active');

        $this->forge->createTable('usuaris');
    }

    public function down()
    {
        $this->forge->dropTable('usuaris', true); 
    }
}