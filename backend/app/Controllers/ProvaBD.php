<?php
namespace App\Controllers;

use CodeIgniter\Controller;

class ProvaBD extends Controller
{
    public function index()
    {
        try {
            $db = \Config\Database::connect();
            
            $config = config('Database');
            
            echo "Connectat a PostgreSQL correctament!<br>";
            echo "Base de dades: " . $config->default['database'] . "<br>";
            echo "Host: " . $config->default['hostname'] . "<br>";
            echo "Driver: " . $config->default['DBDriver'] . "<br>";
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}