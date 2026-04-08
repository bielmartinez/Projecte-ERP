<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Detall de factura</h2>
      <div class="flex gap-2">
        <RouterLink to="/factures" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
        <RouterLink v-if="factura" :to="`/factures/${factura.id}/editar`" class="text-gray-700 hover:underline">
          Editar
        </RouterLink>
        <button
          v-if="factura"
          type="button"
          class="text-gray-700 hover:underline"
          @click="handleDescarregarPdf"
        >
          Descarregar PDF
        </button>
      </div>
    </div>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-40 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="h-4 w-52 rounded bg-gray-200"></div>
          <div class="h-4 w-44 rounded bg-gray-200"></div>
          <div class="h-4 w-36 rounded bg-gray-200"></div>
          <div class="h-4 w-48 rounded bg-gray-200"></div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-36 rounded bg-gray-200"></div>
        <div class="h-3 w-full rounded bg-gray-200"></div>
        <div v-for="row in 3" :key="`cob-${row}`" class="h-10 rounded bg-gray-200"></div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-44 rounded bg-gray-200"></div>
        <div v-for="row in 4" :key="`lin-${row}`" class="h-10 rounded bg-gray-200"></div>
      </section>
    </div>

    <template v-else>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="text-sm text-green-600">{{ success }}</p>

      <section v-if="factura" class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Informació general</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <p><strong>Número:</strong> {{ factura.numero_factura }}</p>
          <p><strong>Client:</strong> {{ clientLabel }}</p>
          <p><strong>Data emissió:</strong> {{ factura.data_emisio }}</p>
          <p><strong>Data venciment:</strong> {{ factura.data_venciment ?? '-' }}</p>
          <p><strong>IVA:</strong> {{ Number(factura.iva_percentatge).toFixed(2) }} %</p>
          <p><strong>IRPF:</strong> {{ Number(factura.irpf_percentatge).toFixed(2) }} %</p>
          <p><strong>Subtotal:</strong> {{ Number(factura.subtotal).toFixed(2) }} €</p>
          <p><strong>IVA import:</strong> {{ Number(factura.iva_import).toFixed(2) }} €</p>
          <p><strong>IRPF import:</strong> {{ Number(factura.irpf_import).toFixed(2) }} €</p>
          <p><strong>Total:</strong> {{ Number(factura.total).toFixed(2) }} €</p>
          <p><strong>Mètode pagament:</strong> {{ factura.metode_pagament ?? '-' }}</p>
          <p><strong>Estat:</strong> {{ factura.estat }}</p>
          <p class="md:col-span-2"><strong>Notes:</strong> {{ factura.notes ?? '-' }}</p>
        </div>

        <div class="flex items-center gap-2">
          <label for="estat" class="text-sm">Canviar estat:</label>
          <select id="estat" v-model="selectedEstat" class="border rounded px-3 py-2 text-sm">
            <option value="esborrany">Esborrany</option>
            <option value="emesa">Emesa</option>
            <option value="parcialment_cobrada">Parcialment cobrada</option>
            <option value="cancel·lada">Cancel·lada</option>
            <option value="cobrada">Cobrada</option>
          </select>
          <button
            type="button"
            class="px-3 py-2 border rounded text-sm disabled:opacity-50"
            :disabled="changingEstat"
            @click="handleCanviarEstat"
          >
            {{ changingEstat ? 'Actualitzant...' : 'Guardar estat' }}
          </button>
        </div>
      </section>

      <section v-if="factura" class="bg-white rounded shadow p-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-lg font-semibold">Cobraments</h3>
          <p class="text-sm text-gray-700">
            Cobrat: {{ cobramentsMeta.total_cobrat.toFixed(2) }} € · Pendent: {{ cobramentsMeta.pendent.toFixed(2) }} €
          </p>
        </div>

        <div class="h-3 w-full rounded-full bg-gray-200 overflow-hidden">
          <div class="h-full bg-green-500 transition-all duration-300" :style="{ width: `${percentatgeCobrat}%` }"></div>
        </div>

        <p v-if="cobramentsError" class="text-sm text-red-600">{{ cobramentsError }}</p>
        <p v-if="cobramentFormError" class="text-sm text-red-600">{{ cobramentFormError }}</p>
        <p v-if="cobramentFormSuccess" class="text-sm text-green-600">{{ cobramentFormSuccess }}</p>

        <div v-if="cobramentsLoading" class="space-y-2 animate-pulse" aria-busy="true" aria-live="polite">
          <div v-for="row in 3" :key="`row-cobraments-${row}`" class="h-10 rounded bg-gray-200"></div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Data</th>
                <th class="py-2 pr-4">Import</th>
                <th class="py-2 pr-4">Mètode</th>
                <th class="py-2 pr-4">Notes</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cobrament in cobraments" :key="cobrament.id" class="border-b">
                <td class="py-2 pr-4">{{ cobrament.data_cobrament }}</td>
                <td class="py-2 pr-4">{{ Number(cobrament.import).toFixed(2) }} €</td>
                <td class="py-2 pr-4">{{ cobrament.metode_pagament ?? '-' }}</td>
                <td class="py-2 pr-4">{{ cobrament.notes ?? '-' }}</td>
                <td class="py-2 pr-4">
                  <button
                    type="button"
                    class="text-red-600 hover:underline"
                    :disabled="cobramentFormLoading"
                    @click="handleEliminarCobrament(cobrament.id)"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="cobraments.length === 0">
                <td colspan="5" class="py-4 text-gray-500">Aquesta factura no té cobraments registrats</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="potRegistrarCobraments" class="border rounded p-4 space-y-3">
          <h4 class="font-medium text-sm">Registrar cobrament</h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="space-y-1">
              <span class="text-sm font-medium text-gray-700">Import (€)</span>
              <input
                v-model.number="cobramentForm.import"
                type="number"
                min="0.01"
                step="0.01"
                class="border rounded px-3 py-2 w-full"
              />
            </label>

            <label class="space-y-1">
              <span class="text-sm font-medium text-gray-700">Data</span>
              <input v-model="cobramentForm.data_cobrament" type="date" class="border rounded px-3 py-2 w-full" />
            </label>

            <label class="space-y-1">
              <span class="text-sm font-medium text-gray-700">Mètode de pagament</span>
              <input
                v-model="cobramentForm.metode_pagament"
                type="text"
                maxlength="50"
                class="border rounded px-3 py-2 w-full"
                placeholder="Transferència, targeta..."
              />
            </label>

            <label class="space-y-1 md:col-span-2">
              <span class="text-sm font-medium text-gray-700">Notes</span>
              <textarea
                v-model="cobramentForm.notes"
                class="border rounded px-3 py-2 w-full"
                rows="2"
                placeholder="Notes opcionals"
              ></textarea>
            </label>
          </div>

          <div class="flex gap-2">
            <button
              type="button"
              class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
              :disabled="cobramentFormLoading"
              @click="handleCrearCobrament"
            >
              {{ cobramentFormLoading ? 'Guardant...' : 'Registrar cobrament' }}
            </button>
          </div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Línies</h3>
          <button
            v-if="factura"
            type="button"
            class="text-red-600 hover:underline text-sm"
            @click="handleDeleteFactura"
          >
            Eliminar factura
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Descripció</th>
                <th class="py-2 pr-4">Quantitat</th>
                <th class="py-2 pr-4">Preu</th>
                <th class="py-2 pr-4">IVA %</th>
                <th class="py-2 pr-4">Desc %</th>
                <th class="py-2 pr-4">Total línia</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="linia in linies" :key="linia.id" class="border-b">
                <td class="py-2 pr-4">{{ linia.descripcio }}</td>
                <td class="py-2 pr-4">{{ Number(linia.quantitat).toFixed(3) }}</td>
                <td class="py-2 pr-4">{{ Number(linia.preu_unitari).toFixed(2) }} €</td>
                <td class="py-2 pr-4">{{ Number(linia.iva_percentatge).toFixed(2) }} %</td>
                <td class="py-2 pr-4">{{ Number(linia.descompte).toFixed(2) }} %</td>
                <td class="py-2 pr-4">{{ Number(linia.total_linia).toFixed(2) }} €</td>
              </tr>
              <tr v-if="linies.length === 0">
                <td colspan="6" class="py-4 text-gray-500">Aquesta factura no té línies</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { crearCobrament, eliminarCobrament, getCobramentsFactura, type Cobrament } from '@/services/cobraments'
