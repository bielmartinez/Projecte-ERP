<template>
  <aside class="w-64 bg-gray-900 text-white min-h-screen">
    <nav class="p-4 space-y-2">
      <RouterLink
        to="/"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Dashboard
      </RouterLink>

      <RouterLink
        to="/factures"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Factures
      </RouterLink>

      <RouterLink
        to="/clients"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Clients
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>