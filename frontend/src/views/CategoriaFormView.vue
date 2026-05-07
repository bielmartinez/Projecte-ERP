<template>
  <div class="space-y-6">
    <PageHeader :title="isEdit ? 'Editar categoria' : 'Nova categoria'">
      <RouterLink to="/categories" class="text-primary hover:underline">Tornar al llistat</RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-6 w-36 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-10 w-24 rounded bg-gray-200"></div>
          <div class="h-10 w-24 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">{{ isEdit ? 'Editar categoria' : 'Crear categoria' }}</h3>
        <FormMessages :error="formError" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Nom <span class="text-red-500">*</span></span>
            <input
              v-model="form.nom"
              type="text"
              class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              placeholder="Nom*"
            />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Tipus</span>
            <select
              v-model="form.tipus"
              class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
            >
              <option value="ingres">Ingrés</option>
              <option value="despesa">Despesa</option>
            </select>
          </label>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover disabled:opacity-50"
            :disabled="formLoading"
            @click="handleSubmit"
          >
            {{ formLoading ? 'Guardant...' : isEdit ? 'Actualitzar' : 'Crear' }}
          </button>

          <button
            type="button"
            class="px-4 py-2 rounded border border-primary text-primary hover:bg-primary-light"
            :disabled="formLoading"
            @click="resetForm"
          >
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
import { createCategoria, getCategoria, updateCategoria, type CategoriaMovimentPayload, type TipusMoviment } from '@/services/categories'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))
const formLoading = ref(false)
const formError = ref('')

const form = reactive<CategoriaMovimentPayload>({
  nom: '',
  tipus: 'despesa'
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function resetForm() {
  if (isEdit.value) {
    return
  }

  form.nom = ''
  form.tipus = 'despesa'
  formError.value = ''
}

async function loadCategoriaForEdit(id: string) {
  const response = await getCategoria(id)
  const categoria = response?.data

  if (!categoria) {
    await router.replace('/categories')
    return
  }

  form.nom = categoria.nom ?? ''
  form.tipus = (categoria.tipus ?? 'despesa') as TipusMoviment
}

async function handleSubmit() {
  formError.value = ''

  if (!form.nom?.trim()) {
    formError.value = 'El nom és obligatori.'
    return
  }

  formLoading.value = true

  try {
    if (isEdit.value) {
      await updateCategoria(route.params.id as string, form)
    } else {
      await createCategoria(form)
    }

    await router.push('/categories')
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'No s\'ha pogut desar la categoria.'
  } finally {
    formLoading.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      if (isEdit.value) {
        await loadCategoriaForEdit(route.params.id as string)
      }
    } catch (requestError: any) {
      const status = requestError?.response?.status
      if (isEdit.value && status === 404) {
        await router.replace('/categories')
        return
      }

      formError.value =
        requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de categoria.'
    }
  })
})
</script>
