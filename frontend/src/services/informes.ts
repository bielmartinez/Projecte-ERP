import api from '@/services/api'

export interface PeriodeInforme {
  tipus: string
  etiqueta: string
  data_inici: string
  data_fi: string
}

export interface DadesInforme {
  periode: PeriodeInforme
  moviments: {
    ingressos: number
    despeses: number
    benefici: number
  }
  factures: {
    num_factures: number
    base_imposable: number
    total_facturat: number
  }
  fiscal: {
    iva_repercutit: number
    iva_suportat: number
    resultat_iva: number
    irpf_retingut: number
  }
}

export async function getInformeMensual(any: number, mes: number): Promise<DadesInforme> {
  const response = await api.get(`/informes/mensual/${any}/${mes}`)
  return response.data.data
}

export async function getInformeTrimestral(any: number, trimestre: number): Promise<DadesInforme> {
  const response = await api.get(`/informes/trimestral/${any}/${trimestre}`)
  return response.data.data
}

export async function getInformeAnual(any: number): Promise<DadesInforme> {
  const response = await api.get(`/informes/anual/${any}`)
  return response.data.data
}

export async function descarregarInformePdf(tipus: string, periode: string): Promise<void> {
  const response = await api.get(`/informes/pdf/${tipus}/${periode}`, {
    responseType: 'blob',
  })

  const blob = new Blob([response.data], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url

  const contentDisposition = response.headers['content-disposition'] ?? ''
  const match = contentDisposition.match(/filename="?(.+?)"?$/i)
  link.download = match ? match[1] : `informe_${tipus}_${periode}.pdf`

  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
