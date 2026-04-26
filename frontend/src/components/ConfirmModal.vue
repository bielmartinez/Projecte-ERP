<template>
  <Transition
    enter-active-class="transition-opacity duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="visible" class="fixed inset-0 z-50 bg-black/50" @click.self="onCancel">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md mx-auto rounded-lg bg-white p-6 shadow-xl">
          <h3 class="text-lg font-semibold">{{ title }}</h3>
          <p class="mt-2 text-gray-600">{{ message }}</p>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="rounded border px-4 py-2 hover:bg-gray-50"
              @click="onCancel"
            >
              {{ cancelText }}
            </button>
            <button
              type="button"
              class="rounded px-4 py-2 text-white"
              :class="confirmButtonClass"
              @click="onConfirm"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type ConfirmVariant = 'danger' | 'warning'

interface Props {
  visible: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  variant?: ConfirmVariant
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Confirmar acció',
  message: 'Estàs segur que vols continuar?',
  confirmText: 'Confirmar',
  cancelText: 'Cancel·lar',
  variant: 'danger'
})

const emit = defineEmits<{
  (event: 'confirma'): void
  (event: 'cancel·la'): void
  (event: 'cancela'): void
}>()

const confirmButtonClass = computed(() => {
  if (props.variant === 'warning') {
    return 'bg-amber-500 hover:bg-amber-600'
  }

  return 'bg-red-600 hover:bg-red-700'
})

function onConfirm() {
  emit('confirma')
}

function onCancel() {
  emit('cancel·la')
  emit('cancela')
}
</script>