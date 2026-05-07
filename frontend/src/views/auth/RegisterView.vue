<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 flex items-center justify-center px-4">
    <AuthCard>
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-2">Crear compte</h1>
      <p class="text-sm text-gray-500 mb-6">Registra el teu usuari per començar a treballar.</p>

      <p v-if="authStore.error" class="mb-4 text-sm text-danger-hover bg-danger-light border border-danger-light rounded p-2">
        {{ authStore.error }}
      </p>

      <AuthForm mode="register" :loading="authStore.loading" @submit="handleRegister" />

      <p class="text-sm text-gray-600 mt-5 text-center">
        Ja tens compte?
        <RouterLink class="text-primary hover:text-primary-hover font-medium" :to="{ name: 'login' }">Inicia sessió</RouterLink>
      </p>
    </AuthCard>
  </div>
</template>

<script setup lang="ts">
import { useRouter, RouterLink } from 'vue-router'

import AuthCard from '@/components/auth/AuthCard.vue'
import AuthForm from '@/components/auth/AuthForm.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function handleRegister(payload: {
  email: string
  password: string
  nom?: string
  cognoms?: string
  recordar?: boolean
}) {
  if (typeof payload.nom !== 'string' || payload.nom.trim() === '') {
    return
  }

  try {
    await authStore.register({
      nom: payload.nom,
      cognoms: payload.cognoms,
      email: payload.email,
      password: payload.password
    })
    await router.push({ name: 'login' })
  } catch {
  }
}
</script>
