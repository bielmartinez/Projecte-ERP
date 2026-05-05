<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="p-6 flex-1 overflow-y-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Navbar from '@/components/Navbar.vue'
import Sidebar from '@/components/Sidebar.vue'
import { RouterView } from 'vue-router'

const sidebarOpen = ref(false)
</script>