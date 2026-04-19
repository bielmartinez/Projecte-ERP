<?php

namespace App\Controllers;

use App\Libraries\VerifactuService;
use App\Models\RegistreVerifactuModel;
use CodeIgniter\HTTP\ResponseInterface;

class VerifactuController extends BaseController
{
    protected RegistreVerifactuModel $registreModel;
    protected VerifactuService $verifactuService;

    public function __construct()
    {
        $this->registreModel = new RegistreVerifactuModel();
        $this->verifactuService = new VerifactuService();
    }

    public function index(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $limit = max(1, (int) ($this->request->getGet('limit') ?? 20));

            $resultat = $this->registreModel->obtenirRegistresPerUsuari($usuariId, $page, $limit);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $resultat['data'],
                'meta' => $resultat['meta'],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu index: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en llistar els registres Verifactu',
            ]);
        }
    }

    public function show(int $id): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $registre = $this->registreModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$registre) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Registre Verifactu no trobat',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $registre,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu show: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en carregar el registre Verifactu',
            ]);
        }
    }

    public function validar(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $resultat = $this->verifactuService->validarCadena($usuariId);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $resultat,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu validar: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en validar la cadena Verifactu',
            ]);
        }
    }

    public function exportar(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $registres = $this->registreModel->obtenirTotsRegistres($usuariId);
            $nomFitxer = 'verifactu-export-' . date('Y-m-d') . '.json';

            return $this->response
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $nomFitxer . '"')
                ->setBody(json_encode($registres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu exportar: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error en exportar els registres Verifactu',
            ]);
        }
    }
}
