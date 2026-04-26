<template>
  <div class="space-y-6">
    <PageHeader title="Plantilles">
      <RouterLink
        to="/plantilles/nova"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
      >
        Nova plantilla
      </RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div v-for="row in 5" :key="row" class="h-10 rounded bg-gray-200"></div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Cerca plantilla</span>
            <input
              v-model="filters.search"
              type="text"
              class="border rounded px-3 py-2 w-full"
              placeholder="Nom o descripció"
              @keyup.enter="loadPlantilles(1)"
            />
          </label>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
            @click="loadPlantilles(1)"
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

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </section>

      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Nom</th>
                <th class="py-2 pr-4">Línies</th>
                <th class="py-2 pr-4">IVA / IRPF</th>
                <th class="py-2 pr-4">Mètode pagament</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="plantilla in plantilles" :key="plantilla.id" class="border-b">
                <td class="py-2 pr-4">
                  <p class="font-medium">{{ plantilla.nom }}</p>
                  <p v-if="plantilla.descripcio" class="text-xs text-gray-500">{{ plantilla.descripcio }}</p>
                </td>
                <td class="py-2 pr-4">{{ plantilla.linies?.length ?? 0 }}</td>
                <td class="py-2 pr-4">
                  IVA {{ Number(plantilla.iva_percentatge ?? 0).toFixed(2) }}% ·
                  IRPF {{ Number(plantilla.irpf_percentatge ?? 0).toFixed(2) }}%
                </td>
                <td class="py-2 pr-4">{{ plantilla.metode_pagament || '-' }}</td>
                <td class="py-2 pr-4 flex flex-wrap gap-2">
                  <LlistaFacturesAction label="Crear factura" variant="primary" @click="handleCrearFactura(plantilla.id)" />
                  <LlistaFacturesAction label="Editar" :to="`/plantilles/${plantilla.id}/editar`" variant="neutral" />
                  <LlistaFacturesAction label="Eliminar" variant="danger" @click="handleDelete(plantilla.id)" />
                </td>
              </tr>
              <tr v-if="!loading && plantilles.length === 0">
                <td colspan="5" class="py-4 text-gray-500">No hi ha plantilles</td>
              </tr>
            </tbody>
          </table>
        </div>

        <PaginationControls :page="meta.page" :total-pages="meta.total_pages" @canvia-pagina="loadPlantilles" />
      </section>
    </template>

    <ConfirmModal
      :visible="showDeleteModal"
      title="Eliminar plantilla"
      message="Estàs segur que vols eliminar aquesta plantilla? Aquesta acció no es pot desfer."
      confirm-text="Eliminar"
      @confirma="confirmDelete"
      @cancel·la="cancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import ConfirmModal from '@/components/ConfirmModal.vue'
import LlistaFacturesAction from '@/components/LlistaFacturesAction.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationControls from '@/components/PaginationControls.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import { deletePlantilla, getPlantilles, type Plantilla } from '@/services/plantilles'

const router = useRouter()

const loading = ref(false)
const error = ref('')
const plantilles = ref<Plantilla[]>([])
const showDeleteModal = ref(false)
const deletingId = ref<number | null>(null)

const filters = reactive({
  search: ''
})

const meta = reactive({
  page: 1,
  limit: 10,
  total: 0,
  total_pages: 1
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function resetFilters() {
  filters.search = ''
  loadPlantilles(1)
}

async function loadPlantilles(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const response = await getPlantilles({
      page,
      limit: meta.limit,
      search: filters.search.trim() || undefined
    })

    plantilles.value = response.data ?? []

    const responseMeta = response.meta ?? {}
    meta.page = responseMeta.page ?? page
    meta.limit = responseMeta.limit ?? meta.limit
    meta.total = responseMeta.total ?? 0
    meta.total_pages = responseMeta.total_pages ?? 1
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'han pogut carregar les plantilles.'
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

  error.value = ''

  try {
    await deletePlantilla(deletingId.value)
    await loadPlantilles(meta.page)
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut eliminar la plantilla.'
  }

  deletingId.value = null
}

function cancelDelete() {
  showDeleteModal.value = false
  deletingId.value = null
}

async function handleCrearFactura(id: number) {
  await router.push({
    path: '/factures/nova',
    query: {
      plantilla_id: String(id)
    }
  })
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      await loadPlantilles(1)
    } catch (requestError: any) {
      error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar la pantalla de plantilles.'
    }
  })
})
</script>
