<?php

namespace App\Controllers;

use App\Models\UsuariModel;
use App\Models\TokenAccesModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected UsuariModel $usuariModel;
    protected TokenAccesModel $tokenModel;

    public function __construct()
    {
        $this->usuariModel = new UsuariModel();
        $this->tokenModel  = new TokenAccesModel();
    }

    public function register(): ResponseInterface
    {
        $dades = $this->request->getJSON(true);

        $usuari = [
            'email'         => $dades['email']    ?? '',
            'password_hash' => $dades['password'] ?? '',
            'nom'           => $dades['nom']       ?? '',
            'cognoms'       => $dades['cognoms']   ?? null,
            'nif'           => $dades['nif']       ?? null,
            'telefon'       => $dades['telefon']   ?? null,
        ];

        if (!$this->usuariModel->validate($usuari)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'errors'  => $this->usuariModel->errors(),
                ]);
        }

        $usuari['password_hash'] = password_hash($usuari['password_hash'], PASSWORD_BCRYPT);

        $nouId = $this->usuariModel->insert($usuari);

        $usuariCreat = $this->usuariModel->find($nouId);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status' => 'ok',
                'usuari' => $usuariCreat,
            ]);
    }

    public function login(): ResponseInterface
    {
        $dades = $this->request->getJSON(true);

        $email     = $dades['email']    ?? '';
        $password  = $dades['password'] ?? '';
        $recordar  = $dades['recordar'] ?? false;

        $usuari = $this->usuariModel->findPerLogin($email);

        if (!$usuari || !password_verify($password, $usuari['password_hash'])) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Credencials incorrectes.',
                ]);
        }

        $this->usuariModel->update($usuari['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $token = $this->tokenModel->crearToken($usuari['id'], $recordar);

        unset($usuari['password_hash']);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status' => 'ok',
                'token'  => $token,
                'usuari' => $usuari,
            ]);
    }

    public function logout(): ResponseInterface
    {
        $token = $this->request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'No s\'ha proporcionat cap token.',
                ]);
        }

        $this->tokenModel->eliminarToken($token);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status'  => 'ok',
                'message' => 'Sessió tancada correctament.',
            ]);
    }
}