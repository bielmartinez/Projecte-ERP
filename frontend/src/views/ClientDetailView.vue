<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-semibold">Detall del client</h2>
      <RouterLink to="/clients" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </div>

    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

    <section v-if="client" class="bg-white rounded shadow p-6 space-y-3">
      <h3 class="text-lg font-semibold">Dades del client</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <p><strong>Nom:</strong> {{ client.nom }} {{ client.cognoms ?? '' }}</p>
        <p><strong>Empresa:</strong> {{ client.nom_empresa ?? '-' }}</p>
        <p><strong>NIF:</strong> {{ client.nif ?? '-' }}</p>
        <p><strong>Email:</strong> {{ client.email ?? '-' }}</p>
        <p><strong>Telèfon:</strong> {{ client.telefon ?? '-' }}</p>
        <p><strong>País:</strong> {{ client.pais ?? '-' }}</p>
        <p class="md:col-span-2"><strong>Adreça:</strong> {{ client.adreca ?? '-' }}</p>
        <p class="md:col-span-2"><strong>Notes:</strong> {{ client.notes ?? '-' }}</p>
      </div>
    </section>

    <section class="bg-white rounded shadow p-6 space-y-3">
      <h3 class="text-lg font-semibold">Factures associades</h3>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">Número</th>
              <th class="py-2 pr-4">Data emissió</th>
              <th class="py-2 pr-4">Estat</th>
              <th class="py-2 pr-4">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="factura in factures" :key="factura.id" class="border-b">
              <td class="py-2 pr-4">{{ factura.numero_factura }}</td>
              <td class="py-2 pr-4">{{ factura.data_emisio }}</td>
              <td class="py-2 pr-4">{{ factura.estat }}</td>
              <td class="py-2 pr-4">{{ factura.total }} €</td>
            </tr>
            <tr v-if="factures.length === 0">
              <td colspan="4" class="py-4 text-gray-500">Aquest client encara no té factures</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { getClient, type Client } from '@/services/clients'

interface FacturaClient {
  id: number
  numero_factura: string
  data_emisio: string
  estat: string
  total: string
}

const route = useRoute()

const client = ref<Client | null>(null)
const factures = ref<FacturaClient[]>([])
const error = ref('')

async function loadClient() {
  error.value = ''

  try {
    const response = await getClient(route.params.id as string)
    client.value = response?.data?.client ?? null
    factures.value = response?.data?.factures ?? []
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el client.'
  }
}

onMounted(() => {
  loadClient()
})
</script>
