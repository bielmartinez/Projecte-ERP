import api from '@/services/api'

export interface ClientPayload {
  nom: string
  cognoms?: string
  nom_empresa?: string
  nif?: string
  email?: string
  telefon?: string
  adreca?: string
  codi_postal?: string
  poblacio?: string
  provincia?: string
  pais?: string
  notes?: string
}

export interface Client {
  id: number
  usuari_id: number
  nom: string
  cognoms: string | null
  nom_empresa: string | null
  nif: string | null
  email: string | null
  telefon: string | null
  adreca: string | null
  codi_postal: string | null
  poblacio: string | null
  provincia: string | null
  pais: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

export async function getClients(params: { page?: number; limit?: number; search?: string } = {}) {
  const { data } = await api.get('/clients', { params })
  return data
}

export async function getClient(id: number | string) {
  const { data } = await api.get(`/clients/${id}`)
  return data
}

export async function createClient(payload: ClientPayload) {
  const { data } = await api.post('/clients', payload)
  return data
}

export async function updateClient(id: number | string, payload: Partial<ClientPayload>) {
  const { data } = await api.put(`/clients/${id}`, payload)
  return data
}

export async function deleteClient(id: number | string) {
  const { data } = await api.delete(`/clients/${id}`)
  return data
}
