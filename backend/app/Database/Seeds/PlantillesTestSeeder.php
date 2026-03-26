<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PlantillesTestSeeder extends Seeder
{
    public function run()
    {
        echo "\n═══════════════════════════════════════════════════════\n";
        echo "  PLANTILLES TEST SEEDER\n";
        echo "═══════════════════════════════════════════════════════\n\n";

        $db = $this->db;

        try {
            // 1. Comprovar o crear usuari de test
            echo "1️⃣  Setup d'usuari de test...\n";

            $usuari = $db->query("
                SELECT id FROM usuaris 
                WHERE email = 'test@example.com'
                LIMIT 1
            ")->getRow();

            if (!$usuari) {
                echo "   Creant usuari test@example.com...\n";
                $db->table('usuaris')->insert([
                    'email' => 'test@example.com',
                    'password_hash' => password_hash('test1234', PASSWORD_BCRYPT),
                    'nom' => 'Test',
                    'cognoms' => 'User',
                    'nif' => '12345678A',
                    'nom_empresa' => 'Test Company',
                    'telefon' => '600000000',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $usuariId = $db->insertID();
                echo "   ✓ Usuari creat amb ID: $usuariId\n";
            } else {
                $usuariId = $usuari->id;
                echo "   ✓ Usuari existent amb ID: $usuariId\n";
            }

            // 2. Comprovar o crear client de test
            echo "\n2️⃣  Setup de client de test...\n";

            $client = $db->query("
                SELECT id FROM clients 
                WHERE usuari_id = ? AND email = 'client@test.com'
                LIMIT 1
            ", [$usuariId])->getRow();

            if (!$client) {
                echo "   Creant client client@test.com...\n";
                $db->table('clients')->insert([
                    'usuari_id' => $usuariId,
                    'nom' => 'Client Test',
                    'email' => 'client@test.com',
                    'telefon' => '600000001',
                    'nif' => '87654321B',
                    'adreca' => 'C/ Client, 1',
                    'codi_postal' => '08001',
                    'poblacio' => 'Barcelona',
                    'provincia' => 'Barcelona',
                    'pais' => 'ES',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $clientId = $db->insertID();
                echo "   ✓ Client creat amb ID: $clientId\n";
            } else {
                $clientId = $client->id;
                echo "   ✓ Client existeix amb ID: $clientId\n";
            }

            // 3. Assegurar que existeixen les taules de plantilles
            echo "\n3️⃣  Verificant taules de plantilles...\n";

            $tables = $db->query("
                SELECT table_name FROM information_schema.tables 
                WHERE table_schema = 'public'
            ")->getResultArray();
            
            $tableNames = array_column($tables, 'table_name');
            
            $plantillesExisteix = in_array('plantilles_factura', $tableNames);
            $liniesExisteix = in_array('linies_plantilla', $tableNames);
            
            if (!$plantillesExisteix) {
                echo "   Creant taula plantilles_factura...\n";
                $db->query("
                    CREATE TABLE plantilles_factura (
                        id SERIAL PRIMARY KEY,
                        usuari_id INT NOT NULL,
                        nom VARCHAR(100) NOT NULL,
                        descripcio TEXT,
                        iva_percentatge DECIMAL(5,2) DEFAULT 21.00,
                        irpf_percentatge DECIMAL(5,2) DEFAULT 0,
                        metode_pagament VARCHAR(50),
                        notes_plantilla TEXT,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL,
                        deleted_at TIMESTAMP NULL,
                        CONSTRAINT fk_plantilles_usuari FOREIGN KEY (usuari_id) REFERENCES usuaris(id) ON DELETE CASCADE
                    )
                ");
                echo "   ✓ Taula plantilles_factura creada\n";
            } else {
                echo "   ✓ Taula plantilles_factura ja existeix\n";
            }
            
            if (!$liniesExisteix) {
                echo "   Creant taula linies_plantilla...\n";
                $db->query("
                    CREATE TABLE linies_plantilla (
                        id SERIAL PRIMARY KEY,
                        plantilla_id INT NOT NULL,
                        descripcio VARCHAR(500) NOT NULL,
                        quantitat DECIMAL(10,3) DEFAULT 1,
                        preu_unitari DECIMAL(12,2) DEFAULT 0,
                        iva_percentatge DECIMAL(5,2) DEFAULT 21.00,
                        descompte DECIMAL(5,2) DEFAULT 0,
                        ordre INT DEFAULT 0,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL,
                        deleted_at TIMESTAMP NULL,
                        CONSTRAINT fk_linies_plantilla FOREIGN KEY (plantilla_id) REFERENCES plantilles_factura(id) ON DELETE CASCADE
                    )
                ");
                echo "   ✓ Taula linies_plantilla creada\n";
            } else {
                echo "   ✓ Taula linies_plantilla ja existeix\n";
            }

            // 4. Crear plantilla de test
            echo "\n4️⃣  Setup de plantilla de test...\n";

            $plantilla = $db->query("
                SELECT id FROM plantilles_factura 
                WHERE usuari_id = ? AND nom = 'Plantilla Test'
                LIMIT 1
            ", [$usuariId])->getRow();

            if (!$plantilla) {
                echo "   Creant plantilla 'Plantilla Test'...\n";
                $db->table('plantilles_factura')->insert([
                    'usuari_id' => $usuariId,
                    'nom' => 'Plantilla Test',
                    'descripcio' => 'Plantilla de prova per a testing',
                    'iva_percentatge' => 21.00,
                    'irpf_percentatge' => 0,
                    'metode_pagament' => 'Transferència',
                    'notes_plantilla' => 'Aquestes són les notes de la plantilla de prova',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $plantillaId = $db->insertID();
                echo "   ✓ Plantilla creada amb ID: $plantillaId\n";
            } else {
                $plantillaId = $plantilla->id;
                echo "   ✓ Plantilla existeix amb ID: $plantillaId\n";
            }

            // 5. Afegir línies a la plantilla
            echo "\n5️⃣  Setup de línies de plantilla...\n";

            $countLinies = $db->query("
                SELECT COUNT(*) as total FROM linies_plantilla 
                WHERE plantilla_id = ?
            ", [$plantillaId])->getRow()->total;

            if ($countLinies == 0) {
                echo "   Afegint 3 línies de prova...\n";
                $linies = [
                    [
                        'plantilla_id' => $plantillaId,
                        'descripcio' => 'Desenvolupament de software (8h)',
                        'quantitat' => 8,
                        'preu_unitari' => 50.00,
                        'iva_percentatge' => 21.00,
                        'descompte' => 0,
                        'ordre' => 0,
                    ],
                    [
                        'plantilla_id' => $plantillaId,
                        'descripcio' => 'Consultoria (4h)',
                        'quantitat' => 4,
                        'preu_unitari' => 75.00,
                        'iva_percentatge' => 21.00,
                        'descompte' => 0,
                        'ordre' => 1,
                    ],
                    [
                        'plantilla_id' => $plantillaId,
                        'descripcio' => 'Manteniment mensual',
                        'quantitat' => 1,
                        'preu_unitari' => 100.00,
                        'iva_percentatge' => 21.00,
                        'descompte' => 5.00,
                        'ordre' => 2,
                    ],
                ];
                $db->table('linies_plantilla')->insertBatch($linies);
                echo "   ✓ 3 línies afegides\n";
            } else {
                echo "   ✓ La plantilla ja té $countLinies línies\n";
            }

            // 5. Mostrar resum
            echo "\n6️⃣  DADES DE PROVA CREADES:\n\n";

            $p = $db->query("SELECT * FROM plantilles_factura WHERE id = ?", [$plantillaId])->getRow();
            $l = $db->query("SELECT * FROM linies_plantilla WHERE plantilla_id = ? ORDER BY ordre", [$plantillaId])->getResultArray();

            echo "   🔑 CREDENCIALS DE LOGIN:\n";
            echo "      Email: test@example.com\n";
            echo "      Password: test1234\n";

            echo "\n   📋 DADES DE PROVA:\n";
            echo "      ID Usuari: $usuariId\n";
            echo "      ID Client: $clientId\n";
            echo "      ID Plantilla: $plantillaId\n";
            echo "      Nom: {$p->nom}\n";

            echo "\n   📝 Línies de la plantilla:\n";
            foreach ($l as $linia) {
                $subtotal = $linia['quantitat'] * $linia['preu_unitari'];
                echo "      {$linia['descripcio']}: {$linia['quantitat']} u × €{$linia['preu_unitari']} = €{$subtotal}\n";
            }

            echo "\n═══════════════════════════════════════════════════════\n";
            echo "  ✓ SETUP COMPLETAT!\n";
            echo "═══════════════════════════════════════════════════════\n\n";

        } catch (\Exception $e) {
            echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
}
