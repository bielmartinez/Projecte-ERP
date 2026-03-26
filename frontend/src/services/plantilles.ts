import api from '@/services/api'

export interface PlantillaLiniaPayload {
  descripcio: string
  quantitat: number
  preu_unitari: number
  iva_percentatge?: number
  descompte?: number
}

export interface PlantillaPayload {
  nom: string
  descripcio?: string
  iva_percentatge?: number
  irpf_percentatge?: number
  metode_pagament?: string
  notes_plantilla?: string
  linies: PlantillaLiniaPayload[]
}

export interface Plantilla {
  id: number
  usuari_id: number
  nom: string
  descripcio: string | null
  iva_percentatge: string
  irpf_percentatge: string
  metode_pagament: string | null
  notes_plantilla: string | null
  created_at: string | null
  updated_at: string | null
  linies?: PlantillaLinia[]
}

export interface PlantillaLinia {
  id: number
  plantilla_id: number
  descripcio: string
  quantitat: string
  preu_unitari: string
  iva_percentatge: string
  descompte: string
  ordre: number
}

export async function getPlantilles(params: { page?: number; limit?: number; search?: string } = {}) {
  const { data } = await api.get('/plantilles', { params })
  return data
}

export async function getPlantilla(id: number | string) {
  const { data } = await api.get(`/plantilles/${id}`)
  return data
}

export async function createPlantilla(payload: PlantillaPayload) {
  const { data } = await api.post('/plantilles', payload)
  return data
}

export async function updatePlantilla(id: number | string, payload: Partial<PlantillaPayload>) {
  const { data } = await api.put(`/plantilles/${id}`, payload)
  return data
}

export async function deletePlantilla(id: number | string) {
  const { data } = await api.delete(`/plantilles/${id}`)
  return data
}

export async function crearFacturaDesDePlantilla(
  id: number | string,
  payload: { client_id: number; data_emisio?: string; data_venciment?: string }
) {
  const { data } = await api.post(`/plantilles/${id}/crear-factura`, payload)
  return data
}
