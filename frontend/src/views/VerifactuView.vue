<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4 mb-6">
      <h2 class="text-2xl font-semibold">Registres Verifactu</h2>
      <div class="flex gap-2">
        <button
          type="button"
          class="px-4 py-2 border rounded text-sm disabled:opacity-50"
          :disabled="validacioLoading"
          @click="handleValidar"
        >
          {{ validacioLoading ? 'Validant...' : 'Validar cadena' }}
        </button>
        <button
          type="button"
          class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-800 disabled:opacity-50"
          :disabled="exportarLoading"
          @click="handleExportar"
        >
          {{ exportarLoading ? 'Exportant...' : 'Exportar registres' }}
        </button>
      </div>
    </div>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-48 rounded bg-gray-200"></div>
        <div class="h-4 w-80 rounded bg-gray-200"></div>
        <div class="h-4 w-56 rounded bg-gray-200"></div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-40 rounded bg-gray-200"></div>
        <div class="h-10 rounded bg-gray-200"></div>
        <div class="h-10 rounded bg-gray-200"></div>
        <div class="h-10 rounded bg-gray-200"></div>
      </section>
    </div>

    <template v-else>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <section
        v-if="validacio"
        class="rounded-lg p-4"
        :class="validacio.valid ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'"
      >
        <p
          class="text-sm font-semibold"
          :class="validacio.valid ? 'text-green-700' : 'text-red-700'"
        >
          {{ validacio.valid ? 'Cadena íntegra' : 'Cadena compromesa' }}
        </p>
        <p class="text-sm mt-1" :class="validacio.valid ? 'text-green-700' : 'text-red-700'">
          Total de registres: {{ validacio.total_registres }}
        </p>

        <ul v-if="!validacio.valid && validacio.errors.length > 0" class="mt-2 space-y-1 text-sm text-red-700">
          <li v-for="item in validacio.errors" :key="`${item.registre_id}-${item.motiu}`">
            Registre #{{ item.registre_id }}: {{ item.motiu }}
          </li>
        </ul>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-4">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">#</th>
                <th class="py-2 pr-4">Factura</th>
                <th class="py-2 pr-4">Tipus</th>
                <th class="py-2 pr-4">Data</th>
                <th class="py-2 pr-4">Import</th>
                <th class="py-2 pr-4">Hash</th>
                <th class="py-2 pr-4">Estat factura</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="registre in registres" :key="registre.id" class="border-b">
                <td class="py-2 pr-4">{{ registre.id }}</td>
                <td class="py-2 pr-4">
                  <RouterLink :to="`/factures/${registre.factura_id}`" class="text-blue-600 hover:underline">
                    {{ registre.numero_factura }}
                  </RouterLink>
                </td>
                <td class="py-2 pr-4">
                  <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                    :class="registre.tipus_registre === 'alta' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  >
                    {{ registre.tipus_registre }}
                  </span>
                </td>
                <td class="py-2 pr-4">{{ formatDataHora(registre.data_hora_generacio) }}</td>
                <td class="py-2 pr-4">{{ formatMoneda(registre.import_total) }}</td>
                <td class="py-2 pr-4">
                  <span class="font-mono text-xs" :title="registre.hash_registre">
                    {{ hashCurt(registre.hash_registre) }}
                  </span>
                </td>
                <td class="py-2 pr-4">
                  <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium capitalize"
                    :class="classeEstat(registre.estat_factura_actual)"
                  >
                    {{ registre.estat_factura_actual ?? '-' }}
                  </span>
                </td>
                <td class="py-2 pr-4">
                  <RouterLink :to="`/verifactu/${registre.id}`" class="text-blue-600 hover:underline">
                    Detall
                  </RouterLink>
                </td>
              </tr>

              <tr v-if="registres.length === 0">
                <td colspan="8" class="py-4 text-gray-500">No hi ha registres Verifactu</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="meta.total_pages > 1" class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Pàgina {{ meta.page }} de {{ meta.total_pages }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page <= 1 || pageLoading"
              @click="handlePagina(meta.page - 1)"
            >
              Anterior
            </button>
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page >= meta.total_pages || pageLoading"
              @click="handlePagina(meta.page + 1)"
            >
              Següent
            </button>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import {
  exportarRegistresVerifactu,
  getRegistresVerifactu,
  validarCadenaVerifactu,
  type RegistreVerifactu,
  type ValidacioCadena
} from '@/services/verifactu'

const registres = ref<RegistreVerifactu[]>([])
const validacio = ref<ValidacioCadena | null>(null)
const error = ref('')
const validacioLoading = ref(false)
const exportarLoading = ref(false)
const pageLoading = ref(false)

const meta = reactive({
  page: 1,
  limit: 20,
  total: 0,
  total_pages: 1
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function formatData(data: string | null | undefined): string {
  if (!data) {
    return '-'
  }

  const parts = String(data).split('-')
  if (parts.length !== 3) {
    return String(data)
  }

  return `${parts[2]}/${parts[1]}/${parts[0]}`
}

function formatMoneda(valor: number | string | null | undefined): string {
  return Number(valor ?? 0).toFixed(2) + ' €'
}

function formatDataHora(dataHora: string | null | undefined): string {
  if (!dataHora) {
    return '-'
  }

  let normalitzada = String(dataHora).trim().replace(' ', 'T')
  if (/[+-]\d{2}$/.test(normalitzada)) {
    normalitzada += ':00'
  }

  const data = new Date(normalitzada)
  if (Number.isNaN(data.getTime())) {
    return formatData(normalitzada.split('T')[0])
  }

  return data.toLocaleString('ca-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

function hashCurt(hash: string): string {
  const valor = String(hash ?? '')
  if (valor.length <= 16) {
    return valor
  }

  return `${valor.slice(0, 16)}...`
}

function classeEstat(estat: string | null): string {
  if (estat === 'emesa') {
    return 'bg-blue-100 text-blue-800'
  }

  if (estat === 'cobrada') {
    return 'bg-green-100 text-green-800'
  }

  if (estat === 'cancel·lada') {
    return 'bg-red-100 text-red-800'
  }

  if (estat === 'parcialment_cobrada') {
    return 'bg-yellow-100 text-yellow-800'
  }

  return 'bg-gray-100 text-gray-700'
}

async function carregarRegistres(page = 1) {
  const resposta = await getRegistresVerifactu({ page, limit: meta.limit })

  if (resposta.status !== 'ok') {
    throw new Error(resposta.message ?? 'No s\'han pogut carregar els registres Verifactu.')
  }

  registres.value = resposta.data ?? []
  meta.page = Number(resposta.meta?.page ?? page)
  meta.limit = Number(resposta.meta?.limit ?? meta.limit)
  meta.total = Number(resposta.meta?.total ?? 0)
  meta.total_pages = Number(resposta.meta?.total_pages ?? 1)
}

async function carregarValidacio() {
  const resposta = await validarCadenaVerifactu()

  if (resposta.status !== 'ok') {
    throw new Error(resposta.message ?? 'No s\'ha pogut validar la cadena Verifactu.')
  }

  validacio.value = resposta.data
}

async function handleValidar() {
  validacioLoading.value = true
  error.value = ''

  try {
    await carregarValidacio()
  } catch (requestError: any) {
    error.value = requestError?.message ?? requestError?.response?.data?.message ?? 'No s\'ha pogut validar la cadena Verifactu.'
  } finally {
    validacioLoading.value = false
  }
}

async function handleExportar() {
  exportarLoading.value = true
  error.value = ''

  try {
    await exportarRegistresVerifactu()
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'han pogut exportar els registres Verifactu.'
  } finally {
    exportarLoading.value = false
  }
}

async function handlePagina(page: number) {
  if (page < 1 || page > meta.total_pages || page === meta.page) {
    return
  }

  pageLoading.value = true
  error.value = ''

  try {
    await carregarRegistres(page)
  } catch (requestError: any) {
    error.value = requestError?.message ?? requestError?.response?.data?.message ?? 'No s\'han pogut carregar els registres Verifactu.'
  } finally {
    pageLoading.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    const [resRegistres, resValidacio] = await Promise.allSettled([
      carregarRegistres(meta.page),
      carregarValidacio()
    ])

    if (resRegistres.status === 'rejected') {
      error.value =
        resRegistres.reason?.message ??
        resRegistres.reason?.response?.data?.message ??
        'No s\'han pogut carregar els registres Verifactu.'
    }

    if (resValidacio.status === 'rejected' && !error.value) {
      error.value =
        resValidacio.reason?.message ??
        resValidacio.reason?.response?.data?.message ??
        'No s\'ha pogut validar la cadena Verifactu.'
    }
  })
})
</script>
