<template>
  <header class="h-14 bg-white border-b flex items-center justify-between px-6">
    <h1 class="text-lg font-semibold">ERP Web</h1>

    <div class="flex items-center gap-4">
      <span class="text-sm text-gray-600">{{ displayName }}</span>
      <button
        type="button"
        class="text-sm text-red-500 hover:text-red-600 disabled:opacity-50"
        :disabled="isLoggingOut"
        @click="handleLogout"
      >
        {{ isLoggingOut ? 'Sortint...' : 'Sortir' }}
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const isLoggingOut = ref(false)

const displayName = computed(() => {
  if (authStore.usuari?.nom) {
    return authStore.usuari.nom
  }

  if (authStore.usuari?.email) {
    return authStore.usuari.email
  }

  return 'Usuari'
})

async function handleLogout() {
  isLoggingOut.value = true

  try {
    await authStore.logout()
  } catch {
    authStore.clearSession()
  } finally {
    await router.push({ name: 'login' })
    isLoggingOut.value = false
  }
}
</script>