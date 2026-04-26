<template>
  <div class="space-y-6">
    <PageHeader :title="isEdit ? 'Editar factura' : 'Nova factura'">
      <RouterLink to="/factures" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </PageHeader>

    <!-- Div de càrrega  de la pagina-->
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
      <FormMessages :error="error" :success="success" />

      <section class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Dades generals</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Client</span>
            <select v-model.number="form.client_id" class="border rounded px-3 py-2 w-full">
              <option :value="0">Selecciona client</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">
                {{ client.nom }} {{ client.cognoms ?? '' }}
              </option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data emissió</span>
            <input v-model="form.data_emisio" type="date" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Data venciment</span>
            <input v-model="form.data_venciment" type="date" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">IVA per defecte (%)</span>
            <select v-model.number="form.iva_percentatge" class="border rounded px-3 py-2 w-full">
              <option v-for="iva in IVA_OPTIONS" :key="iva" :value="iva">{{ iva }}%</option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">IRPF (%)</span>
            <input v-model.number="form.irpf_percentatge" type="number" step="0.01" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1">
            <span class="text-sm font-medium text-gray-700">Estat</span>
            <select v-model="form.estat" class="border rounded px-3 py-2 w-full">
              <option value="esborrany">Esborrany</option>
              <option value="emesa">Emesa</option>
              <option value="cancel·lada">Cancel·lada</option>
              <option value="cobrada">Cobrada</option>
            </select>
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-gray-700">Mètode de pagament</span>
            <input v-model="form.metode_pagament" type="text" class="border rounded px-3 py-2 w-full" />
          </label>

          <label class="space-y-1 md:col-span-3">
            <span class="text-sm font-medium text-gray-700">Notes</span>
            <textarea v-model="form.notes" rows="3" class="border rounded px-3 py-2 w-full"></textarea>
          </label>
        </div>
      </section>

      <LiniesEditor
        v-model:linies="form.linies"
        :iva-per-defecte="form.iva_percentatge"
        :show-totals="true"
        titol="Línies de factura"
      />

      <section class="bg-white rounded shadow p-6 space-y-3">
        <h3 class="text-lg font-semibold">Resum</h3>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
          <p><strong>Subtotal:</strong> {{ subtotal.toFixed(2) }} €</p>
          <p><strong>IVA:</strong> {{ ivaImport.toFixed(2) }} €</p>
          <p><strong>IRPF:</strong> {{ irpfImport.toFixed(2) }} €</p>
          <p><strong>Total:</strong> {{ total.toFixed(2) }} €</p>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded border disabled:opacity-50"
            :disabled="saving"
            @click="handleSaveAsTemplate"
          >
            {{ saving ? 'Guardant...' : 'Desar com plantilla' }}
          </button>
          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
            :disabled="saving"
            @click="handleSubmit"
          >
            {{ saving ? 'Guardant...' : isEdit ? 'Actualitzar factura' : 'Crear factura' }}
          </button>
        </div>
      </section>

      <div
        v-if="showZeroPriceModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
      >
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl space-y-4">
          <h4 class="text-lg font-semibold text-gray-900">Revisió de preus</h4>
          <p class="text-sm text-gray-700">
            La factura conté un objecte amb valor de 0€.
          </p>
          <p class="text-sm text-gray-700">
            Estàs segur que vols {{ isEdit ? 'actualitzar' : 'crear' }} la factura?
          </p>
          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded border"
              :disabled="saving"
              @click="cancelZeroPriceWarning"
            >
              Cancel·lar
            </button>
            <button
              type="button"
              class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
              :disabled="saving"
              @click="confirmZeroPriceWarning"
            >
              {{ saving ? 'Enviant...' : 'D\'acord, enviar' }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import FormMessages from '@/components/FormMessages.vue'
import LiniesEditor, { IVA_OPTIONS, ivaLiniaImport, type LiniaForm, totalLinia } from '@/components/LiniesEditor.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import { getClients, type Client } from '@/services/clients'
import { createFactura, getFactura, updateFactura, type FacturaLiniaPayload, type FacturaPayload } from '@/services/factures'
import { createPlantilla, getPlantilla, type PlantillaPayload } from '@/services/plantilles'

type EstatFactura = 'esborrany' | 'emesa' | 'cancel·lada' | 'cobrada'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))

