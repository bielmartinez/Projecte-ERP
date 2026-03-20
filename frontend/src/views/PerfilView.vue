<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-semibold">Perfil</h2>

    <section class="bg-white rounded shadow p-6 space-y-4">
      <h3 class="text-lg font-semibold">Dades personals i fiscals</h3>

      <p v-if="perfilError" class="text-sm text-red-600">{{ perfilError }}</p>
      <p v-if="perfilSuccess" class="text-sm text-green-600">{{ perfilSuccess }}</p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="perfil.nom" class="border rounded px-3 py-2" placeholder="Nom" type="text" />
        <input v-model="perfil.cognoms" class="border rounded px-3 py-2" placeholder="Cognoms" type="text" />
        <input v-model="perfil.nif" class="border rounded px-3 py-2" placeholder="NIF" type="text" />
        <input v-model="perfil.telefon" class="border rounded px-3 py-2" placeholder="Telèfon" type="text" />
        <input v-model="perfil.nom_empresa" class="border rounded px-3 py-2" placeholder="Nom empresa" type="text" />
        <input v-model="perfil.compte_bancari" class="border rounded px-3 py-2" placeholder="Compte bancari" type="text" />
        <input v-model="perfil.adreca" class="border rounded px-3 py-2 md:col-span-2" placeholder="Adreça" type="text" />
        <input v-model="perfil.codi_postal" class="border rounded px-3 py-2" placeholder="Codi postal" type="text" />
        <input v-model="perfil.poblacio" class="border rounded px-3 py-2" placeholder="Població" type="text" />
        <input v-model="perfil.provincia" class="border rounded px-3 py-2" placeholder="Província" type="text" />
        <input v-model="perfil.pais" class="border rounded px-3 py-2" placeholder="País" type="text" />
      </div>

      <button
        type="button"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
        :disabled="perfilLoading"
        @click="handleSavePerfil"
      >
        {{ perfilLoading ? 'Guardant...' : 'Guardar canvis' }}
      </button>
    </section>

    <section class="bg-white rounded shadow p-6 space-y-4">
      <h3 class="text-lg font-semibold">Canviar contrasenya</h3>

      <p v-if="passwordError" class="text-sm text-red-600">{{ passwordError }}</p>
      <p v-if="passwordSuccess" class="text-sm text-green-600">{{ passwordSuccess }}</p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="passwordForm.contrasenya_actual" class="border rounded px-3 py-2" placeholder="Contrasenya actual" type="password" />
        <input v-model="passwordForm.contrasenya_nova" class="border rounded px-3 py-2" placeholder="Contrasenya nova" type="password" />
        <input v-model="passwordForm.contrasenya_confirmacio" class="border rounded px-3 py-2" placeholder="Confirmació contrasenya" type="password" />
      </div>

      <button
        type="button"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
        :disabled="passwordLoading"
        @click="handleChangePassword"
      >
        {{ passwordLoading ? 'Actualitzant...' : 'Canviar contrasenya' }}
      </button>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import { changePassword, getPerfil, type PerfilPayload, updatePerfil } from '@/services/profile'

const perfil = reactive<PerfilPayload>({
  nom: '',
  cognoms: '',
  nif: '',
  telefon: '',
  nom_empresa: '',
  adreca: '',
  codi_postal: '',
  poblacio: '',
  provincia: '',
  pais: '',
  compte_bancari: ''
})

const perfilLoading = ref(false)
const perfilError = ref('')
const perfilSuccess = ref('')

const passwordForm = reactive({
  contrasenya_actual: '',
  contrasenya_nova: '',
  contrasenya_confirmacio: ''
})

const passwordLoading = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

async function loadPerfil() {
  perfilError.value = ''

  try {
    const response = await getPerfil()
    const data = response.data ?? {}

    perfil.nom = data.nom ?? ''
    perfil.cognoms = data.cognoms ?? ''
    perfil.nif = data.nif ?? ''
    perfil.telefon = data.telefon ?? ''
    perfil.nom_empresa = data.nom_empresa ?? ''
    perfil.adreca = data.adreca ?? ''
    perfil.codi_postal = data.codi_postal ?? ''
    perfil.poblacio = data.poblacio ?? ''
    perfil.provincia = data.provincia ?? ''
    perfil.pais = data.pais ?? ''
    perfil.compte_bancari = data.compte_bancari ?? ''
  } catch (error: any) {
    perfilError.value = error?.response?.data?.message ?? 'No s\'ha pogut carregar el perfil.'
  }
}

async function handleSavePerfil() {
  perfilLoading.value = true
  perfilError.value = ''
  perfilSuccess.value = ''

  try {
    await updatePerfil(perfil)
    perfilSuccess.value = 'Perfil actualitzat correctament.'
  } catch (error: any) {
    perfilError.value = error?.response?.data?.message ?? 'No s\'ha pogut actualitzar el perfil.'
  } finally {
    perfilLoading.value = false
  }
}

async function handleChangePassword() {
  passwordLoading.value = true
  passwordError.value = ''
  passwordSuccess.value = ''

  try {
    await changePassword(passwordForm)
    passwordSuccess.value = 'Contrasenya actualitzada correctament.'
    passwordForm.contrasenya_actual = ''
    passwordForm.contrasenya_nova = ''
    passwordForm.contrasenya_confirmacio = ''
  } catch (error: any) {
    passwordError.value = error?.response?.data?.message ?? 'No s\'ha pogut canviar la contrasenya.'
  } finally {
    passwordLoading.value = false
  }
}

onMounted(() => {
  loadPerfil()
})
</script>
