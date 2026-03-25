<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">{{ isEdit ? 'Editar factura' : 'Nova factura' }}</h2>
      <RouterLink to="/factures" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
    </div>

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
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="text-sm text-green-600">{{ success }}</p>

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
            <span class="text-sm font-medium text-gray-700">IVA (%)</span>
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

      <section class="bg-white rounded shadow p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Línies de factura</h3>
          <button type="button" class="px-3 py-1 border rounded" @click="addLinia">Afegir línia</button>
        </div>

        <div class="space-y-3">
          <div class="hidden md:grid md:grid-cols-12 gap-3 text-xs font-semibold text-gray-500 uppercase">
            <span class="md:col-span-5">Descripció</span>
            <span class="md:col-span-2">Quantitat</span>
            <span class="md:col-span-2">Preu unitari</span>
            <span class="md:col-span-2">Descompte %</span>
            <span class="md:col-span-1">Acció</span>
          </div>

          <div
            v-for="(linia, index) in form.linies"
            :key="`linia-${index}`"
            class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start"
          >
            <input
              v-model="linia.descripcio"
              type="text"
              class="border rounded px-3 py-2 md:col-span-5"
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

            <div class="md:col-span-12 text-sm text-gray-600">
              Total línia: {{ totalLinia(linia).toFixed(2) }} €
            </div>
          </div>
        </div>
      </section>

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
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
            :disabled="saving"
            @click="handleSubmit"
          >
            {{ saving ? 'Guardant...' : isEdit ? 'Actualitzar factura' : 'Crear factura' }}
          </button>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { getClients, type Client } from '@/services/clients'
import { createFactura, getFactura, updateFactura, type FacturaLiniaPayload, type FacturaPayload } from '@/services/factures'

type EstatFactura = 'esborrany' | 'emesa' | 'cancel·lada' | 'cobrada'

interface FormLinia {
  descripcio: string
  quantitat: number
  preu_unitari: number
  descompte: number
}

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))

const clients = ref<Client[]>([])
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  client_id: 0,
  data_emisio: '',
  data_venciment: '',
  estat: 'esborrany' as EstatFactura,
  iva_percentatge: 21,
  irpf_percentatge: 0,
  metode_pagament: '',
  notes: '',
  linies: [] as FormLinia[]
})

const IVA_OPTIONS = [0, 4, 10, 21]

const { initialLoading, runInitialLoad } = useInitialLoading()

function todayDate() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

function addLinia() {
  form.linies.push({
    descripcio: '',
    quantitat: 1,
    preu_unitari: 0,
    descompte: 0
  })
}

function removeLinia(index: number) {
  form.linies.splice(index, 1)
}

function totalLinia(linia: FormLinia) {
  const base = (Number(linia.quantitat) || 0) * (Number(linia.preu_unitari) || 0)
  const discount = Number(linia.descompte) || 0
  const baseAmbDescompte = base * (1 - discount / 100)
  return Math.max(0, baseAmbDescompte)
}

const subtotal = computed(() => {
  return form.linies.reduce((acc, linia) => acc + totalLinia(linia), 0)
})

const ivaImport = computed(() => subtotal.value * ((Number(form.iva_percentatge) || 0) / 100))
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
    iva_percentatge: Number(form.iva_percentatge) || 0,
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
    return 'L\'IVA només pot ser 0, 4, 10 o 21.'
  }

  const liniaBuida = form.linies.find((linia) => !linia.descripcio.trim())
  if (liniaBuida) {
    return 'Totes les línies han de tenir descripció.'
  }

  const liniaInvalid = form.linies.find((linia) => Number(linia.quantitat) <= 0)
  if (liniaInvalid) {
    return 'La quantitat ha de ser major que 0 a totes les línies.'
  }

  return ''
}

async function loadFacturaForEdit(id: string) {
  const response = await getFactura(id)
  const factura = response?.data?.factura
  const linies = response?.data?.linies ?? []

  if (!factura) {
    throw new Error('Factura no trobada')
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
    descompte: Number(linia.descompte ?? 0)
  }))

  if (form.linies.length === 0) {
    addLinia()
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

  saving.value = true

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

  try {
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
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut desar la factura.'
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
        addLinia()
      }
    } catch (requestError: any) {
      error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar el formulari de factura.'
    }
  })
})
</script>