const clients = ref<Client[]>([])
const saving = ref(false)
const error = ref('')
const success = ref('')
const showZeroPriceModal = ref(false)
const pendingPayload = ref<FacturaPayload | null>(null)

const form = reactive({
  client_id: 0,
  data_emisio: '',
  data_venciment: '',
  estat: 'esborrany' as EstatFactura,
  iva_percentatge: 21,
  irpf_percentatge: 0,
  metode_pagament: '',
  notes: '',
  linies: [] as LiniaForm[]
})

const { initialLoading, runInitialLoad } = useInitialLoading()

function todayDate() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

const subtotal = computed(() => {
  return form.linies.reduce((acc, linia) => acc + totalLinia(linia), 0)
})

const ivaImport = computed(() => {
  return form.linies.reduce((acc, linia) => acc + ivaLiniaImport(linia), 0)
})
const irpfImport = computed(() => subtotal.value * ((Number(form.irpf_percentatge) || 0) / 100))
const total = computed(() => subtotal.value + ivaImport.value - irpfImport.value)

async function loadClients() {
  const response = await getClients({ page: 1, limit: 100 })
  clients.value = response.data ?? []
}

function mapLiniesPayload(): FacturaLiniaPayload[] {
  return form.linies.map((linia) => ({
    descripcio: linia.descripcio,
    quantitat: Number(linia.quantitat),
    preu_unitari: Number(linia.preu_unitari),
    iva_percentatge: Number(linia.iva_percentatge) || Number(form.iva_percentatge) || 0,
    descompte: Number(linia.descompte) || 0
  }))
}

