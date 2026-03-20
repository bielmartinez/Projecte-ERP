<?php

namespace App\Controllers;

use App\Models\UsuariModel;
use CodeIgniter\HTTP\ResponseInterface;

class PerfilController extends BaseController
{
    protected UsuariModel $usuariModel;

    public function __construct()
    {
        $this->usuariModel = new UsuariModel();
    }

    public function getPerfil(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $usuari = $this->usuariModel->find($usuariId);

            if (!$usuari) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Usuari no trobat',
                ]);
            }

            unset($usuari['password_hash']);
            unset($usuari['remember_token']);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $usuari,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en getPerfil: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtenir el perfil',
            ]);
        }
    }

    public function updatePerfil(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $data = $this->request->getJSON(true);

            if (!$data) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap dada',
                ]);
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
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No hi ha camps vàlids per actualitzar',
                ]);
            }


            if (!$this->usuariModel->validate($dataActualitzar)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Dades no vàlides',
                    'errors' => $this->usuariModel->errors(),
                ]);
            }

            $this->usuariModel->update($usuariId, $dataActualitzar);
            $usuari = $this->usuariModel->find($usuariId);
            unset($usuari['password_hash']);
            unset($usuari['remember_token']);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Perfil actualitzat correctament',
                'data' => $usuari,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en updatePerfil: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al actualitzar el perfil',
            ]);
        }
    }

    public function pujarLogo(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $fitxer = $this->request->getFile('logo');

            if (!$fitxer || $fitxer->getError() !== UPLOAD_ERR_OK) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'No s\'ha enviat cap fitxer o hi ha un error en la pujada',
                ]);
            }

            $mimeTypesPermesos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($fitxer->getMimeType(), $mimeTypesPermesos)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Tipus de fitxer no permès. Només JPG, PNG o GIF',
                ]);
            }

            if ($fitxer->getSize() > 5 * 1024 * 1024) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'El fitxer és massa gran. Màxim 5MB',
                ]);
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

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Logotip pujat correctament',
                'logo' => $rutaRelativa,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en pujarLogo: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al pujar el logotip',
            ]);
        }
    }

    public function canviarContrasenya(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $data = $this->request->getJSON(true);

            if (!isset($data['contrasenya_actual'], $data['contrasenya_nova'], $data['contrasenya_confirmacio'])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Falten camps requerits',
                ]);
            }

            // Query raw perquè el model elimina password_hash en afterFind
            $db = \Config\Database::connect();
            $usuari = $db->query(
                'SELECT password_hash FROM usuaris WHERE id = ?',
                [$usuariId]
            )->getRowArray();

            if (!$usuari || !isset($usuari['password_hash'])) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Usuari no trobat',
                ]);
            }

            if (!password_verify($data['contrasenya_actual'], $usuari['password_hash'])) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'La contrasenya actual no és correcta',
                ]);
            }

            if ($data['contrasenya_nova'] !== $data['contrasenya_confirmacio']) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Les contrasenyes noves no coincideixen',
                ]);
            }

            if (strlen($data['contrasenya_nova']) < 8) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La contrasenya nova ha de tenir mínim 8 caràcters',
                ]);
            }

            $passwordHash = password_hash($data['contrasenya_nova'], PASSWORD_BCRYPT);
            $this->usuariModel->update($usuariId, ['password_hash' => $passwordHash]);

            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Contrasenya actualitzada correctament',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en canviarContrasenya: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al canviar la contrasenya',
            ]);
        }
    }
}
