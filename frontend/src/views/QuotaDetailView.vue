<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Detall de quota</h2>
      <div class="flex gap-2">
        <RouterLink to="/quotes" class="text-blue-600 hover:underline">Tornar al llistat</RouterLink>
        <RouterLink
          v-if="quota"
          :to="{ path: '/quotes', query: { edit: String(quota.id) } }"
          class="text-gray-700 hover:underline"
        >
          Editar
        </RouterLink>
      </div>
    </div>

    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-44 rounded bg-gray-200"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="h-4 w-56 rounded bg-gray-200"></div>
          <div class="h-4 w-52 rounded bg-gray-200"></div>
          <div class="h-4 w-40 rounded bg-gray-200"></div>
          <div class="h-4 w-48 rounded bg-gray-200"></div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-64 rounded bg-gray-200"></div>
        <div v-for="row in 3" :key="`pend-${row}`" class="h-10 rounded bg-gray-200"></div>
      </section>

      <section class="bg-white rounded shadow p-6 space-y-3 animate-pulse">
        <div class="h-6 w-52 rounded bg-gray-200"></div>
        <div v-for="row in 3" :key="`hist-${row}`" class="h-10 rounded bg-gray-200"></div>
      </section>
    </div>

    <template v-else>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <section v-if="quota" class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Dades de la quota</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <p><strong>Nom:</strong> {{ quota.nom }}</p>
          <p><strong>Import:</strong> {{ Number(quota.import).toFixed(2) }} €</p>
          <p><strong>Periodicitat:</strong> {{ labelPeriodicitat(quota.periodicitat) }}</p>
          <p><strong>Dia pagament:</strong> {{ quota.dia_pagament }}</p>
          <p><strong>Data inici:</strong> {{ quota.data_inici }}</p>
          <p><strong>Data fi:</strong> {{ quota.data_fi ?? '-' }}</p>
          <p><strong>Categoria:</strong> {{ quota.categoria_nom ?? '-' }}</p>
          <p>
            <strong>Estat:</strong>
            <span
              class="inline-block px-2 py-0.5 rounded text-xs font-medium ml-2"
              :class="activaLabel.activa ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'"
            >
              {{ activaLabel.text }}
            </span>
          </p>
          <p class="md:col-span-2"><strong>Descripció:</strong> {{ quota.descripcio ?? '-' }}</p>
        </div>
      </section>

      <section v-if="quota" class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Períodes pendents de pagament</h3>

        <p v-if="pagamentError" class="text-sm text-red-600">{{ pagamentError }}</p>
        <p v-if="pagamentSuccess" class="text-sm text-green-600">{{ pagamentSuccess }}</p>

        <p v-if="periodesPendents.length === 0" class="text-sm text-green-700 bg-green-50 border border-green-200 rounded p-3">
          Tots els períodes estan al dia
        </p>

        <template v-else>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="py-2 pr-4">Període</th>
                  <th class="py-2 pr-4">Import (€)</th>
                  <th class="py-2 pr-4">Acció</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="periode in periodesPendents" :key="periode.periode" class="border-b">
                  <td class="py-2 pr-4">{{ formatPeriode(periode.periode) }}</td>
                  <td class="py-2 pr-4">{{ Number(periode.import).toFixed(2) }} €</td>
                  <td class="py-2 pr-4">
                    <button
                      type="button"
                      class="bg-gray-900 text-white px-3 py-1.5 rounded hover:bg-gray-800"
                      @click="obrirFormulariPagament(periode)"
                    >
                      Pagar
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="periodeSeleccionat" class="border rounded p-4 space-y-3">
            <h4 class="font-medium text-sm">Pagar {{ formatPeriode(periodeSeleccionat) }}</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label class="space-y-1">
                <span class="text-sm font-medium text-gray-700">Import (€)</span>
                <input
                  v-model.number="formulariPagament.import"
                  type="number"
                  min="0.01"
                  step="0.01"
                  class="border rounded px-3 py-2 w-full"
                />
              </label>

              <label class="space-y-1 md:col-span-2">
                <span class="text-sm font-medium text-gray-700">Notes (opcional)</span>
                <textarea
                  v-model="formulariPagament.notes"
                  class="border rounded px-3 py-2 w-full"
                  rows="2"
                  placeholder="Notes del pagament"
                ></textarea>
              </label>
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
                :disabled="pagamentLoading"
                @click="confirmarPagament"
              >
                {{ pagamentLoading ? 'Guardant...' : 'Confirmar pagament' }}
              </button>
              <button
                type="button"
                class="px-4 py-2 rounded border"
                :disabled="pagamentLoading"
                @click="cancelarPagament"
              >
                Cancel·lar
              </button>
            </div>
          </div>
        </template>
      </section>

      <section v-if="quota" class="bg-white rounded shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Historial de pagaments</h3>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Període</th>
                <th class="py-2 pr-4">Data pagament</th>
                <th class="py-2 pr-4">Import (€)</th>
                <th class="py-2 pr-4">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pagament in pagaments" :key="pagament.id" class="border-b">
                <td class="py-2 pr-4">{{ formatPeriode(pagament.periode_corresponent) }}</td>
                <td class="py-2 pr-4">{{ pagament.data_pagament }}</td>
                <td class="py-2 pr-4">{{ Number(pagament.import).toFixed(2) }} €</td>
                <td class="py-2 pr-4">{{ pagament.notes ?? '-' }}</td>
              </tr>
              <tr v-if="pagaments.length === 0">
                <td colspan="4" class="py-4 text-gray-500">No hi ha pagaments registrats</td>
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
import { RouterLink, useRoute } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { getQuota, pagarQuota, type PagamentQuota, type PeriodePendent, type Periodicitat, type Quota } from '@/services/quotes'

