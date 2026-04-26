<template>
  <div class="space-y-6">
    <PageHeader title="Quotes">
      <RouterLink
        to="/quotes/nova"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
      >
        Nova quota
      </RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-4" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="h-8 w-36 rounded bg-gray-200"></div>
        <div class="h-10 w-52 rounded bg-gray-200"></div>
        <div class="space-y-2">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="flex items-end gap-3">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Filtre estat</span>
            <select v-model="filters.activa" class="border rounded px-3 py-2 w-full">
              <option value="totes">Totes</option>
              <option value="1">Actives</option>
              <option value="0">Inactives</option>
            </select>
          </label>

          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
            @click="loadQuotes"
          >
            Filtrar
          </button>
        </div>

        <p v-if="listError" class="text-sm text-red-600">{{ listError }}</p>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Nom</th>
                <th class="py-2 pr-4">Import (€)</th>
                <th class="py-2 pr-4">Periodicitat</th>
                <th class="py-2 pr-4">Dia pagament</th>
                <th class="py-2 pr-4">Pendents</th>
                <th class="py-2 pr-4">Proper venciment</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="quota in quotes" :key="quota.id" class="border-b">
                <td class="py-2 pr-4 font-medium">{{ quota.nom }}</td>
                <td class="py-2 pr-4">{{ Number(quota.import).toFixed(2) }} €</td>
                <td class="py-2 pr-4">{{ labelPeriodicitat(quota.periodicitat) }}</td>
                <td class="py-2 pr-4">{{ quota.dia_pagament }}</td>
                <td class="py-2 pr-4">
                  <span
                    v-if="Number(quota.periodes_pendents_count ?? 0) > 0"
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                  >
                    {{ quota.periodes_pendents_count }} pendents
                  </span>
                  <span
                    v-else
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                  >
                    Al dia
                  </span>
                </td>
                <td class="py-2 pr-4">
                  {{ quota.proper_venciment ? formatPeriode(quota.proper_venciment) : '-' }}
                </td>
                <td class="py-2 pr-4 flex flex-wrap gap-2">
                  <RouterLink :to="`/quotes/${quota.id}`" class="text-blue-600 hover:underline">Veure</RouterLink>
                  <RouterLink :to="`/quotes/${quota.id}/editar`" class="text-gray-700 hover:underline">
                    Editar
                  </RouterLink>
                  <button type="button" class="text-red-600 hover:underline" @click="handleDelete(quota.id)">
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && quotes.length === 0">
                <td colspan="7" class="py-4 text-gray-500">No hi ha quotes</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>

    <ConfirmModal
      :visible="showDeleteModal"
      title="Eliminar quota"
      message="Estàs segur que vols eliminar aquesta quota? Aquesta acció no es pot desfer."
      confirm-text="Eliminar"
      @confirma="confirmDelete"
      @cancel·la="cancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import ConfirmModal from '@/components/ConfirmModal.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import {
  deleteQuota,
  getQuotes,
  type Periodicitat,
  type Quota
} from '@/services/quotes'

const loading = ref(false)
const listError = ref('')
const quotes = ref<Quota[]>([])
const showDeleteModal = ref(false)
const deletingId = ref<number | null>(null)

const filters = reactive({
  activa: 'totes'
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function formatPeriode(dateStr: string): string {
  const mesos = ['Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny', 'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre']
  const d = new Date(dateStr + 'T00:00:00')
  if (Number.isNaN(d.getTime())) {
    return dateStr
  }
  return `${mesos[d.getMonth()]} ${d.getFullYear()}`
}

function labelPeriodicitat(periodicitat: Periodicitat): string {
  if (periodicitat === 'trimestral') {
    return 'Trimestral'
  }

  if (periodicitat === 'anual') {
    return 'Anual'
  }

  return 'Mensual'
}

async function loadQuotes() {
  loading.value = true
  listError.value = ''

  const params: { activa?: boolean } = {}
  if (filters.activa === '1') {
    params.activa = true
  }
  if (filters.activa === '0') {
    params.activa = false
  }

  try {
    const response = await getQuotes(params)
    quotes.value = response?.data ?? []
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'han pogut carregar les quotes.'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number) {
  deletingId.value = id
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!deletingId.value) {
    return
  }

  showDeleteModal.value = false

  listError.value = ''

  try {
    await deleteQuota(deletingId.value)
    await loadQuotes()
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut eliminar la quota.'
  }

  deletingId.value = null
}

function cancelDelete() {
  showDeleteModal.value = false
  deletingId.value = null
}

onMounted(() => {
  runInitialLoad(async () => {
    await loadQuotes()
  })
})
</script>
