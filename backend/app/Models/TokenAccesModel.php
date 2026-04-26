<?php

namespace App\Models;

use CodeIgniter\Model;

class TokenAccesModel extends Model
{
    protected $table      = 'tokens_acces';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = false;

    protected $allowedFields = [
        'usuari_id',
        'token',
        'expires_at',
        'last_used_at',
    ];
    /**
     * Genera token.
     *
     * @return string Valor textual obtingut o generat pel mètode.
     */
    public function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    /**
     * Crea i persisteix un token d'accés per a l'usuari.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @param bool $recordar Indica si la sessió ha de ser persistent.
     * @return string Valor textual obtingut o generat pel mètode.
     */
    public function crearToken(int $usuariId, bool $recordar = false): string
    {
        $token = $this->generarToken();
        $expiresAt = $recordar ? null : date('Y-m-d H:i:s', strtotime('+8 hours'));
        $createdAt = date('Y-m-d H:i:s');

        $this->db->query(
            'INSERT INTO tokens_acces (usuari_id, token, expires_at, created_at) VALUES (?, ?, ?, ?)',
            [$usuariId, $token, $expiresAt, $createdAt]
        );

        return $token;
    }
    /**
     * Cerca i retorna registres segons els criteris indicats.
     *
     * @param string $token Token d'accés de la sessió.
     * @return array|null Registre trobat o null si no existeix.
     */
    public function findTokenValid(string $token): array|null
    {
        return $this->where('token', $token)
                    ->groupStart()
                        ->where('expires_at IS NULL')
                        ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                    ->groupEnd()
                    ->first();
    }
    /**
     * Actualitza la data d'últim ús del token.
     *
     * @param string $token Token d'accés de la sessió.
     * @return void No retorna cap valor.
     */
    public function registrarUs(string $token): void
    {
        $this->db->query(
            'UPDATE tokens_acces SET last_used_at = ? WHERE token = ?',
            [date('Y-m-d H:i:s'), $token]
        );
    }
    /**
     * Elimina token.
     *
     * @param string $token Token d'accés de la sessió.
     * @return void No retorna cap valor.
     */
    public function eliminarToken(string $token): void
    {
        $this->where('token', $token)->delete();
    }
    /**
     * Elimina tots els tokens.
     *
     * @param int $usuariId Identificador de l'usuari autenticat.
     * @return void No retorna cap valor.
     */
    public function eliminarTotsElsTokens(int $usuariId): void
    {
        $this->where('usuari_id', $usuariId)->delete();
    }
}
