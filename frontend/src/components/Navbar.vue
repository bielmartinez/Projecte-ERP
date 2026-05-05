<template>
  <header class="h-14 bg-white border-b flex items-center justify-between px-6">
    <div class="flex items-center gap-3">
      <button type="button" class="md:hidden text-gray-600 hover:text-gray-900" aria-label="Obrir menú" @click="emit('toggle-sidebar')">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <h1 class="text-lg font-semibold">ERP Web</h1>
    </div>

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
const emit = defineEmits<{ (e: 'toggle-sidebar'): void }>()
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
  } finally {
    await router.replace({ name: 'login' })
    isLoggingOut.value = false
  }
}
</script>