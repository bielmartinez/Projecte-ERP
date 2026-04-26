<template>
  <section class="bg-white rounded shadow p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold">{{ props.titol }}</h3>
      <button type="button" class="px-3 py-1 border rounded" @click="addLinia">Afegir línia</button>
    </div>

    <div class="space-y-3">
      <div class="hidden md:grid md:grid-cols-12 gap-3 text-xs font-semibold text-gray-500 uppercase">
        <span class="md:col-span-4">Descripció</span>
        <span class="md:col-span-2">Quantitat</span>
        <span class="md:col-span-2">Preu unitari</span>
        <span class="md:col-span-1">IVA %</span>
        <span class="md:col-span-2">Descompte %</span>
        <span class="md:col-span-1">Acció</span>
      </div>

      <div v-for="(linia, index) in props.linies" :key="`linia-${index}`" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
        <input
          v-model="linia.descripcio"
          type="text"
          class="border rounded px-3 py-2 md:col-span-4"
          placeholder="Descripció"
        />

        <input
          v-model.number="linia.quantitat"
          type="number"
          min="1"
          step="1"
          class="border rounded px-3 py-2 md:col-span-2"
          placeholder="Quantitat"
        />

        <div class="relative md:col-span-2">
          <input
            v-model.number="linia.preu_unitari"
            type="number"
            min="0"
            step="0.01"
            class="border rounded px-3 py-2 pr-8 w-full"
            placeholder="Preu"
          />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">€</span>
        </div>

        <select
          v-model.number="linia.iva_percentatge"
          class="border rounded px-3 py-2 md:col-span-1"
        >
          <option v-for="iva in IVA_OPTIONS" :key="`iva-${index}-${iva}`" :value="iva">{{ iva }}%</option>
        </select>

        <input
          v-model.number="linia.descompte"
          type="number"
          min="0"
          max="100"
          step="0.01"
          class="border rounded px-3 py-2 md:col-span-2"
          placeholder="Desc %"
        />

        <button
          type="button"
          class="text-red-600 hover:underline md:col-span-1 py-2"
          @click="removeLinia(index)"
        >
          Eliminar
        </button>

        <div v-if="props.showTotals" class="md:col-span-12 text-sm text-gray-600">
          Base línia: {{ totalLinia(linia).toFixed(2) }} € ·
          IVA línia: {{ ivaLiniaImport(linia).toFixed(2) }} €
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
export interface LiniaForm {
  descripcio: string
  quantitat: number
  preu_unitari: number
  iva_percentatge: number
  descompte: number
}

export const IVA_OPTIONS = [0, 4, 10, 21]

export function totalLinia(linia: LiniaForm) {
  const base = (Number(linia.quantitat) || 0) * (Number(linia.preu_unitari) || 0)
  const descompte = Number(linia.descompte) || 0
  const baseAmbDescompte = base * (1 - descompte / 100)
  return Math.max(0, baseAmbDescompte)
}

export function ivaLiniaImport(linia: LiniaForm) {
  return totalLinia(linia) * ((Number(linia.iva_percentatge) || 0) / 100)
}
</script>

<script setup lang="ts">
interface Props {
  linies: LiniaForm[]
  ivaPerDefecte?: number
  showTotals?: boolean
  titol?: string
}

const props = withDefaults(defineProps<Props>(), {
  ivaPerDefecte: 21,
  showTotals: false,
  titol: 'Línies'
})

const emit = defineEmits<{
  (event: 'update:linies', value: LiniaForm[]): void
}>()

function addLinia() {
  const liniesMutables = props.linies as LiniaForm[]
  liniesMutables.push({
    descripcio: '',
    quantitat: 1,
    preu_unitari: 0,
    iva_percentatge: Number(props.ivaPerDefecte) || 21,
    descompte: 0
  })
  emit('update:linies', [...liniesMutables])
}

function removeLinia(index: number) {
  const liniesMutables = props.linies as LiniaForm[]
  liniesMutables.splice(index, 1)
  emit('update:linies', [...liniesMutables])
}
</script>