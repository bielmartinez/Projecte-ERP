<template>
  <div v-if="error || success">
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
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