function validateForm() {
  if (!form.client_id) {
    return 'Has de seleccionar un client.'
  }

  if (!form.data_emisio) {
    return 'La data d\'emissió és obligatòria.'
  }

  if (form.linies.length === 0) {
    return 'La factura ha de tenir almenys una línia.'
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

function hasZeroPriceLinia() {
  return form.linies.some((linia) => Number(linia.preu_unitari) === 0)
}

async function submitFactura(payload: FacturaPayload) {
  if (isEdit.value) {
    const id = route.params.id as string
    const response = await updateFactura(id, payload)
    success.value = 'Factura actualitzada correctament.'
    const facturaId = response?.data?.factura?.id ?? id
    await router.push(`/factures/${facturaId}`)
  } else {
    const response = await createFactura(payload)
    success.value = 'Factura creada correctament.'
    const facturaId = response?.data?.factura?.id

    if (facturaId) {
      await router.push(`/factures/${facturaId}`)
    }
  }
}

async function loadFacturaForEdit(id: string) {
  const response = await getFactura(id)
  const factura = response?.data?.factura
  const linies = response?.data?.linies ?? []

  if (!factura) {
    throw new Error('Factura no trobada')
  }

  if (factura.estat !== 'esborrany') {
    await router.replace(`/factures/${factura.id}`)
    return
  }

  form.client_id = Number(factura.client_id) || 0
  form.data_emisio = factura.data_emisio ?? ''
  form.data_venciment = factura.data_venciment ?? ''
  form.estat = (factura.estat ?? 'esborrany') as EstatFactura
  const ivaCarregat = Number(factura.iva_percentatge ?? 21)
  form.iva_percentatge = IVA_OPTIONS.includes(ivaCarregat) ? ivaCarregat : 21
  form.irpf_percentatge = Number(factura.irpf_percentatge ?? 0)
  form.metode_pagament = factura.metode_pagament ?? ''
  form.notes = factura.notes ?? ''
  form.linies = linies.map((linia: any) => ({
    descripcio: linia.descripcio ?? '',
    quantitat: Number(linia.quantitat ?? 1),
    preu_unitari: Number(linia.preu_unitari ?? 0),
    iva_percentatge: Number(linia.iva_percentatge ?? factura.iva_percentatge ?? 21),
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

async function loadPlantillaForCreate(id: string) {
  const response = await getPlantilla(id)
  const plantilla = response?.data?.plantilla
  const linies = response?.data?.linies ?? []

  if (!plantilla) {
    throw new Error('Plantilla no trobada')
  }

  const ivaPlantilla = Number(plantilla.iva_percentatge ?? 21)
  form.iva_percentatge = IVA_OPTIONS.includes(ivaPlantilla) ? ivaPlantilla : 21
  form.irpf_percentatge = Number(plantilla.irpf_percentatge ?? 0)
  form.metode_pagament = plantilla.metode_pagament ?? ''
  form.notes = plantilla.notes_plantilla ?? ''

  form.linies = linies.map((linia: any) => ({
    descripcio: linia.descripcio ?? '',
    quantitat: Number(linia.quantitat ?? 1),
    preu_unitari: Number(linia.preu_unitari ?? 0),
    iva_percentatge: Number(linia.iva_percentatge ?? plantilla.iva_percentatge ?? form.iva_percentatge ?? 21),
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
  success.value = ''

  const formError = validateForm()
  if (formError) {
    error.value = formError
    return
  }

  const payload: FacturaPayload = {
    client_id: form.client_id,
    data_emisio: form.data_emisio,
    data_venciment: form.data_venciment || undefined,
    estat: form.estat,
    iva_percentatge: Number(form.iva_percentatge) || 0,
    irpf_percentatge: Number(form.irpf_percentatge) || 0,
    metode_pagament: form.metode_pagament || undefined,
    notes: form.notes || undefined,
    linies: mapLiniesPayload()
  }

  if (hasZeroPriceLinia()) {
    pendingPayload.value = payload
    showZeroPriceModal.value = true
    return
  }

  await submitWithPayload(payload)
}

async function submitWithPayload(payload: FacturaPayload) {
  saving.value = true
  try {
    await submitFactura(payload)
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut desar la factura.'
  } finally {
    saving.value = false
  }
}

function cancelZeroPriceWarning() {
  showZeroPriceModal.value = false
  pendingPayload.value = null
}

async function confirmZeroPriceWarning() {
  if (!pendingPayload.value) {
    return
  }

  showZeroPriceModal.value = false
  await submitWithPayload(pendingPayload.value)
  pendingPayload.value = null
}

async function handleSaveAsTemplate() {
  error.value = ''
  success.value = ''

  const formError = validateForm()
  if (formError) {
    error.value = formError
    return
  }

  const nomPlantilla = window.prompt('Nom de la plantilla:', isEdit.value ? `Plantilla ${route.params.id}` : 'Nova plantilla')?.trim()
  if (!nomPlantilla) {
    return
  }

  saving.value = true

  const payload: PlantillaPayload = {
    nom: nomPlantilla,
    iva_percentatge: Number(form.iva_percentatge) || 0,
    irpf_percentatge: Number(form.irpf_percentatge) || 0,
    metode_pagament: form.metode_pagament || undefined,
    notes_plantilla: form.notes || undefined,
    linies: mapLiniesPayload()
  }

  try {
    await createPlantilla(payload)
    success.value = 'Plantilla desada correctament.'
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut desar la plantilla.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    try {
      await loadClients()

      if (isEdit.value) {
        await loadFacturaForEdit(route.params.id as string)
      } else {
        form.data_emisio = todayDate()

        const plantillaId = route.query.plantilla_id
        if (typeof plantillaId === 'string' && plantillaId.trim() !== '') {
          await loadPlantillaForCreate(plantillaId)
        } else {
          form.linies.push({
            descripcio: '',
            quantitat: 1,
            preu_unitari: 0,
            iva_percentatge: Number(form.iva_percentatge) || 21,
            descompte: 0
          })
        }
      }
    } catch (requestError: any) {
      error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de factura.'
    }
  })
})
</script>
