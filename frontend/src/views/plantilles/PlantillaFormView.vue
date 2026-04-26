<template>
  <div class="space-y-6">
    <PageHeader :title="isEdit ? 'Editar plantilla' : 'Nova plantilla'">
      <RouterLink to="/plantilles" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </PageHeader>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-6 w-44 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-4 animate-pulse">
        <div class="h-6 w-36 rounded bg-gray-200"></div>
        <div v-for="row in 3" :key="row" class="grid grid-cols-1 md:grid-cols-5 gap-3">
          <div class="h-10 rounded bg-gray-200 md:col-span-2"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
          <div class="h-10 rounded bg-gray-200"></div>
        </div>
      </section>
    </div>

    <template v-else>
      <FormMessages :error="error" />

      <section class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Dades de plantilla</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Nom de plantilla</span>
            <input v-model="form.nom" type="text" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">IVA (%) per defecte</span>
            <select v-model.number="form.iva_percentatge" class="border rounded px-3 py-2 w-full">
              <option v-for="iva in IVA_OPTIONS" :key="iva" :value="iva">{{ iva }}%</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">IRPF (%)</span>
            <input v-model.number="form.irpf_percentatge" type="number" step="0.01" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Mètode de pagament</span>
            <input v-model="form.metode_pagament" type="text" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1 md:col-span-3">
            <span class="text-sm font-medium text-gray-700">Descripció</span>
            <textarea v-model="form.descripcio" rows="2" class="border rounded px-3 py-2 w-full"></textarea>
          </label>

          <label class="space-y-1 md:col-span-3">
            <span class="text-sm font-medium text-gray-700">Notes plantilla</span>
            <textarea v-model="form.notes_plantilla" rows="3" class="border rounded px-3 py-2 w-full"></textarea>
          </label>
        </div>
      </section>

      <LiniesEditor
        v-model:linies="form.linies"
        :iva-per-defecte="form.iva_percentatge"
        titol="Línies de plantilla"
      />

      <section class="bg-white rounded shadow p-6">
        <button
          type="button"
          class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
          :disabled="saving"
          @click="handleSubmit"
        >
          {{ saving ? 'Guardant...' : isEdit ? 'Actualitzar plantilla' : 'Crear plantilla' }}
        </button>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import FormMessages from '@/components/FormMessages.vue'
import LiniesEditor, { IVA_OPTIONS, type LiniaForm } from '@/components/LiniesEditor.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import { createPlantilla, getPlantilla, updatePlantilla, type PlantillaLiniaPayload, type PlantillaPayload } from '@/services/plantilles'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))
const saving = ref(false)
const error = ref('')

const form = reactive({
  nom: '',
  descripcio: '',
  iva_percentatge: 21,
  irpf_percentatge: 0,
  metode_pagament: '',
  notes_plantilla: '',
  linies: [] as LiniaForm[]
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function mapLiniesPayload(): PlantillaLiniaPayload[] {
  return form.linies.map((linia) => ({
    descripcio: linia.descripcio,
    quantitat: Number(linia.quantitat),
    preu_unitari: Number(linia.preu_unitari),
    iva_percentatge: Number(linia.iva_percentatge) || Number(form.iva_percentatge) || 0,
    descompte: Number(linia.descompte) || 0
  }))
}

function validateForm() {
  if (!form.nom.trim()) {
    return 'El nom de plantilla és obligatori.'
  }

  if (form.linies.length === 0) {
    return 'La plantilla ha de tenir almenys una línia.'
  }

  if (!IVA_OPTIONS.includes(Number(form.iva_percentatge))) {
    return 'L\'IVA per defecte només pot ser 0, 4, 10 o 21.'
  }

  const liniaBuida = form.linies.find((linia) => !linia.descripcio.trim())
  if (liniaBuida) {
    return 'Totes les línies han de tenir descripció.'
  }

  const liniaInvalid = form.linies.find((linia) => Number(linia.quantitat) <= 0)
  if (liniaInvalid) {
    return 'La quantitat ha de ser major que 0 a totes les línies.'
  }

  const liniaIvaInvalid = form.linies.find((linia) => !IVA_OPTIONS.includes(Number(linia.iva_percentatge)))
  if (liniaIvaInvalid) {
    return 'L\'IVA de cada línia només pot ser 0, 4, 10 o 21.'
  }

  return ''
}

async function loadPlantillaForEdit(id: string) {
  const response = await getPlantilla(id)
  const plantilla = response?.data?.plantilla
  const linies = response?.data?.linies ?? []

  if (!plantilla) {
    throw new Error('Plantilla no trobada')
  }

  form.nom = plantilla.nom ?? ''
  form.descripcio = plantilla.descripcio ?? ''

  const ivaCarregat = Number(plantilla.iva_percentatge ?? 21)
  form.iva_percentatge = IVA_OPTIONS.includes(ivaCarregat) ? ivaCarregat : 21

  form.irpf_percentatge = Number(plantilla.irpf_percentatge ?? 0)
  form.metode_pagament = plantilla.metode_pagament ?? ''
  form.notes_plantilla = plantilla.notes_plantilla ?? ''

  form.linies = linies.map((linia: any) => ({
    descripcio: linia.descripcio ?? '',
    quantitat: Number(linia.quantitat ?? 1),
    preu_unitari: Number(linia.preu_unitari ?? 0),
    iva_percentatge: Number(linia.iva_percentatge ?? plantilla.iva_percentatge ?? 21),
    descompte: Number(linia.descompte ?? 0)
  }))

  if (form.linies.length === 0) {
    form.linies.push({
      descripcio: '',
      quantitat: 1,
      preu_unitari: 0,
      iva_percentatge: Number(form.iva_percentatge) || 21,
      descompte: 0
    })
  }
}

async function handleSubmit() {
  error.value = ''

  const formError = validateForm()
  if (formError) {
    error.value = formError
    return
  }

  saving.value = true

  const payload: PlantillaPayload = {
    nom: form.nom.trim(),
    descripcio: form.descripcio || undefined,
    iva_percentatge: Number(form.iva_percentatge) || 0,
    irpf_percentatge: Number(form.irpf_percentatge) || 0,
    metode_pagament: form.metode_pagament || undefined,
    notes_plantilla: form.notes_plantilla || undefined,
    linies: mapLiniesPayload()
  }

  try {
    if (isEdit.value) {
      await updatePlantilla(route.params.id as string, payload)
    } else {
      await createPlantilla(payload)
    }

    await router.push('/plantilles')
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut desar la plantilla.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      if (isEdit.value) {
        await loadPlantillaForEdit(route.params.id as string)
      } else {
        form.linies.push({
          descripcio: '',
          quantitat: 1,
          preu_unitari: 0,
          iva_percentatge: Number(form.iva_percentatge) || 21,
          descompte: 0
        })
      }
    } catch (requestError: any) {
      error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de plantilla.'
    }
  })
})
</script>
