<?php

namespace App\Controllers;

use App\Models\UsuariModel;
use CodeIgniter\HTTP\ResponseInterface;

class PerfilController extends BaseController
{
    protected UsuariModel $usuariModel;
    /**
     * Inicialitza els models i serveis: UsuariModel.
     */
    public function __construct()
    {
        $this->usuariModel = new UsuariModel();
    }
    /**
     * Obté perfil.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function getPerfil(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $usuari = $this->usuariModel->find($usuariId);

            if (!$usuari) {
                return $this->jsonError('Usuari no trobat', 404);
            }

            unset($usuari['password_hash']);
            unset($usuari['remember_token']);

            return $this->jsonOk(['data' => $usuari]);
        } catch (\Exception $e) {
            log_message('error', 'Error en getPerfil: ' . $e->getMessage());
            return $this->jsonError('Error al obtenir el perfil', 500);
        }
    }
    /**
     * Actualitza les dades bàsiques del perfil d'usuari.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function updatePerfil(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $data = $this->request->getJSON(true);

            if (!$data) {
                return $this->jsonError('No s\'ha enviat cap dada', 400);
            }

            // Whitelist fields per evitar mass assignment
            $fieldsPermesos = [
                'nom',
                'cognoms',
                'nif',
                'telefon',
                'nom_empresa',
                'adreca',
                'codi_postal',
                'poblacio',
                'provincia',
                'pais',
                'compte_bancari',
            ];

            $dataActualitzar = [];
            foreach ($fieldsPermesos as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null) {
                    $dataActualitzar[$field] = $data[$field];
                }
            }

            if (empty($dataActualitzar)) {
                return $this->jsonError('No hi ha camps vàlids per actualitzar', 400);
            }


            if (!$this->usuariModel->validate($dataActualitzar)) {
                return $this->jsonError('Dades no vàlides', 422, ['errors' => $this->usuariModel->errors()]);
            }

            $this->usuariModel->update($usuariId, $dataActualitzar);
            $usuari = $this->usuariModel->find($usuariId);
            unset($usuari['password_hash']);
            unset($usuari['remember_token']);

            return $this->jsonOk([
                'message' => 'Perfil actualitzat correctament',
                'data' => $usuari,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en updatePerfil: ' . $e->getMessage());
            return $this->jsonError('Error al actualitzar el perfil', 500);
        }
    }
    /**
     * Puja i associa el logotip del perfil de l'usuari.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function pujarLogo(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $fitxer = $this->request->getFile('logo');

            if (!$fitxer || $fitxer->getError() !== UPLOAD_ERR_OK) {
                return $this->jsonError('No s\'ha enviat cap fitxer o hi ha un error en la pujada', 400);
            }

            $mimeTypesPermesos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($fitxer->getMimeType(), $mimeTypesPermesos)) {
                return $this->jsonError('Tipus de fitxer no permès. Només JPG, PNG o GIF', 422);
            }

            if ($fitxer->getSize() > 5 * 1024 * 1024) {
                return $this->jsonError('El fitxer és massa gran. Màxim 5MB', 422);
            }

            $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $usuariId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = $fitxer->guessExtension();
            $nomFitxer = 'logo_' . time() . '.' . $extension;
            $fitxer->move($uploadDir, $nomFitxer);

            $rutaRelativa = 'uploads/' . $usuariId . '/' . $nomFitxer;
            $this->usuariModel->update($usuariId, ['logo' => $rutaRelativa]);

            return $this->jsonOk([
                'message' => 'Logotip pujat correctament',
                'logo' => $rutaRelativa,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en pujarLogo: ' . $e->getMessage());
            return $this->jsonError('Error al pujar el logotip', 500);
        }
    }
    /**
     * Canvia contrasenya.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function canviarContrasenya(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $data = $this->request->getJSON(true);

            if (!isset($data['contrasenya_actual'], $data['contrasenya_nova'], $data['contrasenya_confirmacio'])) {
                return $this->jsonError('Falten camps requerits', 400);
            }

            // Query raw perquè el model elimina password_hash en afterFind
            $db = \Config\Database::connect();
            $usuari = $db->query(
                'SELECT password_hash FROM usuaris WHERE id = ?',
                [$usuariId]
            )->getRowArray();

            if (!$usuari || !isset($usuari['password_hash'])) {
                return $this->jsonError('Usuari no trobat', 404);
            }

            if (!password_verify($data['contrasenya_actual'], $usuari['password_hash'])) {
                return $this->jsonError('La contrasenya actual no és correcta', 401);
            }

            if ($data['contrasenya_nova'] !== $data['contrasenya_confirmacio']) {
                return $this->jsonError('Les contrasenyes noves no coincideixen', 422);
            }

            if (strlen($data['contrasenya_nova']) < 8) {
                return $this->jsonError('La contrasenya nova ha de tenir mínim 8 caràcters', 422);
            }

            $passwordHash = password_hash($data['contrasenya_nova'], PASSWORD_BCRYPT);
            $this->usuariModel->update($usuariId, ['password_hash' => $passwordHash]);

            return $this->jsonOk(['message' => 'Contrasenya actualitzada correctament']);
        } catch (\Exception $e) {
            log_message('error', 'Error en canviarContrasenya: ' . $e->getMessage());
            return $this->jsonError('Error al canviar la contrasenya', 500);
        }
    }
}
