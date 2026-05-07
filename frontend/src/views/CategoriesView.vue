<template>
  <div class="space-y-6">
    <PageHeader title="Categories">
      <RouterLink to="/categories/nova" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover">
        Nova categoria
      </RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-4" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
        <div class="h-10 w-24 rounded bg-gray-200"></div>
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
              class="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              placeholder="Nom de categoria"
              @keyup.enter="loadCategories(1)"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Tipus</span>
            <select
              v-model="filters.tipus"
              class="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
            >
              <option value="">Tots els tipus</option>
              <option value="ingres">Ingrés</option>
              <option value="despesa">Despesa</option>
            </select>
          </label>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover"
            @click="loadCategories(1)"
          >
            Cercar
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded border border-primary text-primary hover:bg-primary-light"
            @click="resetFilters"
          >
            Netejar
          </button>
        </div>

        <p v-if="listError" class="text-sm text-danger">{{ listError }}</p>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="py-3 pr-4 text-xs font-semibold uppercase tracking-wide text-gray-600">Nom</th>
                <th class="py-3 pr-4 text-xs font-semibold uppercase tracking-wide text-gray-600">Tipus</th>
                <th class="py-3 pr-4 text-xs font-semibold uppercase tracking-wide text-gray-600">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="categoria in categories" :key="categoria.id" class="border-b hover:bg-gray-50 transition-colors">
                <td class="py-2 pr-4">{{ categoria.nom }}</td>
                <td class="py-2 pr-4 capitalize">{{ categoria.tipus }}</td>
                <td class="py-2 pr-4 flex gap-2">
                  <RouterLink :to="`/categories/${categoria.id}/editar`" class="text-primary hover:underline">
                    Editar
                  </RouterLink>
                  <button type="button" class="text-danger hover:underline" @click="handleDelete(categoria.id)">
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && categories.length === 0">
                <td colspan="3" class="py-4 text-gray-500">No hi ha categories</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Pàgina {{ meta.page }} de {{ meta.total_pages || 1 }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors disabled:opacity-50"
              :disabled="meta.page <= 1"
              @click="loadCategories(meta.page - 1)"
            >
              Anterior
            </button>
            <button
              type="button"
              class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors disabled:opacity-50"
              :disabled="meta.page >= (meta.total_pages || 1)"
              @click="loadCategories(meta.page + 1)"
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

import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import { deleteCategoria, getCategories, type CategoriaMoviment, type TipusMoviment } from '@/services/categories'

const loading = ref(false)
const listError = ref('')
const categories = ref<CategoriaMoviment[]>([])

const filters = reactive({
  search: '',
  tipus: ''
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
  filters.tipus = ''
  loadCategories(1)
}

async function loadCategories(page = 1) {
  loading.value = true
  listError.value = ''

  try {
    const response = await getCategories({
      page,
      limit: meta.limit,
      search: filters.search.trim() || undefined,
      tipus: (filters.tipus || undefined) as TipusMoviment | undefined
    })

    categories.value = response.data ?? []

    const responseMeta = response.meta ?? {}
    meta.page = responseMeta.page ?? page
    meta.limit = responseMeta.limit ?? meta.limit
    meta.total = responseMeta.total ?? 0
    meta.total_pages = responseMeta.total_pages ?? 1
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'han pogut carregar les categories.'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number) {
  if (!window.confirm('Vols eliminar aquesta categoria?')) {
    return
  }

  listError.value = ''

  try {
    await deleteCategoria(id)
    await loadCategories(meta.page)
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut eliminar la categoria.'
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    await loadCategories(1)
  })
})
</script>
