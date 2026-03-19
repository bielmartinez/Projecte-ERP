<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="mode === 'register'">
      <label class="block text-sm font-medium text-gray-700 mb-1" for="nom">Nom</label>
      <input
        id="nom"
        v-model.trim="nom"
        type="text"
        required
        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div v-if="mode === 'register'">
      <label class="block text-sm font-medium text-gray-700 mb-1" for="cognoms">Cognoms</label>
      <input
        id="cognoms"
        v-model.trim="cognoms"
        type="text"
        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email</label>
      <input
        id="email"
        v-model.trim="email"
        type="email"
        required
        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Contrasenya</label>
      <input
        id="password"
        v-model="password"
        type="password"
        required
        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div v-if="mode === 'login'" class="flex items-center gap-2">
      <input id="recordar" v-model="recordar" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
      <label for="recordar" class="text-sm text-gray-700">Recorda'm</label>
    </div>

    <button
      type="submit"
      :disabled="loading"
      class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 disabled:opacity-60"
    >
      {{ buttonLabel }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  mode: 'login' | 'register'
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [
    payload:
      | { email: string; password: string; recordar: boolean }
      | { nom: string; cognoms?: string; email: string; password: string }
  ]
}>()

const nom = ref('')
const cognoms = ref('')
const email = ref('')
const password = ref('')
const recordar = ref(false)

const buttonLabel = computed(() => {
  if (props.loading) {
    return 'Carregant...'
  }

  return props.mode === 'login' ? 'Iniciar sessió' : 'Registrar-se'
})

function onSubmit() {
  if (props.mode === 'login') {
    emit('submit', {
      email: email.value,
      password: password.value,
      recordar: recordar.value
    })

    return
  }

  emit('submit', {
    nom: nom.value,
    cognoms: cognoms.value || undefined,
    email: email.value,
    password: password.value
  })
}
</script>
