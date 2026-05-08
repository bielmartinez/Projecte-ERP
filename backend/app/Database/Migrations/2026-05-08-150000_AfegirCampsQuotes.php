<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfegirCampsQuotes extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE quotes ADD COLUMN IF NOT EXISTS dia_pagament INTEGER NOT NULL DEFAULT 1");
        $this->db->query("ALTER TABLE quotes ADD COLUMN IF NOT EXISTS categoria_id INTEGER NULL");
        $this->db->query("ALTER TABLE quotes ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL");

        $this->db->query("ALTER TABLE pagaments_quotes ADD COLUMN IF NOT EXISTS periode_corresponent DATE NULL");
        $this->db->query("ALTER TABLE pagaments_quotes ADD COLUMN IF NOT EXISTS notes TEXT NULL");
        $this->db->query("ALTER TABLE pagaments_quotes ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE pagaments_quotes DROP COLUMN IF EXISTS created_at');
        $this->db->query('ALTER TABLE pagaments_quotes DROP COLUMN IF EXISTS notes');
        $this->db->query('ALTER TABLE pagaments_quotes DROP COLUMN IF EXISTS periode_corresponent');

        $this->db->query('ALTER TABLE quotes DROP COLUMN IF EXISTS deleted_at');
        $this->db->query('ALTER TABLE quotes DROP COLUMN IF EXISTS categoria_id');
        $this->db->query('ALTER TABLE quotes DROP COLUMN IF EXISTS dia_pagament');
    }
}