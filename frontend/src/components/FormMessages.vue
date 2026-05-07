<template>
  <div v-if="error || success" class="space-y-2">
    <p v-if="error" class="text-sm rounded px-3 py-2 bg-danger-light text-danger-hover">{{ error }}</p>
    <p v-if="success" class="text-sm rounded px-3 py-2 bg-success-light text-success-hover">{{ success }}</p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  error?: string
  success?: string
}

withDefaults(defineProps<Props>(), {
  error: '',
  success: ''
})
</script>