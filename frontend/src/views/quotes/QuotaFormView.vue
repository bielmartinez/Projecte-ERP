<template>
  <div class="space-y-6">
    <PageHeader :title="isEdit ? 'Editar quota' : 'Nova quota'">
      <RouterLink to="/quotes" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-4" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-8 w-40 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 w-32 rounded bg-gray-200"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-10 w-24 rounded bg-gray-200"></div>
          <div class="h-10 w-24 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">{{ isEdit ? 'Editar quota' : 'Crear quota' }}</h3>

        <FormMessages :error="formError" />

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
            {{ formLoading ? 'Guardant...' : isEdit ? 'Actualitzar' : 'Crear' }}
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
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import FormMessages from '@/components/FormMessages.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import { getCategories, type CategoriaMoviment } from '@/services/categories'
import {
  createQuota,
  getQuota,
  updateQuota,
  type Periodicitat,
  type QuotaPayload
} from '@/services/quotes'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))

const categories = ref<CategoriaMoviment[]>([])
const formLoading = ref(false)
const formError = ref('')

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

function resetForm() {
  if (isEdit.value) {
    return
  }

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

  const catImpostos = categories.value.find(
    (c) => c.nom.toLowerCase().includes('impostos')
  )
  if (catImpostos) {
    form.categoria_id = catImpostos.id
  }
}

async function loadCategories() {
  const response = await getCategories({ page: 1, limit: 100, tipus: 'despesa' })
  categories.value = response?.data ?? []

  if (!isEdit.value) {
    const catImpostos = categories.value.find(
      (c) => c.nom.toLowerCase().includes('impostos')
    )
    if (catImpostos) {
      form.categoria_id = catImpostos.id
    }
  }
}

async function loadQuotaForEdit(id: string) {
  const response = await getQuota(id)
  const quota = response?.data

  if (!quota) {
    await router.replace('/quotes')
    return
  }

  form.nom = quota.nom ?? ''
  form.descripcio = quota.descripcio ?? ''
  form.import = Number(quota.import) || 0
  form.periodicitat = (quota.periodicitat ?? 'mensual') as Periodicitat
  form.dia_pagament = Number(quota.dia_pagament) || 1
  form.data_inici = quota.data_inici ?? avui()
  form.data_fi = quota.data_fi ?? ''
  form.categoria_id = Number(quota.categoria_id) || 0
  form.activa = normalitzarActiva(quota.activa)
}

async function handleSubmit() {
  formError.value = ''

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

  const payload: QuotaPayload = {
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
    if (isEdit.value) {
      await updateQuota(route.params.id as string, payload)
    } else {
      await createQuota(payload)
    }

    await router.push('/quotes')
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'No s\'ha pogut desar la quota.'
  } finally {
    formLoading.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      await loadCategories()

      if (isEdit.value) {
        await loadQuotaForEdit(route.params.id as string)
      }
    } catch (requestError: any) {
      const status = requestError?.response?.status
      if (isEdit.value && status === 404) {
        await router.replace('/quotes')
        return
      }

      formError.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de quota.'
    }
  })
})
</script>
