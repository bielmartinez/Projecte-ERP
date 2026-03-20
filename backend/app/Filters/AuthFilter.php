<?php

namespace App\Filters;

use App\Models\TokenAccesModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return service('response')->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat.',
            ]);
        }

        $token = trim($matches[1]);
        $tokenModel = new TokenAccesModel();
        $tokenData = $tokenModel->findTokenValid($token);

        if (!$tokenData) {
            return service('response')->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Token invàlid o expirat.',
            ]);
        }

        $request->usuariId = $tokenData['usuari_id'];

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return;
    }
}