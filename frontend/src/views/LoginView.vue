<template>
  <AuthCard>
    <h1 class="text-2xl font-semibold text-gray-900 mb-2">Iniciar sessió</h1>
    <p class="text-sm text-gray-600 mb-6">Accedeix al teu ERP per gestionar el negoci.</p>

    <p v-if="authStore.error" class="mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded p-2">
      {{ authStore.error }}
    </p>

    <p v-if="sessionMessage" class="mb-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
      {{ sessionMessage }}
    </p>

    <AuthForm mode="login" :loading="authStore.loading" @submit="handleLogin" />

    <p class="text-sm text-gray-600 mt-5 text-center">
      Encara no tens compte?
      <RouterLink class="text-indigo-600 font-medium" :to="{ name: 'register' }">Registra't</RouterLink>
    </p>
  </AuthCard>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'

import AuthCard from '@/components/auth/AuthCard.vue'
import AuthForm from '@/components/auth/AuthForm.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const AUTH_REASON_STORAGE_KEY = 'erp_auth_redirect_reason'
const sessionMessage = ref<string | null>(null)

onMounted(() => {
  const reasonFromQuery = typeof route.query.reason === 'string' ? route.query.reason : null
  const reasonFromStorage = sessionStorage.getItem(AUTH_REASON_STORAGE_KEY)
  const reason = reasonFromQuery ?? reasonFromStorage

  if (reason === 'session-expired') {
    sessionMessage.value = 'La sessió ha caducat. Torna a iniciar sessió, si us plau.'
    sessionStorage.removeItem(AUTH_REASON_STORAGE_KEY)
  }
})

async function handleLogin(payload: {
  email: string
  password: string
  recordar?: boolean
  nom?: string
  cognoms?: string
}) {
  if (typeof payload.recordar !== 'boolean') {
    return
  }

  try {
    await authStore.login({
      email: payload.email,
      password: payload.password,
      recordar: payload.recordar
    })
    await router.push({ name: 'dashboard' })
  } catch {
  }
}
</script>
