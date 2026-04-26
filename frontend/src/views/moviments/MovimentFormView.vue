<template>
  <div class="space-y-6">
    <PageHeader :title="isEdit ? 'Editar moviment' : 'Nou moviment'">
      <RouterLink to="/moviments" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-4" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-6 w-44 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">{{ isEdit ? 'Editar moviment' : 'Crear moviment' }}</h3>
        <FormMessages :error="formError" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Tipus</span>
            <select v-model="form.tipus" class="border rounded px-3 py-2 w-full">
              <option value="ingres">Ingrés</option>
              <option value="despesa">Despesa</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Categoria</span>
            <select v-model.number="form.categoria_id" class="border rounded px-3 py-2 w-full">
              <option :value="0">Selecciona categoria</option>
              <option v-for="categoria in categoriesForForm" :key="categoria.id" :value="categoria.id">
                {{ categoria.nom }}
              </option>
            </select>
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Descripció</span>
            <input
              v-model="form.descripcio"
              type="text"
              class="border rounded px-3 py-2 w-full"
              placeholder="Descripció del moviment"
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

          <label v-if="form.tipus === 'despesa'" class="space-y-1">
            <span class="text-sm font-medium text-gray-700">IVA (%)</span>
            <select v-model.number="form.iva_percentatge" class="border rounded px-3 py-2 w-full">
              <option :value="0">0% (Exempt)</option>
              <option :value="4">4% (Superreduït)</option>
              <option :value="10">10% (Reduït)</option>
              <option :value="21">21% (General)</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data</span>
            <input v-model="form.data" type="date" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Factura (opcional)</span>
            <select v-model.number="form.factura_id" class="border rounded px-3 py-2 w-full">
              <option :value="0">Sense factura</option>
              <option v-for="factura in factures" :key="factura.id" :value="factura.id">
                {{ factura.numero_factura }} — {{ Number(factura.total).toFixed(2) }} €
              </option>
            </select>
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import FormMessages from '@/components/FormMessages.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import { getCategories, type CategoriaMoviment, type TipusMoviment } from '@/services/categories'
import { getFactures, type Factura } from '@/services/factures'
import { createMoviment, getMoviment, updateMoviment } from '@/services/moviments'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))

const categories = ref<CategoriaMoviment[]>([])
const factures = ref<Factura[]>([])
const formLoading = ref(false)
const formError = ref('')

const form = reactive({
  categoria_id: 0,
  factura_id: 0,
  tipus: 'despesa' as TipusMoviment,
  descripcio: '',
  import: 0,
  iva_percentatge: 21,
  data: todayDate()
})

const { initialLoading, runInitialLoad } = useInitialLoading()

const categoriesForForm = computed(() => {
  return categories.value.filter((categoria) => categoria.tipus === form.tipus)
})

watch(
  () => form.tipus,
  () => {
    const stillValid = categoriesForForm.value.some((categoria) => categoria.id === form.categoria_id)
    if (!stillValid) {
      form.categoria_id = 0
    }
  }
)

function todayDate() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

function resetForm() {
  if (isEdit.value) {
    return
  }

  form.tipus = 'despesa'
  form.categoria_id = 0
  form.factura_id = 0
  form.descripcio = ''
  form.import = 0
  form.iva_percentatge = 21
  form.data = todayDate()
  formError.value = ''
}

async function loadCategoriesCatalog() {
  const response = await getCategories({ page: 1, limit: 100 })
  categories.value = response.data ?? []
}

async function loadFactures() {
  const response = await getFactures({ limit: 100 })
  factures.value = (response.data ?? []).filter(
    (f: Factura) => f.estat !== 'esborrany'
  )
}

async function loadMovimentForEdit(id: string) {
  const response = await getMoviment(id)
  const moviment = response?.data

  if (!moviment) {
    await router.replace('/moviments')
    return
  }

  form.tipus = moviment.tipus
  form.categoria_id = Number(moviment.categoria_id) || 0
  form.factura_id = moviment.factura_id ? Number(moviment.factura_id) : 0
  form.descripcio = moviment.descripcio ?? ''
  form.import = Number(moviment.import) || 0
  form.iva_percentatge = Number(moviment.iva_percentatge) || 0
  form.data = moviment.data ?? todayDate()
}

async function handleSubmit() {
  formError.value = ''

  if (!form.descripcio.trim()) {
    formError.value = 'La descripció és obligatòria.'
    return
  }

  if (!form.categoria_id) {
    formError.value = 'Has de seleccionar una categoria.'
    return
  }

  if (Number(form.import) <= 0) {
    formError.value = 'L\'import ha de ser major que 0.'
    return
  }

  if (!form.data) {
    formError.value = 'La data és obligatòria.'
    return
  }

  formLoading.value = true

  const payload = {
    categoria_id: Number(form.categoria_id),
    factura_id: form.factura_id ? Number(form.factura_id) : undefined,
    tipus: form.tipus,
    descripcio: form.descripcio.trim(),
    import: Number(form.import),
    iva_percentatge: form.tipus === 'despesa' ? Number(form.iva_percentatge) : 0,
    data: form.data
  }

  try {
    if (isEdit.value) {
      await updateMoviment(route.params.id as string, payload)
    } else {
      await createMoviment(payload)
    }

    await router.push('/moviments')
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'No s\'ha pogut desar el moviment.'
  } finally {
    formLoading.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      await Promise.all([loadCategoriesCatalog(), loadFactures()])

      if (isEdit.value) {
        await loadMovimentForEdit(route.params.id as string)
      } else {
        form.data = todayDate()
      }
    } catch (requestError: any) {
      const status = requestError?.response?.status
      if (isEdit.value && status === 404) {
        await router.replace('/moviments')
        return
      }

      formError.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de moviment.'
    }
  })
})
</script>
