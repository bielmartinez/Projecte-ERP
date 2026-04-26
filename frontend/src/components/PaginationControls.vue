<template>
  <div class="flex items-center justify-between">
    <p class="text-sm text-gray-600">Pàgina {{ page }} de {{ totalPages || 1 }}</p>
    <div class="flex gap-2">
      <button
        type="button"
        class="px-3 py-1 border rounded disabled:opacity-50"
        :disabled="page <= 1"
        @click="$emit('canvia-pagina', page - 1)"
      >
        Anterior
      </button>
      <button
        type="button"
        class="px-3 py-1 border rounded disabled:opacity-50"
        :disabled="page >= (totalPages || 1)"
        @click="$emit('canvia-pagina', page + 1)"
      >
        Següent
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  page: number
  totalPages: number
}>()

defineEmits<{
  (e: 'canvia-pagina', page: number): void
}>()
</script>
