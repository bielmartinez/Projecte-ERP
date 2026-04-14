<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Quotes</h2>
      <button
        type="button"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
        @click="prepareCreate"
      >
        Nova quota
      </button>
    </div>

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

      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-8 w-40 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="h-10 rounded bg-gray-200"></div>
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
                  <button type="button" class="text-gray-700 hover:underline" @click="prepareEdit(quota)">
                    Editar
                  </button>
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

      <section ref="formSection" class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">{{ editingId ? 'Editar quota' : 'Crear quota' }}</h3>

        <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
        <p v-if="formSuccess" class="text-sm text-green-600">{{ formSuccess }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Nom</span>
            <input
              v-model="form.nom"
              type="text"
              class="border rounded px-3 py-2 w-full"
              placeholder="Nom de la quota"
            />
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Descripció (opcional)</span>
            <input
              v-model="form.descripcio"
              type="text"
              class="border rounded px-3 py-2 w-full"
              placeholder="Descripció de la quota"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Import (€)</span>
            <input
              v-model.number="form.import"
              type="number"
              min="0.01"
              step="0.01"
              class="border rounded px-3 py-2 w-full"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Periodicitat</span>
            <select v-model="form.periodicitat" class="border rounded px-3 py-2 w-full">
              <option value="mensual">Mensual</option>
              <option value="trimestral">Trimestral</option>
              <option value="anual">Anual</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Dia de pagament</span>
            <input
              v-model.number="form.dia_pagament"
              type="number"
              min="1"
              max="31"
              class="border rounded px-3 py-2 w-full"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data inici</span>
            <input v-model="form.data_inici" type="date" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data fi (opcional)</span>
            <input v-model="form.data_fi" type="date" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Categoria</span>
            <select v-model.number="form.categoria_id" class="border rounded px-3 py-2 w-full">
              <option :value="0">Selecciona categoria</option>
              <option v-for="categoria in categories" :key="categoria.id" :value="categoria.id">
                {{ categoria.nom }}
              </option>
            </select>
          </label>

          <label class="inline-flex items-center gap-2 mt-7">
            <input v-model="form.activa" type="checkbox" class="h-4 w-4" />
            <span class="text-sm text-gray-700">Quota activa</span>
          </label>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
            :disabled="formLoading"
            @click="handleSubmit"
          >
            {{ formLoading ? 'Guardant...' : editingId ? 'Actualitzar' : 'Crear' }}
          </button>
          <button type="button" class="px-4 py-2 rounded border" :disabled="formLoading" @click="resetForm">
            Netejar
          </button>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { getCategories, type CategoriaMoviment } from '@/services/categories'
import {
  createQuota,
  deleteQuota,
  getQuotes,
  updateQuota,
  type Periodicitat,
  type Quota
} from '@/services/quotes'

const route = useRoute()

const loading = ref(false)
const listError = ref('')
const quotes = ref<Quota[]>([])
const categories = ref<CategoriaMoviment[]>([])

const filters = reactive({
  activa: 'totes'
})

const editingId = ref<number | null>(null)
const formLoading = ref(false)
const formError = ref('')
const formSuccess = ref('')
const formSection = ref<HTMLElement | null>(null)

const form = reactive({
  nom: '',
  descripcio: '',
  import: 0,
  periodicitat: 'mensual' as Periodicitat,
  dia_pagament: 1,
  data_inici: avui(),
  data_fi: '',
  categoria_id: 0,
  activa: true
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function avui() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

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

function normalitzarActiva(valor: unknown): boolean {
  if (typeof valor === 'boolean') {
    return valor
  }

  if (typeof valor === 'number') {
    return valor === 1
  }

  if (typeof valor === 'string') {
    return ['1', 'true', 't', 'on'].includes(valor.toLowerCase())
  }

  return false
}

function scrollForm() {
  nextTick(() => {
    formSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

function resetForm() {
  editingId.value = null
  form.nom = ''
  form.descripcio = ''
  form.import = 0
  form.periodicitat = 'mensual'
  form.dia_pagament = 1
  form.data_inici = avui()
  form.data_fi = ''
  form.categoria_id = 0
  form.activa = true
  formError.value = ''
  formSuccess.value = ''
}

function prepareCreate() {
  resetForm()
  scrollForm()
}

function prepareEdit(quota: Quota) {
  editingId.value = quota.id
  form.nom = quota.nom
  form.descripcio = quota.descripcio ?? ''
  form.import = Number(quota.import)
  form.periodicitat = quota.periodicitat
  form.dia_pagament = Number(quota.dia_pagament)
  form.data_inici = quota.data_inici
  form.data_fi = quota.data_fi ?? ''
  form.categoria_id = Number(quota.categoria_id)
  form.activa = normalitzarActiva(quota.activa)
  formError.value = ''
  formSuccess.value = ''
  scrollForm()
}

async function loadCategories() {
  const response = await getCategories({ page: 1, limit: 100, tipus: 'despesa' })
  categories.value = response?.data ?? []

  if (!editingId.value) {
    const catImpostos = categories.value.find(
      (c) => c.nom.toLowerCase().includes('impostos')
    )
    if (catImpostos) {
      form.categoria_id = catImpostos.id
    }
  }
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

async function handleSubmit() {
  formError.value = ''
  formSuccess.value = ''

  if (!form.nom.trim()) {
    formError.value = 'El nom és obligatori.'
    return
  }

  if (Number(form.import) <= 0) {
    formError.value = 'L\'import ha de ser major que 0.'
    return
  }

  if (Number(form.dia_pagament) < 1 || Number(form.dia_pagament) > 31) {
    formError.value = 'El dia de pagament ha d\'estar entre 1 i 31.'
    return
  }

  if (!form.data_inici) {
    formError.value = 'La data d\'inici és obligatòria.'
    return
  }

  if (!form.categoria_id) {
    formError.value = 'Has de seleccionar una categoria.'
    return
  }

  formLoading.value = true

  const payload = {
    nom: form.nom.trim(),
    descripcio: form.descripcio.trim() || undefined,
    import: Number(form.import),
    periodicitat: form.periodicitat,
    dia_pagament: Number(form.dia_pagament),
    data_inici: form.data_inici,
    data_fi: form.data_fi || null,
    categoria_id: Number(form.categoria_id),
    activa: Boolean(form.activa)
  }

  try {
    if (editingId.value) {
      await updateQuota(editingId.value, payload)
      formSuccess.value = 'Quota actualitzada correctament.'
    } else {
      await createQuota(payload)
      formSuccess.value = 'Quota creada correctament.'
      resetForm()
    }

    await loadQuotes()
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'No s\'ha pogut desar la quota.'
  } finally {
    formLoading.value = false
  }
}

async function handleDelete(id: number) {
  if (!window.confirm('Vols eliminar aquesta quota?')) {
    return
  }

  listError.value = ''

  try {
    await deleteQuota(id)
    await loadQuotes()
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut eliminar la quota.'
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    await Promise.all([loadCategories(), loadQuotes()])

    const editId = Number(route.query.edit ?? 0)
    if (editId > 0) {
      const quota = quotes.value.find((item) => item.id === editId)
      if (quota) {
        prepareEdit(quota)
      }
    }
  })
})
</script>
