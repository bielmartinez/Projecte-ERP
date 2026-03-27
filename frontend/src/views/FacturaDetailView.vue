<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Detall de factura</h2>
      <div class="flex gap-2">
        <RouterLink to="/factures" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
        <RouterLink v-if="factura" :to="`/factures/${factura.id}/editar`" class="text-gray-700 hover:underline">
          Editar
        </RouterLink>
        <button
          v-if="factura"
          type="button"
          class="text-gray-700 hover:underline"
          @click="handleDescarregarPdf"
        >
          Descarregar PDF
        </button>
      </div>
    </div>

    <!-- Div de càrrega  de la pagina-->
    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-40 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="h-4 w-52 rounded bg-gray-200"></div>
          <div class="h-4 w-44 rounded bg-gray-200"></div>
          <div class="h-4 w-36 rounded bg-gray-200"></div>
          <div class="h-4 w-48 rounded bg-gray-200"></div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-44 rounded bg-gray-200"></div>
        <div v-for="row in 4" :key="row" class="h-10 rounded bg-gray-200"></div>
      </section>
    </div>

    <template v-else>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="text-sm text-green-600">{{ success }}</p>

      <section v-if="factura" class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Informació general</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <p><strong>Número:</strong> {{ factura.numero_factura }}</p>
          <p><strong>Client:</strong> {{ clientLabel }}</p>
          <p><strong>Data emissió:</strong> {{ factura.data_emisio }}</p>
          <p><strong>Data venciment:</strong> {{ factura.data_venciment ?? '-' }}</p>
          <p><strong>IVA:</strong> {{ Number(factura.iva_percentatge).toFixed(2) }} %</p>
          <p><strong>IRPF:</strong> {{ Number(factura.irpf_percentatge).toFixed(2) }} %</p>
          <p><strong>Subtotal:</strong> {{ Number(factura.subtotal).toFixed(2) }} €</p>
          <p><strong>IVA import:</strong> {{ Number(factura.iva_import).toFixed(2) }} €</p>
          <p><strong>IRPF import:</strong> {{ Number(factura.irpf_import).toFixed(2) }} €</p>
          <p><strong>Total:</strong> {{ Number(factura.total).toFixed(2) }} €</p>
          <p><strong>Mètode pagament:</strong> {{ factura.metode_pagament ?? '-' }}</p>
          <p><strong>Estat:</strong> {{ factura.estat }}</p>
          <p class="md:col-span-2"><strong>Notes:</strong> {{ factura.notes ?? '-' }}</p>
        </div>

        <div class="flex items-center gap-2">
          <label for="estat" class="text-sm">Canviar estat:</label>
          <select id="estat" v-model="selectedEstat" class="border rounded px-3 py-2 text-sm">
            <option value="esborrany">Esborrany</option>
            <option value="emesa">Emesa</option>
            <option value="cancel·lada">Cancel·lada</option>
            <option value="cobrada">Cobrada</option>
          </select>
          <button
            type="button"
            class="px-3 py-2 border rounded text-sm disabled:opacity-50"
            :disabled="changingEstat"
            @click="handleCanviarEstat"
          >
            {{ changingEstat ? 'Actualitzant...' : 'Guardar estat' }}
          </button>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Línies</h3>
          <button
            v-if="factura"
            type="button"
            class="text-red-600 hover:underline text-sm"
            @click="handleDeleteFactura"
          >
            Eliminar factura
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Descripció</th>
                <th class="py-2 pr-4">Quantitat</th>
                <th class="py-2 pr-4">Preu</th>
                <th class="py-2 pr-4">IVA %</th>
                <th class="py-2 pr-4">Desc %</th>
                <th class="py-2 pr-4">Total línia</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="linia in linies" :key="linia.id" class="border-b">
                <td class="py-2 pr-4">{{ linia.descripcio }}</td>
                <td class="py-2 pr-4">{{ Number(linia.quantitat).toFixed(3) }}</td>
                <td class="py-2 pr-4">{{ Number(linia.preu_unitari).toFixed(2) }} €</td>
                <td class="py-2 pr-4">{{ Number(linia.iva_percentatge).toFixed(2) }} %</td>
                <td class="py-2 pr-4">{{ Number(linia.descompte).toFixed(2) }} %</td>
                <td class="py-2 pr-4">{{ Number(linia.total_linia).toFixed(2) }} €</td>
              </tr>
              <tr v-if="linies.length === 0">
                <td colspan="6" class="py-4 text-gray-500">Aquesta factura no té línies</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { canviarEstatFactura, descarregarFacturaPdf, deleteFactura, getFactura, type Factura, type FacturaLinia } from '@/services/factures'

const route = useRoute()
const router = useRouter()

const factura = ref<Factura | null>(null)
const linies = ref<FacturaLinia[]>([])
const client = ref<any>(null)

const error = ref('')
const success = ref('')
const selectedEstat = ref<'esborrany' | 'emesa' | 'cancel·lada' | 'cobrada'>('esborrany')
const changingEstat = ref(false)

const { initialLoading, runInitialLoad } = useInitialLoading()

const clientLabel = computed(() => {
  if (!client.value) {
    return '-'
  }

  return `${client.value.nom ?? ''} ${client.value.cognoms ?? ''}`.trim()
})

async function loadFactura() {
  error.value = ''

  try {
    const response = await getFactura(route.params.id as string)
    factura.value = response?.data?.factura ?? null
    linies.value = response?.data?.linies ?? []
    client.value = response?.data?.client ?? null

    if (factura.value) {
      selectedEstat.value = factura.value.estat
    }
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar la factura.'
  }
}

async function handleCanviarEstat() {
  if (!factura.value) {
    return
  }

  changingEstat.value = true
  error.value = ''
  success.value = ''

  try {
    await canviarEstatFactura(factura.value.id, selectedEstat.value)
    success.value = 'Estat actualitzat correctament.'
    await loadFactura()
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut canviar l\'estat.'
  } finally {
    changingEstat.value = false
  }
}

async function handleDeleteFactura() {
  if (!factura.value) {
    return
  }

  if (!window.confirm('Vols eliminar aquesta factura?')) {
    return
  }

  error.value = ''

  try {
    await deleteFactura(factura.value.id)
    await router.push('/factures')
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut eliminar la factura.'
  }
}

async function handleDescarregarPdf() {
  if (!factura.value) {
    return
  }

  error.value = ''

  try {
    await descarregarFacturaPdf(factura.value.id)
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut descarregar el PDF.'
  }
}

onMounted(() => {
  runInitialLoad(loadFactura)
})
</script>