import { canviarEstatFactura, descarregarFacturaPdf, deleteFactura, getFactura, type Factura, type FacturaLinia } from '@/services/factures'

type FacturaEstatLocal = 'esborrany' | 'emesa' | 'cancel·lada' | 'cobrada' | 'parcialment_cobrada'

const route = useRoute()
const router = useRouter()

const factura = ref<Factura | null>(null)
const linies = ref<FacturaLinia[]>([])
const client = ref<any>(null)
const cobraments = ref<Cobrament[]>([])

const error = ref('')
const success = ref('')
const cobramentsError = ref('')
const selectedEstat = ref<FacturaEstatLocal>('esborrany')
const changingEstat = ref(false)
const cobramentsLoading = ref(false)
const cobramentFormLoading = ref(false)
const cobramentFormError = ref('')
const cobramentFormSuccess = ref('')

const cobramentsMeta = reactive({
  total_cobrat: 0,
  total_factura: 0,
  pendent: 0
})

const cobramentForm = reactive({
  import: 0,
  data_cobrament: avui(),
  metode_pagament: '',
  notes: ''
})

const { initialLoading, runInitialLoad } = useInitialLoading()

const clientLabel = computed(() => {
  if (!client.value) {
    return '-'
  }

  return `${client.value.nom ?? ''} ${client.value.cognoms ?? ''}`.trim()
})

