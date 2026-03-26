<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Factures</h2>
      <div class="flex items-center gap-2">
        <RouterLink
          to="/plantilles"
          class="px-4 py-2 rounded border hover:bg-gray-50"
        >
          Des de plantilla
        </RouterLink>
        <RouterLink
          to="/factures/nova"
          class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
        >
          Nova factura
        </RouterLink>
      </div>
    </div>

    <!-- Div de càrrega  de la pagina-->
    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
        <div class="h-10 w-24 rounded bg-gray-200"></div>
      </section>

      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4"><div class="h-4 w-14 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-14 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-16 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-14 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-14 rounded bg-gray-200"></div></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in 6" :key="row" class="border-b">
                <td class="py-2 pr-4"><div class="h-4 w-24 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-40 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-20 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-20 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-24 rounded bg-gray-200"></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Cerca</span>
            <input
              v-model="filters.search"
              type="text"
              class="border rounded px-3 py-2 w-full"
              placeholder="Número o notes"
              @keyup.enter="loadFactures(1)"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Estat</span>
            <select v-model="filters.estat" class="border rounded px-3 py-2 w-full">
              <option value="">Tots els estats</option>
              <option value="esborrany">Esborrany</option>
              <option value="emesa">Emesa</option>
              <option value="cancel·lada">Cancel·lada</option>
              <option value="cobrada">Cobrada</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Client</span>
            <select v-model.number="filters.client_id" class="border rounded px-3 py-2 w-full">
              <option :value="0">Tots els clients</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">
                {{ client.nom }} {{ client.cognoms ?? '' }}
              </option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data des de</span>
            <input v-model="filters.data_desde" type="date" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data fins</span>
            <input v-model="filters.data_fins" type="date" class="border rounded px-3 py-2 w-full" />
          </label>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
            @click="loadFactures(1)"
          >
            Cercar
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded border"
            @click="resetFilters"
          >
            Netejar
          </button>
        </div>

        <p v-if="listError" class="text-sm text-red-600">{{ listError }}</p>
      </section>

      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Número</th>
                <th class="py-2 pr-4">Client</th>
                <th class="py-2 pr-4">Data</th>
                <th class="py-2 pr-4">Estat</th>
                <th class="py-2 pr-4">Total</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="factura in factures" :key="factura.id" class="border-b">
                <td class="py-2 pr-4">{{ factura.numero_factura }}</td>
                <td class="py-2 pr-4">{{ getClientLabel(factura.client_id) }}</td>
                <td class="py-2 pr-4">{{ factura.data_emisio }}</td>
                <td class="py-2 pr-4">
                  <span class="capitalize">{{ factura.estat }}</span>
                </td>
                <td class="py-2 pr-4">{{ Number(factura.total).toFixed(2) }} €</td>
                <td class="py-2 pr-4 flex flex-wrap gap-2">
                  <RouterLink :to="`/factures/${factura.id}`" class="text-blue-600 hover:underline">
                    Veure
                  </RouterLink>
                  <button type="button" class="text-gray-700 hover:underline" @click="handleDescarregarPdf(factura.id)">
                    PDF
                  </button>
                  <RouterLink :to="`/factures/${factura.id}/editar`" class="text-gray-700 hover:underline">
                    Editar
                  </RouterLink>
                  <button type="button" class="text-red-600 hover:underline" @click="handleDelete(factura.id)">
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && factures.length === 0">
                <td colspan="6" class="py-4 text-gray-500">No hi ha factures</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Pàgina {{ meta.page }} de {{ meta.total_pages || 1 }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page <= 1"
              @click="loadFactures(meta.page - 1)"
            >
              Anterior
            </button>
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page >= (meta.total_pages || 1)"
              @click="loadFactures(meta.page + 1)"
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
import { getClients, type Client } from '@/services/clients'
import { descarregarFacturaPdf, deleteFactura, getFactures, type Factura } from '@/services/factures'

const loading = ref(false)
const listError = ref('')
const factures = ref<Factura[]>([])
const clients = ref<Client[]>([])

const filters = reactive({
  search: '',
  estat: '',
  client_id: 0,
  data_desde: '',
  data_fins: ''
})

const meta = reactive({
  page: 1,
  limit: 10,
  total: 0,
  total_pages: 1
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function getClientLabel(clientId: number) {
  const client = clients.value.find((item) => item.id === clientId)
  if (!client) {
    return `Client #${clientId}`
  }

  return `${client.nom} ${client.cognoms ?? ''}`.trim()
}

function resetFilters() {
  filters.search = ''
  filters.estat = ''
  filters.client_id = 0
  filters.data_desde = ''
  filters.data_fins = ''
  loadFactures(1)
}

async function loadClientsSelect() {
  try {
    const response = await getClients({ page: 1, limit: 100 })
    clients.value = response.data ?? []
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'han pogut carregar els clients.'
  }
}

async function loadFactures(page = 1) {
  loading.value = true
  listError.value = ''

  try {
    const response = await getFactures({
      page,
      limit: meta.limit,
      search: filters.search.trim() || undefined,
      estat: filters.estat || undefined,
      client_id: filters.client_id || undefined,
      data_desde: filters.data_desde || undefined,
      data_fins: filters.data_fins || undefined
    })

    factures.value = response.data ?? []

    const responseMeta = response.meta ?? {}
    meta.page = responseMeta.page ?? page
    meta.limit = responseMeta.limit ?? meta.limit
    meta.total = responseMeta.total ?? 0
    meta.total_pages = responseMeta.total_pages ?? 1
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'han pogut carregar les factures.'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number) {
  if (!window.confirm('Vols eliminar aquesta factura?')) {
    return
  }

  listError.value = ''

  try {
    await deleteFactura(id)
    await loadFactures(meta.page)
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut eliminar la factura.'
  }
}

async function handleDescarregarPdf(id: number) {
  listError.value = ''

  try {
    await descarregarFacturaPdf(id)
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut descarregar el PDF.'
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    await Promise.all([loadClientsSelect(), loadFactures(1)])
  })
})
</script>
