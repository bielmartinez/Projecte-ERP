<template>
  <component
    :is="componentType"
    :to="to"
    :type="componentType === 'button' ? 'button' : undefined"
    :class="buttonClass"
    :disabled="componentType === 'button' ? disabled : undefined"
  >
    <slot>{{ label }}</slot>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

type Variant = 'primary' | 'neutral' | 'danger'

const props = withDefaults(
  defineProps<{
    label?: string
    to?: string | Record<string, unknown>
    variant?: Variant
    disabled?: boolean
  }>(),
  {
    label: '',
    to: undefined,
    variant: 'neutral',
    disabled: false
  }
)

const componentType = computed(() => (props.to ? RouterLink : 'button'))

const buttonClass = computed(() => {
  const base = 'px-3 py-1 rounded border text-sm transition-colors disabled:opacity-50'

  if (props.variant === 'primary') {
    return `${base} border-primary text-primary hover:bg-primary-light`
  }

  if (props.variant === 'danger') {
    return `${base} border-danger text-danger-hover hover:bg-danger-light`
  }

  return `${base} border-gray-300 text-gray-700 hover:bg-gray-50`
})
</script>