<template>
  <div class="flex items-center justify-between gap-4">
    <div>
      <h2 class="text-3xl font-bold tracking-tight">{{ title }}</h2>
      <p v-if="subtitle || $slots.subtitle" class="text-sm text-gray-500 mt-1">
        <slot name="subtitle">{{ subtitle }}</slot>
      </p>
    </div>
    <div v-if="$slots.default" class="flex items-center gap-2">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  subtitle?: string
}>()
</script>
