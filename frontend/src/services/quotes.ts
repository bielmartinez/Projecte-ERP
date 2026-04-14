import api from '@/services/api'

export type Periodicitat = 'mensual' | 'trimestral' | 'anual'

export interface Quota {
  id: number
  usuari_id: number
  nom: string
  descripcio: string | null
  import: string
  periodicitat: Periodicitat
  dia_pagament: number
  data_inici: string
  data_fi: string | null
  categoria_id: number
  activa: boolean
  categoria_nom?: string | null
  periodes_pendents_count?: number
  proper_venciment?: string | null
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

export interface QuotaPayload {
  nom: string
  descripcio?: string
  import: number
  periodicitat: Periodicitat
  dia_pagament: number
  data_inici: string
  data_fi?: string | null
  categoria_id: number
  activa?: boolean
}

export interface PeriodePendent {
  periode: string
  import: number
}

export interface PagamentQuota {
  id: number
  quota_id: number
  moviment_id: number | null
  data_pagament: string
  import: string
  estat: string
  periode_corresponent: string
  notes: string | null
  created_at: string | null
}

export interface PagamentPayload {
  periode_corresponent: string
  import?: number
  notes?: string
}

export async function getQuotes(params: { activa?: boolean } = {}) {
  const { data } = await api.get('/quotes', { params })
  return data
}

export async function getQuota(id: number | string) {
  const { data } = await api.get(`/quotes/${id}`)
  return data
}

export async function createQuota(payload: QuotaPayload) {
  const { data } = await api.post('/quotes', payload)
  return data
}

export async function updateQuota(id: number | string, payload: Partial<QuotaPayload>) {
  const { data } = await api.put(`/quotes/${id}`, payload)
  return data
}

export async function deleteQuota(id: number | string) {
  const { data } = await api.delete(`/quotes/${id}`)
  return data
}

export async function pagarQuota(id: number | string, payload: PagamentPayload) {
  const { data } = await api.post(`/quotes/${id}/pagar`, payload)
  return data
}

export async function getPagamentsQuota(id: number | string) {
  const { data } = await api.get(`/quotes/${id}/pagaments`)
  return data
}