const potRegistrarCobraments = computed(() => {
  const estat = String(factura.value?.estat ?? '')
  return estat === 'emesa' || estat === 'parcialment_cobrada'
})

const percentatgeCobrat = computed(() => {
  if (cobramentsMeta.total_factura <= 0) {
    return 0
  }

  const percentatge = (cobramentsMeta.total_cobrat / cobramentsMeta.total_factura) * 100
  return Math.max(0, Math.min(100, percentatge))
})

function avui() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

async function loadFactura() {
  error.value = ''

  try {
    const response = await getFactura(route.params.id as string)
    factura.value = response?.data?.factura ?? null
    linies.value = response?.data?.linies ?? []
    client.value = response?.data?.client ?? null

    if (factura.value) {
      selectedEstat.value = String(factura.value.estat) as FacturaEstatLocal
      cobramentsMeta.total_factura = Number(factura.value.total ?? 0)
    }
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar la factura.'
  }
}

async function loadCobraments() {
  if (!factura.value) {
    cobraments.value = []
    cobramentsMeta.total_cobrat = 0
    cobramentsMeta.pendent = 0
    return
  }

  cobramentsLoading.value = true
  cobramentsError.value = ''

  try {
    const response = await getCobramentsFactura(factura.value.id)
    cobraments.value = response?.data ?? []
    cobramentsMeta.total_cobrat = Number(response?.meta?.total_cobrat ?? 0)
    cobramentsMeta.total_factura = Number(response?.meta?.total_factura ?? factura.value.total ?? 0)
    cobramentsMeta.pendent = Number(response?.meta?.pendent ?? 0)
  } catch (requestError: any) {
    cobramentsError.value = requestError?.response?.data?.message ?? 'No s\'han pogut carregar els cobraments.'
  } finally {
    cobramentsLoading.value = false
  }
}

function resetCobramentForm() {
  cobramentForm.import = 0
  cobramentForm.data_cobrament = avui()
  cobramentForm.metode_pagament = ''
  cobramentForm.notes = ''
}

async function recarregarFacturaICobraments() {
  await loadFactura()
  await loadCobraments()
}

async function handleCanviarEstat() {
  if (!factura.value) {
    return
  }

  changingEstat.value = true
  error.value = ''
  success.value = ''

  try {
    await canviarEstatFactura(factura.value.id, selectedEstat.value as any)
    success.value = 'Estat actualitzat correctament.'
    await recarregarFacturaICobraments()
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut canviar l\'estat.'
  } finally {
    changingEstat.value = false
  }
}

async function handleCrearCobrament() {
  if (!factura.value) {
    return
  }

  cobramentFormError.value = ''
  cobramentFormSuccess.value = ''

  if (Number(cobramentForm.import) <= 0) {
    cobramentFormError.value = 'L\'import ha de ser major que 0.'
    return
  }

  if (!cobramentForm.data_cobrament) {
    cobramentFormError.value = 'La data de cobrament és obligatòria.'
    return
  }

  cobramentFormLoading.value = true

  try {
    await crearCobrament(factura.value.id, {
      import: Number(cobramentForm.import),
      data_cobrament: cobramentForm.data_cobrament,
      metode_pagament: cobramentForm.metode_pagament.trim() || undefined,
      notes: cobramentForm.notes.trim() || undefined
    })

    cobramentFormSuccess.value = 'Cobrament registrat correctament.'
    resetCobramentForm()
    await recarregarFacturaICobraments()
  } catch (requestError: any) {
    cobramentFormError.value = requestError?.response?.data?.message ?? 'No s\'ha pogut registrar el cobrament.'
  } finally {
    cobramentFormLoading.value = false
  }
}

async function handleEliminarCobrament(cobramentId: number) {
  if (!factura.value) {
    return
  }

  if (!window.confirm('Vols eliminar aquest cobrament?')) {
    return
  }

  cobramentFormError.value = ''
  cobramentFormSuccess.value = ''
  cobramentsError.value = ''

  try {
    await eliminarCobrament(factura.value.id, cobramentId)
    cobramentFormSuccess.value = 'Cobrament eliminat correctament.'
    await recarregarFacturaICobraments()
  } catch (requestError: any) {
    cobramentsError.value = requestError?.response?.data?.message ?? 'No s\'ha pogut eliminar el cobrament.'
  }
}

async function handleDeleteFactura() {
  if (!factura.value) {
    return
  }

  if (!window.confirm('Vols eliminar aquesta factura?')) {
    return
  }

  error.value = ''

  try {
    await deleteFactura(factura.value.id)
    await router.push('/factures')
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut eliminar la factura.'
  }
}

async function handleDescarregarPdf() {
  if (!factura.value) {
    return
  }

  error.value = ''

  try {
    await descarregarFacturaPdf(factura.value.id)
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut descarregar el PDF.'
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    await loadFactura()
    await loadCobraments()
  })
})
</script>
