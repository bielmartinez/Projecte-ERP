import api from '@/services/api'

export interface FacturaLiniaPayload {
  descripcio: string
  quantitat: number
  preu_unitari: number
  iva_percentatge?: number
  descompte?: number
}

export interface FacturaPayload {
  client_id: number
  serie?: string
  data_emisio: string
  data_venciment?: string
  estat?: 'esborrany' | 'emesa' | 'cancel·lada' | 'cobrada'
  iva_percentatge?: number
  irpf_percentatge?: number
  metode_pagament?: string
  data_cobrament?: string
  notes?: string
  linies: FacturaLiniaPayload[]
}

export interface Factura {
  id: number
  usuari_id: number
  client_id: number
  serie: string
  numero_factura: string
  data_emisio: string
  data_venciment: string | null
  estat: 'esborrany' | 'emesa' | 'cancel·lada' | 'cobrada'
  subtotal: string
  iva_percentatge: string
  iva_import: string
  irpf_percentatge: string
  irpf_import: string
  total: string
  metode_pagament: string | null
  data_cobrament: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

export interface FacturaLinia {
  id: number
  factura_id: number
  descripcio: string
  quantitat: string
  preu_unitari: string
  iva_percentatge: string
  descompte: string
  total_linia: string
  ordre: number
}

export interface FacturaFilters {
  page?: number
  limit?: number
  search?: string
  estat?: string
  client_id?: number
  data_desde?: string
  data_fins?: string
}

export async function getFactures(params: FacturaFilters = {}) {
  const { data } = await api.get('/factures', { params })
  return data
}

export async function getFactura(id: number | string) {
  const { data } = await api.get(`/factures/${id}`)
  return data
}

export async function createFactura(payload: FacturaPayload) {
  const { data } = await api.post('/factures', payload)
  return data
}

export async function updateFactura(id: number | string, payload: Partial<FacturaPayload>) {
  const { data } = await api.put(`/factures/${id}`, payload)
  return data
}

export async function deleteFactura(id: number | string) {
  const { data } = await api.delete(`/factures/${id}`)
  return data
}

export async function updateLiniaFactura(id: number | string, liniaId: number | string, payload: Partial<FacturaLiniaPayload>) {
  const { data } = await api.put(`/factures/${id}/linies/${liniaId}`, payload)
  return data
}

export async function deleteLiniaFactura(id: number | string, liniaId: number | string) {
  const { data } = await api.delete(`/factures/${id}/linies/${liniaId}`)
  return data
}

export async function canviarEstatFactura(id: number | string, estat: FacturaPayload['estat']) {
  const { data } = await api.put(`/factures/${id}/estat`, { estat })
  return data
}

export async function descarregarFacturaPdf(id: number | string) {
  const response = await api.get(`/factures/${id}/pdf`, {
    responseType: 'blob'
  })

  const contentDisposition = response.headers['content-disposition'] as string | undefined
  const fileNameMatch = contentDisposition?.match(/filename="?([^\"]+)"?/)
  const fileName = fileNameMatch?.[1] ?? `factura-${id}.pdf`

  const blob = new Blob([response.data], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = fileName
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}
