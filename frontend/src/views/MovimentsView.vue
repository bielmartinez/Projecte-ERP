<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Moviments</h2>
      <RouterLink
        to="/moviments/nou"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
      >
        Nou moviment
      </RouterLink>
    </div>

    <div v-if="initialLoading" class="space-y-4" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Cerca</span>
            <input
              v-model="filters.search"
              type="text"
              class="border rounded px-3 py-2 w-full"
              placeholder="Descripció o categoria"
              @keyup.enter="loadMoviments(1)"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Tipus</span>
            <select v-model="filters.tipus" class="border rounded px-3 py-2 w-full">
              <option value="">Tots els tipus</option>
              <option value="ingres">Ingrés</option>
              <option value="despesa">Despesa</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Categoria</span>
            <select v-model.number="filters.categoria_id" class="border rounded px-3 py-2 w-full">
              <option :value="0">Totes les categories</option>
              <option v-for="categoria in categoriesForFilter" :key="categoria.id" :value="categoria.id">
                {{ categoria.nom }}
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
            @click="loadMoviments(1)"
          >
            Cercar
          </button>
          <button type="button" class="px-4 py-2 rounded border" @click="resetFilters">Netejar</button>
        </div>

        <p v-if="listError" class="text-sm text-red-600">{{ listError }}</p>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Data</th>
                <th class="py-2 pr-4">Tipus</th>
                <th class="py-2 pr-4">Categoria</th>
                <th class="py-2 pr-4">Descripció</th>
                <th class="py-2 pr-4">Import</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="moviment in moviments" :key="moviment.id" class="border-b">
                <td class="py-2 pr-4">{{ moviment.data }}</td>
                <td class="py-2 pr-4">
                  <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                    :class="moviment.tipus === 'ingres' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ moviment.tipus === 'ingres' ? 'Ingrés' : 'Despesa' }}
                  </span>
                </td>
                <td class="py-2 pr-4">{{ moviment.categoria_nom ?? '-' }}</td>
                <td class="py-2 pr-4">{{ moviment.descripcio }}</td>
                <td class="py-2 pr-4 font-medium" :class="moviment.tipus === 'ingres' ? 'text-green-700' : 'text-red-700'">
                  {{ moviment.tipus === 'ingres' ? '+' : '-' }}{{ Number(moviment.import).toFixed(2) }} €
                </td>
                <td class="py-2 pr-4 flex gap-2">
                  <RouterLink :to="`/moviments/${moviment.id}/editar`" class="text-gray-700 hover:underline">
                    Editar
                  </RouterLink>
                  <button type="button" class="text-red-600 hover:underline" @click="handleDelete(moviment.id)">
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && moviments.length === 0">
                <td colspan="6" class="py-4 text-gray-500">No hi ha moviments</td>
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
              @click="loadMoviments(meta.page - 1)"
            >
              Anterior
            </button>
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page >= (meta.total_pages || 1)"
              @click="loadMoviments(meta.page + 1)"
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { getCategories, type CategoriaMoviment, type TipusMoviment } from '@/services/categories'
import { deleteMoviment, getMoviments, type Moviment } from '@/services/moviments'

const loading = ref(false)
const listError = ref('')
const moviments = ref<Moviment[]>([])
const categories = ref<CategoriaMoviment[]>([])

const filters = reactive({
  search: '',
  tipus: '',
  categoria_id: 0,
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

const categoriesForFilter = computed(() => {
  if (!filters.tipus) {
    return categories.value
  }

  return categories.value.filter((categoria) => categoria.tipus === filters.tipus)
})

watch(
  () => filters.tipus,
  () => {
    const stillValid = categoriesForFilter.value.some((categoria) => categoria.id === filters.categoria_id)
    if (!stillValid) {
      filters.categoria_id = 0
    }
  }
)

function resetFilters() {
  filters.search = ''
  filters.tipus = ''
  filters.categoria_id = 0
  filters.data_desde = ''
  filters.data_fins = ''
  loadMoviments(1)
}

async function loadCategoriesCatalog() {
  const response = await getCategories({ page: 1, limit: 100 })
  categories.value = response.data ?? []
}

async function loadMoviments(page = 1) {
  loading.value = true
  listError.value = ''

  try {
    const response = await getMoviments({
      page,
      limit: meta.limit,
      search: filters.search.trim() || undefined,
      tipus: (filters.tipus || undefined) as TipusMoviment | undefined,
      categoria_id: filters.categoria_id || undefined,
      data_desde: filters.data_desde || undefined,
      data_fins: filters.data_fins || undefined
    })

    moviments.value = response.data ?? []

    const responseMeta = response.meta ?? {}
    meta.page = responseMeta.page ?? page
    meta.limit = responseMeta.limit ?? meta.limit
    meta.total = responseMeta.total ?? 0
    meta.total_pages = responseMeta.total_pages ?? 1
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'han pogut carregar els moviments.'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number) {
  if (!window.confirm('Vols eliminar aquest moviment?')) {
    return
  }

  listError.value = ''

  try {
    await deleteMoviment(id)
    await loadMoviments(meta.page)
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut eliminar el moviment.'
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    await Promise.all([loadCategoriesCatalog(), loadMoviments(1)])
  })
})
</script>
