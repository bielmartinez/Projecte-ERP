<template>
  <!-- Overlay mòbil -->
  <div v-if="open" class="fixed inset-0 bg-black/50 z-40 md:hidden" @click="emit('close')" />

  <!-- Sidebar -->
  <aside
    class="bg-gray-900 text-white min-h-screen w-64 z-50 transition-transform duration-300"
    :class="[
      'fixed md:relative',
      open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
    ]"
  >
    <nav class="p-4 space-y-1">
      <RouterLink
        to="/"
        exact
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        exact-active-class="bg-gray-700"
        @click="emit('close')"
      >
        Dashboard
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/factures"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Factures
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/clients"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Clients
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/categories"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Categories
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/moviments"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Moviments
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/quotes"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Quotes
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/informes"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Informes
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/plantilles"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Plantilles
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/verifactu"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Verifactu
      </RouterLink>
      <hr class="border-gray-700" />

      <RouterLink
        to="/perfil"
        class="block px-3 py-4 rounded hover:bg-gray-700"
        active-class="bg-gray-700"
        @click="emit('close')"
      >
        Perfil
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
const { open } = defineProps<{ open: boolean }>()
const emit = defineEmits<{ (e: 'close'): void }>()
</script>