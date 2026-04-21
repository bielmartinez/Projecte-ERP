<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfegirIvaMoviments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('moviments', [
            'iva_percentatge' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 0,
                'after'      => 'import',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('moviments', 'iva_percentatge');
    }
}
