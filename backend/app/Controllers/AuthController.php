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
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'errors' => $this->usuariModel->errors(),
                ]);
            }

            $usuari['password_hash'] = password_hash($usuari['password_hash'], PASSWORD_BCRYPT);

            $db = \Config\Database::connect();
            $db->transStart();

            $nouId = (int) $this->usuariModel->insert($usuari, true);

            if ($nouId <= 0 || !$this->crearCategoriesPerDefecte($nouId)) {
                $db->transRollback();

                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut completar el registre.',
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha pogut completar el registre.',
                ]);
            }

            $usuariCreat = $this->usuariModel->find($nouId);

            return $this->response->setStatusCode(201)->setJSON([
                'status' => 'ok',
                'usuari' => $usuariCreat,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error en auth register: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'No s\'ha pogut completar el registre.',
            ]);
        }
    }

    public function login(): ResponseInterface
    {
        $dades = $this->request->getJSON(true) ?? [];

        $email = trim((string) ($dades['email'] ?? ''));
        $password = (string) ($dades['password'] ?? '');
        $recordar = (bool) ($dades['recordar'] ?? false);

        if ($email === '' || $password === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Email i contrasenya són obligatoris.',
            ]);
        }

        $usuari = $this->usuariModel->findPerLogin($email);

        if (!$usuari || !password_verify($password, $usuari['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Credencials incorrectes.',
            ]);
        }

        $this->usuariModel->update((int) $usuari['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $token = $this->tokenModel->crearToken((int) $usuari['id'], $recordar);

        unset($usuari['password_hash']);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'ok',
            'token' => $token,
            'usuari' => $usuari,
        ]);
    }

    public function logout(): ResponseInterface
    {
        $token = $this->getBearerToken();

        if ($token === null) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat.',
            ]);
        }

        $this->tokenModel->eliminarToken($token);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'ok',
            'message' => 'Sessió tancada correctament.',
        ]);
    }

    //Persisteix la sessió i retorna les dades de l'usuari associat al token vàlid
    public function me(): ResponseInterface
    {
        $token = $this->getBearerToken();

        if ($token === null) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat.',
            ]);
        }

        $tokenData = $this->tokenModel->findTokenValid($token);

        if (!$tokenData) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Token invàlid o expirat.',
            ]);
        }

        $this->tokenModel->registrarUs($token);

        $usuari = $this->usuariModel->find((int) $tokenData['usuari_id']);

        if (!$usuari) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Usuari no trobat.',
            ]);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'ok',
            'usuari' => $usuari,
        ]);
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