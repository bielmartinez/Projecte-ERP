<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-8 relative overflow-hidden">
    <div class="absolute top-0 -left-20 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 pointer-events-none"></div>
    <div class="absolute bottom-0 -right-20 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/3 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 pointer-events-none"></div>
    
    <AuthCard class="relative z-10">
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
