import api from '@/services/api'

export interface PerfilPayload {
  nom?: string
  cognoms?: string
  nif?: string
  telefon?: string
  nom_empresa?: string
  adreca?: string
  codi_postal?: string
  poblacio?: string
  provincia?: string
  pais?: string
  compte_bancari?: string
}

export interface PasswordPayload {
  contrasenya_actual: string
  contrasenya_nova: string
  contrasenya_confirmacio: string
}

export async function getPerfil() {
  const { data } = await api.get('/perfil')
  return data
}

export async function updatePerfil(payload: PerfilPayload) {
  const { data } = await api.put('/perfil', payload)
  return data
}

export async function uploadLogo(file: File) {
  const formData = new FormData()
  formData.append('logo', file)

  const { data } = await api.post('/perfil/logo', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })

  return data
}

export async function changePassword(payload: PasswordPayload) {
  const { data } = await api.put('/perfil/contrasenya', payload)
  return data
}
