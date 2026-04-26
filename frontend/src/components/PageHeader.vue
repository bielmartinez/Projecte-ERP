<template>
  <div class="flex items-center justify-between gap-4">
    <h2 class="text-2xl font-semibold">{{ title }}</h2>
    <div v-if="$slots.default" class="flex items-center gap-2">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>
