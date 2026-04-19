import api from '@/services/api'

export interface RegistreVerifactu {
  id: number
  factura_id: number
  usuari_id: number
  tipus_registre: string
  subsanacio: string
  nif_emisor: string
  numero_factura: string
  data_emisio: string
  nom_rao_emisor: string
  tipus_factura: string
  quota_total: string
  import_total: string
  hash_registre: string
  hash_anterior: string | null
  data_hora_generacio: string
  nif_emisor_anterior: string | null
  numero_factura_anterior: string | null
  data_emisio_anterior: string | null
  dades_factura: string
  codi_qr: string | null
  created_at: string
  estat_factura_actual: string | null
}

export interface ValidacioCadena {
  valid: boolean
  total_registres: number
  errors: Array<{
    registre_id: number
    motiu: string
  }>
}

interface MetaPaginacio {
  page: number
  limit: number
  total: number
  total_pages: number
}

interface ApiResponse<T> {
  status: 'ok' | 'error'
  data: T
  meta?: MetaPaginacio
  message?: string
}

export async function getRegistresVerifactu(params: { page?: number; limit?: number } = {}) {
  const { data } = await api.get<ApiResponse<RegistreVerifactu[]>>('/verifactu/registres', { params })
  return data
}

export async function getRegistreVerifactu(id: number | string) {
  const { data } = await api.get<ApiResponse<RegistreVerifactu>>(`/verifactu/registres/${id}`)
  return data
}

export async function validarCadenaVerifactu() {
  const { data } = await api.get<ApiResponse<ValidacioCadena>>('/verifactu/validar')
  return data
}

export async function exportarRegistresVerifactu() {
  const response = await api.get('/verifactu/exportar', {
    responseType: 'blob'
  })

  const contentDisposition = response.headers['content-disposition'] as string | undefined
  const fileNameMatch = contentDisposition?.match(/filename="?([^\"]+)"?/)
  const fileName = fileNameMatch?.[1] ?? `verifactu-export-${new Date().toISOString().slice(0, 10)}.json`

  const blob = new Blob([response.data], { type: 'application/json' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = fileName
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export async function getRegistresPerFactura(facturaId: number | string) {
  const resposta = await getRegistresVerifactu()

  if (resposta.status !== 'ok') {
    return resposta
  }

  return {
    ...resposta,
    data: (resposta.data ?? []).filter((registre) => Number(registre.factura_id) === Number(facturaId))
  }
}
