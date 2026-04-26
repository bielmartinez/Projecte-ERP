<?php

namespace App\Controllers;

use App\Libraries\VerifactuService;
use App\Models\RegistreVerifactuModel;
use CodeIgniter\HTTP\ResponseInterface;

class VerifactuController extends BaseController
{
    protected RegistreVerifactuModel $registreModel;
    protected VerifactuService $verifactuService;
    /**
     * Inicialitza els models i serveis: RegistreVerifactuModel i VerifactuService.
     */
    public function __construct()
    {
        $this->registreModel = new RegistreVerifactuModel();
        $this->verifactuService = new VerifactuService();
    }
    /**
     * Llista registre Verifactu disponibles per a l'usuari autenticat.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function index(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $limit = max(1, (int) ($this->request->getGet('limit') ?? 20));

            $resultat = $this->registreModel->obtenirRegistresPerUsuari($usuariId, $page, $limit);

            return $this->jsonOk([
                'data' => $resultat['data'],
                'meta' => $resultat['meta'],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu index: ' . $e->getMessage());

            return $this->jsonError('Error en llistar els registres Verifactu', 500);
        }
    }
    /**
     * Recupera el detall d'un registre Verifactu concret.
     *
     * @param int $id Identificador del recurs.
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $registre = $this->registreModel
                ->where('id', $id)
                ->where('usuari_id', $usuariId)
                ->first();

            if (!$registre) {
                return $this->jsonError('Registre Verifactu no trobat', 404);
            }

            return $this->jsonOk(['data' => $registre]);
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu show: ' . $e->getMessage());

            return $this->jsonError('Error en carregar el registre Verifactu', 500);
        }
    }
    /**
     * Valida .
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function validar(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $resultat = $this->verifactuService->validarCadena($usuariId);

            return $this->jsonOk(['data' => $resultat]);
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu validar: ' . $e->getMessage());

            return $this->jsonError('Error en validar la cadena Verifactu', 500);
        }
    }
    /**
     * Exporta els registres Verifactu de l'usuari en format JSON.
     *
     * @return ResponseInterface Fitxer JSON descarregable amb les dades exportades.
     */
    public function exportar(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $registres = $this->registreModel->obtenirTotsRegistres($usuariId);
            $nomFitxer = 'verifactu-export-' . date('Y-m-d') . '.json';

            return $this->response
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $nomFitxer . '"')
                ->setBody(json_encode($registres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            log_message('error', 'Error en verifactu exportar: ' . $e->getMessage());

            return $this->jsonError('Error en exportar els registres Verifactu', 500);
        }
    }
}
