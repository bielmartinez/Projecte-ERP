<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlinearEsquemaMovimentsCategories extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
            CREATE TABLE IF NOT EXISTS categories_moviment (
                id SERIAL PRIMARY KEY,
                usuari_id INT NOT NULL,
                nom VARCHAR(100) NOT NULL,
                tipus VARCHAR(10) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                deleted_at TIMESTAMP NULL
            )
        SQL);

        $this->db->query(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'categories_moviment_usuari_id_fkey'
                ) THEN
                    ALTER TABLE categories_moviment
                    ADD CONSTRAINT categories_moviment_usuari_id_fkey
                    FOREIGN KEY (usuari_id)
                    REFERENCES usuaris(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;
                END IF;
            END
            $$
        SQL);

        $this->db->query(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'chk_categories_moviment_tipus'
                ) THEN
                    ALTER TABLE categories_moviment
                    ADD CONSTRAINT chk_categories_moviment_tipus
                    CHECK (tipus IN ('ingres', 'despesa'));
                END IF;
            END
            $$
        SQL);

        $this->db->query('CREATE INDEX IF NOT EXISTS idx_categories_moviment_usuari ON categories_moviment (usuari_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_categories_moviment_tipus ON categories_moviment (tipus)');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS uq_categories_moviment_usuari_tipus_nom ON categories_moviment (usuari_id, tipus, nom)');

        $this->db->query(<<<'SQL'
            ALTER TABLE moviments
            ADD COLUMN IF NOT EXISTS categoria_id INT NULL
        SQL);

        $this->db->query('CREATE INDEX IF NOT EXISTS idx_moviments_categoria ON moviments (categoria_id)');

        $this->db->query(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'fk_moviments_categoria_id'
                ) THEN
                    ALTER TABLE moviments
                    ADD CONSTRAINT fk_moviments_categoria_id
                    FOREIGN KEY (categoria_id)
                    REFERENCES categories_moviment(id)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT;
                END IF;
            END
            $$
        SQL);

        $this->db->query(<<<'SQL'
            INSERT INTO categories_moviment (usuari_id, nom, tipus, created_at, updated_at)
            SELECT
                u.id,
                c.nom,
                c.tipus,
                NOW(),
                NOW()
            FROM usuaris u
            CROSS JOIN (
                VALUES
                    ('Vendes', 'ingres'),
                    ('Serveis', 'ingres'),
                    ('Altres ingressos', 'ingres'),
                    ('Lloguer', 'despesa'),
                    ('Subministraments', 'despesa'),
                    ('Transport', 'despesa'),
                    ('Material oficina', 'despesa'),
                    ('Quotes i taxes', 'despesa'),
                    ('Altres despeses', 'despesa')
            ) AS c(nom, tipus)
            ON CONFLICT (usuari_id, tipus, nom) DO NOTHING
        SQL);
    }

    public function down(): void
    {
        // No destructive rollback because this migration aligns real environments.
    }
}
