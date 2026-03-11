<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTaulaRegistresVerifactu extends Migration
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
            'usuari_id' => [
                'type' => 'INT',
            ],
            'tipus_registre' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'subsanacio' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'nif_emisor' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'numero_factura' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'data_emisio' => [
                'type' => 'DATE',
            ],
            'nom_rao_emisor' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'tipus_factura' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
            ],
            'quota_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'import_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'hash_registre' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'hash_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
            ],
            'data_hora_generacio' => [
                'type' => 'TIMESTAMP WITH TIME ZONE',
            ],
            'nif_emisor_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],
            'numero_factura_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
            ],
            'data_emisio_anterior' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
            ],
            'dades_factura' => [
                'type' => 'JSONB',
            ],
            'codi_qr' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('factura_id');
        $this->forge->addKey('usuari_id');
        $this->forge->addKey('hash_registre');
        $this->forge->addForeignKey('factura_id', 'factures', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('usuari_id', 'usuaris', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('registres_verifactu');

        $this->db->query("ALTER TABLE registres_verifactu ADD CONSTRAINT chk_verifactu_tipus CHECK (tipus_registre IN ('alta', 'anulacio'))");
    }

    public function down(): void
    {
        $this->forge->dropTable('registres_verifactu', true);
    }
}
