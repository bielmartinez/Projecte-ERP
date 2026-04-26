<?php

namespace App\Controllers;

use App\Models\CategoriaMovimentModel;
use App\Models\TokenAccesModel;
use App\Models\UsuariModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected UsuariModel $usuariModel;
    protected TokenAccesModel $tokenModel;
    protected CategoriaMovimentModel $categoriaMovimentModel;

    public function __construct()
    {
        $this->usuariModel = new UsuariModel();
        $this->tokenModel = new TokenAccesModel();
        $this->categoriaMovimentModel = new CategoriaMovimentModel();
    }

    public function register(): ResponseInterface
    {
        try {
            $dades = $this->request->getJSON(true) ?? [];

            $usuari = [
                'email' => trim((string) ($dades['email'] ?? '')),
                'password_hash' => (string) ($dades['password'] ?? ''),
                'nom' => trim((string) ($dades['nom'] ?? '')),
                'cognoms' => isset($dades['cognoms']) ? trim((string) $dades['cognoms']) : null,
                'nif' => isset($dades['nif']) ? trim((string) $dades['nif']) : null,
                'telefon' => isset($dades['telefon']) ? trim((string) $dades['telefon']) : null,
                'role' => 'user',
                'is_active' => true,
            ];

            if (!$this->usuariModel->validate($usuari)) {
                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->usuariModel->errors()]);
            }

            $usuari['password_hash'] = password_hash($usuari['password_hash'], PASSWORD_BCRYPT);

            $db = \Config\Database::connect();
            $db->transStart();

            $nouId = (int) $this->usuariModel->insert($usuari, true);

            if ($nouId <= 0 || !$this->crearCategoriesPerDefecte($nouId)) {
                $db->transRollback();

                return $this->jsonError('No s\'ha pogut completar el registre.', 500);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->jsonError('No s\'ha pogut completar el registre.', 500);
            }

            $usuariCreat = $this->usuariModel->find($nouId);

            return $this->jsonOk([
                'usuari' => $usuariCreat,
            ], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Error en auth register: ' . $e->getMessage());

            return $this->jsonError('No s\'ha pogut completar el registre.', 500);
        }
    }

    public function login(): ResponseInterface
    {
        $dades = $this->request->getJSON(true) ?? [];

        $email = trim((string) ($dades['email'] ?? ''));
        $password = (string) ($dades['password'] ?? '');
        $recordar = (bool) ($dades['recordar'] ?? false);

        if ($email === '' || $password === '') {
            return $this->jsonError('Email i contrasenya són obligatoris.', 422);
        }

        $usuari = $this->usuariModel->findPerLogin($email);

        if (!$usuari || !password_verify($password, $usuari['password_hash'])) {
            return $this->jsonError('Credencials incorrectes.', 401);
        }

        $this->usuariModel->update((int) $usuari['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $token = $this->tokenModel->crearToken((int) $usuari['id'], $recordar);

        unset($usuari['password_hash']);

        return $this->jsonOk([
            'token' => $token,
            'usuari' => $usuari,
        ], 200);
    }

    public function logout(): ResponseInterface
    {
        $token = $this->getBearerToken();

        if ($token === null) {
            return $this->jsonError('No autenticat.', 401);
        }

        $this->tokenModel->eliminarToken($token);

        return $this->jsonOk(['message' => 'Sessió tancada correctament.'], 200);
    }

    //Persisteix la sessió i retorna les dades de l'usuari associat al token vàlid
    public function me(): ResponseInterface
    {
        $token = $this->getBearerToken();

        if ($token === null) {
            return $this->jsonError('No autenticat.', 401);
        }

        $tokenData = $this->tokenModel->findTokenValid($token);

        if (!$tokenData) {
            return $this->jsonError('Token invàlid o expirat.', 401);
        }

        $this->tokenModel->registrarUs($token);

        $usuari = $this->usuariModel->find((int) $tokenData['usuari_id']);

        if (!$usuari) {
            return $this->jsonError('Usuari no trobat.', 404);
        }

        return $this->jsonOk([
            'usuari' => $usuari,
        ], 200);
    }

    private function getBearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');

        if ($header === '') {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function crearCategoriesPerDefecte(int $usuariId): bool
    {
        if ($usuariId <= 0) {
            return false;
        }

        $ara = date('Y-m-d H:i:s');
        $rows = [
            ['usuari_id' => $usuariId, 'nom' => 'Vendes', 'tipus' => 'ingres', 'created_at' => $ara, 'updated_at' => $ara],
            ['usuari_id' => $usuariId, 'nom' => 'Serveis', 'tipus' => 'ingres', 'created_at' => $ara, 'updated_at' => $ara],
            ['usuari_id' => $usuariId, 'nom' => 'Lloguer', 'tipus' => 'despesa', 'created_at' => $ara, 'updated_at' => $ara],
            ['usuari_id' => $usuariId, 'nom' => 'Subministraments', 'tipus' => 'despesa', 'created_at' => $ara, 'updated_at' => $ara],
            ['usuari_id' => $usuariId, 'nom' => 'Impostos i quotes', 'tipus' => 'despesa', 'created_at' => $ara, 'updated_at' => $ara],
        ];

        return (bool) $this->categoriaMovimentModel->insertBatch($rows);
    }
}