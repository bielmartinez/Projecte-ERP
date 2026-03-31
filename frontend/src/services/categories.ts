import api from '@/services/api'

export type TipusMoviment = 'ingres' | 'despesa'

export interface CategoriaMoviment {
  id: number
  usuari_id: number
  nom: string
  tipus: TipusMoviment
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

export interface CategoriaMovimentPayload {
  nom: string
  tipus: TipusMoviment
}

export interface CategoriaFilters {
  page?: number
  limit?: number
  search?: string
  tipus?: TipusMoviment
}

export async function getCategories(params: CategoriaFilters = {}) {
  const { data } = await api.get('/categories', { params })
  return data
}

export async function getCategoria(id: number | string) {
  const { data } = await api.get(`/categories/${id}`)
  return data
}

export async function createCategoria(payload: CategoriaMovimentPayload) {
  const { data } = await api.post('/categories', payload)
  return data
}

export async function updateCategoria(id: number | string, payload: Partial<CategoriaMovimentPayload>) {
  const { data } = await api.put(`/categories/${id}`, payload)
  return data
}

export async function deleteCategoria(id: number | string) {
  const { data } = await api.delete(`/categories/${id}`)
  return data
}
