<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-semibold">Informes</h2>

    <div v-if="initialLoading" class="space-y-4 animate-pulse" aria-busy="true">
      <div class="bg-white rounded shadow p-4 space-y-3">
        <div class="h-10 rounded bg-gray-200 w-1/3"></div>
        <div class="h-10 rounded bg-gray-200 w-1/3"></div>
        <div class="h-10 rounded bg-gray-200 w-1/4"></div>
      </div>
      <div class="bg-white rounded shadow p-4 space-y-3">
        <div class="h-6 rounded bg-gray-200 w-1/2"></div>
        <div class="h-4 rounded bg-gray-200"></div>
        <div class="h-4 rounded bg-gray-200"></div>
        <div class="h-4 rounded bg-gray-200"></div>
      </div>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Tipus d'informe</span>
            <select v-model="tipusInforme" class="border rounded px-3 py-2 w-full">
              <option value="mensual">Mensual</option>
              <option value="trimestral">Trimestral</option>
              <option value="anual">Anual</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Any</span>
            <select v-model.number="anySeleccionat" class="border rounded px-3 py-2 w-full">
              <option v-for="a in anysDisponibles" :key="a" :value="a">{{ a }}</option>
            </select>
          </label>

          <label v-if="tipusInforme === 'mensual'" class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Mes</span>
            <select v-model.number="mesSeleccionat" class="border rounded px-3 py-2 w-full">
              <option v-for="m in 12" :key="m" :value="m">{{ nomMes(m) }}</option>
            </select>
          </label>

          <label v-if="tipusInforme === 'trimestral'" class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Trimestre</span>
            <select v-model.number="trimestreSeleccionat" class="border rounded px-3 py-2 w-full">
              <option :value="1">T1 (Gener - Març)</option>
              <option :value="2">T2 (Abril - Juny)</option>
              <option :value="3">T3 (Juliol - Setembre)</option>
              <option :value="4">T4 (Octubre - Desembre)</option>
            </select>
          </label>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
            :disabled="loading"
            @click="carregarInforme"
          >
            {{ loading ? 'Carregant...' : 'Generar informe' }}
          </button>
          <button
            v-if="informe"
            type="button"
            class="border px-4 py-2 rounded hover:bg-gray-50 disabled:opacity-50"
            :disabled="descarregant"
            @click="descarregarPdf"
          >
            {{ descarregant ? 'Descarregant...' : 'Descarregar PDF' }}
          </button>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </section>

      <template v-if="informe">
        <section class="bg-white rounded shadow p-4">
          <h3 class="text-lg font-semibold mb-1">{{ titolInforme }}</h3>
          <p class="text-sm text-gray-500 mb-4">{{ informe.periode.data_inici }} — {{ informe.periode.data_fi }}</p>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="py-2 pr-4 text-left font-semibold" colspan="2">Resum d'activitat</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-b">
                  <td class="py-2 pr-4">Ingressos totals</td>
                  <td class="py-2 text-right font-medium text-green-700">{{ formatMoney(informe.moviments.ingressos) }}</td>
                </tr>
                <tr class="border-b">
                  <td class="py-2 pr-4">Despeses totals</td>
                  <td class="py-2 text-right font-medium text-red-700">{{ formatMoney(informe.moviments.despeses) }}</td>
                </tr>
                <tr class="border-b bg-gray-50">
                  <td class="py-2 pr-4 font-semibold">Benefici net</td>
                  <td class="py-2 text-right font-semibold" :class="informe.moviments.benefici >= 0 ? 'text-green-700' : 'text-red-700'">
                    {{ formatMoney(informe.moviments.benefici) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="bg-white rounded shadow p-4">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="py-2 pr-4 text-left font-semibold" colspan="2">Facturació</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-b">
                  <td class="py-2 pr-4">Factures emeses</td>
                  <td class="py-2 text-right font-medium">{{ informe.factures.num_factures }}</td>
                </tr>
                <tr class="border-b">
                  <td class="py-2 pr-4">Base imposable</td>
                  <td class="py-2 text-right font-medium">{{ formatMoney(informe.factures.base_imposable) }}</td>
                </tr>
                <tr class="border-b">
                  <td class="py-2 pr-4">Total facturat</td>
                  <td class="py-2 text-right font-medium">{{ formatMoney(informe.factures.total_facturat) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="bg-white rounded shadow p-4">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="py-2 pr-4 text-left font-semibold" colspan="2">Resum fiscal</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-b">
                  <td class="py-2 pr-4">IVA repercutit (cobrat als clients)</td>
                  <td class="py-2 text-right font-medium">{{ formatMoney(informe.fiscal.iva_repercutit) }}</td>
                </tr>
                <tr class="border-b">
                  <td class="py-2 pr-4">IVA suportat (pagat en despeses)</td>
                  <td class="py-2 text-right font-medium">{{ formatMoney(informe.fiscal.iva_suportat) }}</td>
                </tr>
                <tr class="border-b bg-gray-50">
                  <td class="py-2 pr-4 font-semibold">Resultat IVA (a pagar a Hisenda)</td>
                  <td class="py-2 text-right font-semibold" :class="informe.fiscal.resultat_iva >= 0 ? 'text-red-700' : 'text-green-700'">
                    {{ formatMoney(informe.fiscal.resultat_iva) }}
                  </td>
                </tr>
                <tr class="border-b">
                  <td class="py-2 pr-4">IRPF retingut en factures</td>
                  <td class="py-2 text-right font-medium">{{ formatMoney(informe.fiscal.irpf_retingut) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

import { useInitialLoading } from '@/composables/useInitialLoading'
import {
  descarregarInformePdf,
  getInformeAnual,
  getInformeMensual,
  getInformeTrimestral,
  type DadesInforme,
} from '@/services/informes'

const tipusInforme = ref<'mensual' | 'trimestral' | 'anual'>('mensual')
const anySeleccionat = ref(new Date().getFullYear())
const mesSeleccionat = ref(new Date().getMonth() + 1)
const trimestreSeleccionat = ref(Math.ceil((new Date().getMonth() + 1) / 3))

const informe = ref<DadesInforme | null>(null)
const loading = ref(false)
const descarregant = ref(false)
const error = ref('')

const { initialLoading, runInitialLoad } = useInitialLoading()

const anyActual = new Date().getFullYear()
const anysDisponibles = computed(() => {
  const anys: number[] = []
  for (let a = anyActual; a >= anyActual - 5; a--) {
    anys.push(a)
  }
  return anys
})

const nomsMesos = [
  'Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny',
  'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre',
]

function nomMes(m: number): string {
  return nomsMesos[m - 1] ?? ''
}

const titolInforme = computed(() => {
  if (!informe.value) return ''
  const p = informe.value.periode
  switch (p.tipus) {
    case 'mensual':
      return `Informe mensual — ${p.etiqueta}`
    case 'trimestral':
      return `Informe trimestral — ${p.etiqueta}`
    case 'anual':
      return `Informe anual — ${p.etiqueta}`
    default:
      return `Informe — ${p.etiqueta}`
  }
})

function formatMoney(value: number): string {
  return new Intl.NumberFormat('ca-ES', { style: 'currency', currency: 'EUR' }).format(value)
}

async function carregarInforme() {
  loading.value = true
  error.value = ''
  informe.value = null

  try {
    switch (tipusInforme.value) {
      case 'mensual':
        informe.value = await getInformeMensual(anySeleccionat.value, mesSeleccionat.value)
        break
      case 'trimestral':
        informe.value = await getInformeTrimestral(anySeleccionat.value, trimestreSeleccionat.value)
        break
      case 'anual':
        informe.value = await getInformeAnual(anySeleccionat.value)
        break
    }
  } catch (err: any) {
    error.value = err?.response?.data?.message ?? 'No s\'ha pogut carregar l\'informe.'
  } finally {
    loading.value = false
  }
}

async function descarregarPdf() {
  descarregant.value = true
  error.value = ''

  try {
    let periode: string
    switch (tipusInforme.value) {
      case 'mensual':
        periode = `${anySeleccionat.value}-${String(mesSeleccionat.value).padStart(2, '0')}`
        break
      case 'trimestral':
        periode = `${anySeleccionat.value}-${trimestreSeleccionat.value}`
        break
      case 'anual':
        periode = `${anySeleccionat.value}`
        break
    }
    await descarregarInformePdf(tipusInforme.value, periode)
  } catch (err: any) {
    error.value = err?.response?.data?.message ?? 'No s\'ha pogut descarregar el PDF.'
  } finally {
    descarregant.value = false
  }
}

// Carregar informe del mes actual automàticament
runInitialLoad(() => carregarInforme())
</script>
