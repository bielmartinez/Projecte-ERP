import api from '@/services/api'

import type { TipusMoviment } from '@/services/categories'

export interface Moviment {
  id: number
  usuari_id: number
  categoria_id: number
  factura_id: number | null
  tipus: TipusMoviment
  descripcio: string
  import: string
  data: string
  categoria_nom?: string | null
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

export interface MovimentPayload {
  categoria_id: number
  factura_id?: number
  tipus: TipusMoviment
  descripcio: string
  import: number
  data: string
}

export interface MovimentFilters {
  page?: number
  limit?: number
  search?: string
  tipus?: TipusMoviment
  categoria_id?: number
  data_desde?: string
  data_fins?: string
}

export async function getMoviments(params: MovimentFilters = {}) {
  const { data } = await api.get('/moviments', { params })
  return data
}

export async function getMoviment(id: number | string) {
  const { data } = await api.get(`/moviments/${id}`)
  return data
}

export async function createMoviment(payload: MovimentPayload) {
  const { data } = await api.post('/moviments', payload)
  return data
}

export async function updateMoviment(id: number | string, payload: Partial<MovimentPayload>) {
  const { data } = await api.put(`/moviments/${id}`, payload)
  return data
}

export async function deleteMoviment(id: number | string) {
  const { data } = await api.delete(`/moviments/${id}`)
  return data
}