interface QuotaDetall extends Quota {
  periodes_pendents?: PeriodePendent[]
  pagaments?: PagamentQuota[]
}

const route = useRoute()

const quota = ref<QuotaDetall | null>(null)
const periodesPendents = ref<PeriodePendent[]>([])
const pagaments = ref<PagamentQuota[]>([])

const error = ref('')
const pagamentError = ref('')
const pagamentSuccess = ref('')
const pagamentLoading = ref(false)

const periodeSeleccionat = ref<string | null>(null)
const formulariPagament = reactive({
  import: 0,
  notes: ''
})

const { initialLoading, runInitialLoad } = useInitialLoading()

const activaLabel = computed(() => {
  const activa = normalitzarActiva(quota.value?.activa)
  return {
    activa,
    text: activa ? 'Activa' : 'Inactiva'
  }
})

function formatPeriode(dateStr: string): string {
  const mesos = ['Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny', 'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre']
  const d = new Date(dateStr + 'T00:00:00')
  if (Number.isNaN(d.getTime())) {
    return dateStr
  }
  return `${mesos[d.getMonth()]} ${d.getFullYear()}`
}

function labelPeriodicitat(periodicitat: Periodicitat): string {
  if (periodicitat === 'trimestral') {
    return 'Trimestral'
  }

  if (periodicitat === 'anual') {
    return 'Anual'
  }

  return 'Mensual'
}

function normalitzarActiva(valor: unknown): boolean {
  if (typeof valor === 'boolean') {
    return valor
  }

  if (typeof valor === 'number') {
    return valor === 1
  }

  if (typeof valor === 'string') {
    return ['1', 'true', 't', 'on'].includes(valor.toLowerCase())
  }

  return false
}

async function loadQuota() {
  error.value = ''

  try {
    const response = await getQuota(route.params.id as string)
    const data = (response?.data ?? null) as QuotaDetall | null

    quota.value = data
    periodesPendents.value = data?.periodes_pendents ?? []
    pagaments.value = data?.pagaments ?? []

    if (periodeSeleccionat.value) {
      const encaraPendent = periodesPendents.value.some((periode) => periode.periode === periodeSeleccionat.value)
      if (!encaraPendent) {
        periodeSeleccionat.value = null
      }
    }
  } catch (requestError: any) {
    error.value = requestError?.response?.data?.message ?? 'No s\'ha pogut carregar la quota.'
  }
}

function obrirFormulariPagament(periode: PeriodePendent) {
  periodeSeleccionat.value = periode.periode
  formulariPagament.import = Number(periode.import)
  formulariPagament.notes = ''
  pagamentError.value = ''
  pagamentSuccess.value = ''
}

function cancelarPagament() {
  periodeSeleccionat.value = null
  formulariPagament.import = 0
  formulariPagament.notes = ''
}

async function confirmarPagament() {
  if (!quota.value || !periodeSeleccionat.value) {
    return
  }

  pagamentError.value = ''
  pagamentSuccess.value = ''

  if (Number(formulariPagament.import) <= 0) {
    pagamentError.value = 'L\'import ha de ser major que 0.'
    return
  }

  pagamentLoading.value = true

  try {
    await pagarQuota(quota.value.id, {
      periode_corresponent: periodeSeleccionat.value,
      import: Number(formulariPagament.import),
      notes: formulariPagament.notes.trim() || undefined
    })

    pagamentSuccess.value = 'Pagament registrat correctament.'
    cancelarPagament()
    await loadQuota()
  } catch (requestError: any) {
    pagamentError.value = requestError?.response?.data?.message ?? 'No s\'ha pogut registrar el pagament.'
  } finally {
    pagamentLoading.value = false
  }
}

onMounted(() => {
  runInitialLoad(async () => {
    await loadQuota()
  })
})
</script>
