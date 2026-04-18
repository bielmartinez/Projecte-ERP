import api from '@/services/api'

export interface ResumMes {
  periode: string
  ingressos: number
  despeses: number
  benefici: number
}

export interface DashboardResum {
  mes_actual: ResumMes
  mes_anterior: ResumMes
}

export interface EvolucioMensualItem {
  mes: string
  ingressos: number
  despeses: number
}

export interface DistribucioCategoriaItem {
  categoria: string
  total: number
}

export interface DashboardGrafiques {
  evolucio_mensual: EvolucioMensualItem[]
  distribucio_categories: DistribucioCategoriaItem[]
}

export interface FacturaPendent {
  id: number
  numero_factura: string
  data_emisio: string
  data_venciment: string | null
  total: number
  estat: string
  client_nom: string
  import_cobrat: number
  import_pendent: number
}

export interface QuotaPropera {
  id: number
  nom: string
  import: number
  periodicitat: string
  periodes_pendents_count: number
  proper_venciment: string | null
  categoria_nom: string | null
}

interface ApiResponse<T> {
  status: 'ok' | 'error'
  data: T
  message?: string
}

export async function getDashboardResum() {
  const { data } = await api.get<ApiResponse<DashboardResum>>('/dashboard/resum')
  return data
}

export async function getDashboardGrafiques() {
  const { data } = await api.get<ApiResponse<DashboardGrafiques>>('/dashboard/grafiques')
  return data
}

export async function getFacturesPendents() {
  const { data } = await api.get<ApiResponse<FacturaPendent[]>>('/dashboard/factures-pendents')
  return data
}

export async function getQuotesProperes() {
  const { data } = await api.get<ApiResponse<QuotaPropera[]>>('/dashboard/quotes-properes')
  return data
}
