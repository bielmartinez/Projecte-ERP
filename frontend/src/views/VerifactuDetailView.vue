<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4 mb-6">
      <h2 class="text-2xl font-semibold">Detall registre Verifactu</h2>
      <RouterLink to="/verifactu" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </div>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-48 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="index in 10" :key="`sk-info-${index}`" class="h-4 rounded bg-gray-200"></div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-52 rounded bg-gray-200"></div>
        <div class="h-16 rounded bg-gray-200"></div>
        <div class="h-16 rounded bg-gray-200"></div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-32 rounded bg-gray-200"></div>
        <div class="h-4 w-full rounded bg-gray-200"></div>
      </section>
    </div>

    <template v-else>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <template v-if="registre">
        <section class="bg-white rounded shadow p-6 space-y-4">
          <h3 class="text-lg font-semibold">Informació general</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <p><strong>ID registre:</strong> {{ registre.id }}</p>
            <p>
              <strong>Tipus registre:</strong>
              <span
                class="inline-block px-2 py-0.5 rounded text-xs font-medium ml-2"
                :class="registre.tipus_registre === 'alta' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ registre.tipus_registre }}
              </span>
            </p>
            <p>
              <strong>Factura:</strong>
              <RouterLink :to="`/factures/${registre.factura_id}`" class="text-blue-600 hover:underline ml-1">
                {{ registre.numero_factura }}
              </RouterLink>
            </p>
            <p><strong>NIF emissor:</strong> {{ registre.nif_emisor }}</p>
            <p><strong>Nom/Raó social emissor:</strong> {{ registre.nom_rao_emisor }}</p>
            <p><strong>Data emissió:</strong> {{ formatData(registre.data_emisio) }}</p>
            <p><strong>Tipus factura:</strong> {{ registre.tipus_factura }}</p>
            <p><strong>Quota total (IVA):</strong> {{ formatMoneda(registre.quota_total) }}</p>
            <p><strong>Import total:</strong> {{ formatMoneda(registre.import_total) }}</p>
            <p><strong>Data/hora generació:</strong> {{ formatDataHora(registre.data_hora_generacio) }}</p>
            <p><strong>Data creació registre:</strong> {{ formatDataHora(registre.created_at) }}</p>
          </div>
        </section>

        <section class="bg-white rounded shadow p-6 space-y-4 mt-6">
          <h3 class="text-lg font-semibold">Hash i encadenament</h3>

          <div class="space-y-2">
            <p class="text-sm font-medium">Hash del registre</p>
            <div class="bg-gray-50 p-3 rounded font-mono text-sm break-all">{{ registre.hash_registre }}</div>
          </div>

          <div class="space-y-2">
            <p class="text-sm font-medium">Hash anterior</p>
            <div v-if="registre.hash_anterior" class="bg-gray-50 p-3 rounded font-mono text-sm break-all">
              {{ registre.hash_anterior }}
            </div>
            <p v-else class="text-sm text-gray-600">Primer registre de la cadena</p>
          </div>

          <div v-if="registre.hash_anterior" class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <p><strong>NIF emissor anterior:</strong> {{ registre.nif_emisor_anterior ?? '-' }}</p>
            <p><strong>Número factura anterior:</strong> {{ registre.numero_factura_anterior ?? '-' }}</p>
            <p><strong>Data emissió anterior:</strong> {{ formatData(registre.data_emisio_anterior) }}</p>
          </div>
        </section>

        <section class="bg-white rounded shadow p-6 space-y-4 mt-6">
          <h3 class="text-lg font-semibold">Codi QR</h3>

          <p v-if="registre.codi_qr">
            <a :href="registre.codi_qr" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">
              {{ registre.codi_qr }}
            </a>
          </p>
          <p v-else class="text-sm text-gray-600">Aquest registre no té URL de validació QR.</p>

          <p class="text-xs text-gray-500">URL de validació en entorn de preproducció de l'AEAT.</p>
        </section>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { getRegistreVerifactu, type RegistreVerifactu } from '@/services/verifactu'

const route = useRoute()

const registre = ref<RegistreVerifactu | null>(null)
const error = ref('')

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

async function carregarRegistre() {
  const resposta = await getRegistreVerifactu(route.params.id as string)

  if (resposta.status !== 'ok') {
    throw new Error(resposta.message ?? 'No s\'ha pogut carregar el registre Verifactu.')
  }

  registre.value = resposta.data ?? null
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      await carregarRegistre()
    } catch (requestError: any) {
      error.value = requestError?.message ?? requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el registre Verifactu.'
    }
  })
})
</script>
