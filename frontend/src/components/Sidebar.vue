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

      <RouterLink
        to="/categories"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Categories
      </RouterLink>

      <RouterLink
        to="/moviments"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Moviments
      </RouterLink>

      <RouterLink
        to="/quotes"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Quotes
      </RouterLink>

      <RouterLink
        to="/informes"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Informes
      </RouterLink>

      <RouterLink
        to="/plantilles"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Plantilles
      </RouterLink>

      <RouterLink
        to="/verifactu"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Verifactu
      </RouterLink>

      <RouterLink
        to="/perfil"
        class="block px-3 py-2 rounded hover:bg-gray-700"
      >
        Perfil
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>