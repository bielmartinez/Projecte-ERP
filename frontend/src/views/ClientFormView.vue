<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">{{ isEdit ? 'Editar client' : 'Nou client' }}</h2>
      <RouterLink to="/clients" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </div>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-6 w-36 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-24 rounded bg-gray-200 md:col-span-2"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-10 w-24 rounded bg-gray-200"></div>
          <div class="h-10 w-24 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <section class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">{{ isEdit ? 'Editar client' : 'Crear client' }}</h3>
        <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Nom <span class="text-red-500">*</span></span>
            <input v-model="form.nom" type="text" class="border rounded px-3 py-2" placeholder="Nom*" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Cognoms</span>
            <input v-model="form.cognoms" type="text" class="border rounded px-3 py-2" placeholder="Cognoms" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Nom empresa</span>
            <input v-model="form.nom_empresa" type="text" class="border rounded px-3 py-2" placeholder="Nom empresa" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">NIF <span class="text-red-500">*</span></span>
            <input v-model="form.nif" type="text" class="border rounded px-3 py-2" placeholder="NIF" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Correu electrònic <span class="text-red-500">*</span></span>
            <input v-model="form.email" type="email" class="border rounded px-3 py-2" placeholder="Email" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Telèfon</span>
            <input v-model="form.telefon" type="text" class="border rounded px-3 py-2" placeholder="Telèfon" />
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Adreça</span>
            <input v-model="form.adreca" type="text" class="border rounded px-3 py-2" placeholder="Adreça" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Codi postal</span>
            <input v-model="form.codi_postal" type="text" class="border rounded px-3 py-2" placeholder="Codi postal" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Població</span>
            <input v-model="form.poblacio" type="text" class="border rounded px-3 py-2" placeholder="Població" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Província</span>
            <input v-model="form.provincia" type="text" class="border rounded px-3 py-2" placeholder="Província" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">País</span>
            <input v-model="form.pais" type="text" class="border rounded px-3 py-2" placeholder="País" />
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Notes</span>
            <textarea v-model="form.notes" class="border rounded px-3 py-2" rows="3" placeholder="Notes"></textarea>
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

          <button
            type="button"
            class="px-4 py-2 rounded border"
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

import { useInitialLoading } from '@/composables/useInitialLoading'
import { createClient, getClient, updateClient, type ClientPayload } from '@/services/clients'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))
const formLoading = ref(false)
const formError = ref('')

const form = reactive<ClientPayload>({
  nom: '',
  cognoms: '',
  nom_empresa: '',
  nif: '',
  email: '',
  telefon: '',
  adreca: '',
  codi_postal: '',
  poblacio: '',
  provincia: '',
  pais: 'Espanya',
  notes: ''
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function resetForm() {
  if (isEdit.value) {
    return
  }

  form.nom = ''
  form.cognoms = ''
  form.nom_empresa = ''
  form.nif = ''
  form.email = ''
  form.telefon = ''
  form.adreca = ''
  form.codi_postal = ''
  form.poblacio = ''
  form.provincia = ''
  form.pais = 'Espanya'
  form.notes = ''
  formError.value = ''
}

async function loadClientForEdit(id: string) {
  const response = await getClient(id)
  const client = response?.data?.client

  if (!client) {
    await router.replace('/clients')
    return
  }

  form.nom = client.nom ?? ''
  form.cognoms = client.cognoms ?? ''
  form.nom_empresa = client.nom_empresa ?? ''
  form.nif = client.nif ?? ''
  form.email = client.email ?? ''
  form.telefon = client.telefon ?? ''
  form.adreca = client.adreca ?? ''
  form.codi_postal = client.codi_postal ?? ''
  form.poblacio = client.poblacio ?? ''
  form.provincia = client.provincia ?? ''
  form.pais = client.pais ?? 'Espanya'
  form.notes = client.notes ?? ''
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
      await updateClient(route.params.id as string, form)
    } else {
      await createClient(form)
    }

    await router.push('/clients')
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'No s\'ha pogut desar el client.'
  } finally {
    formLoading.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      if (isEdit.value) {
        await loadClientForEdit(route.params.id as string)
      }
    } catch (requestError: any) {
      const status = requestError?.response?.status
      if (isEdit.value && status === 404) {
        await router.replace('/clients')
        return
      }

      formError.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de client.'
    }
  })
})
</script>
