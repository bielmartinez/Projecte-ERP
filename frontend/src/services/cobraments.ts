import api from '@/services/api'

export interface Cobrament {
  id: number
  factura_id: number
  moviment_id: number | null
  import: string
  data_cobrament: string
  metode_pagament: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

export interface CobramentPayload {
  import: number
  data_cobrament: string
  metode_pagament?: string
  notes?: string
}

export interface CobramentsMeta {
  total_cobrat: number
  total_factura: number
  pendent: number
}

export async function getCobramentsFactura(facturaId: number | string) {
  const { data } = await api.get(`/factures/${facturaId}/cobraments`)
  return data
}

export async function crearCobrament(facturaId: number | string, payload: CobramentPayload) {
  const { data } = await api.post(`/factures/${facturaId}/cobraments`, payload)
  return data
}

export async function eliminarCobrament(facturaId: number | string, cobramentId: number | string) {
  const { data } = await api.delete(`/factures/${facturaId}/cobraments/${cobramentId}`)
  return data
}
