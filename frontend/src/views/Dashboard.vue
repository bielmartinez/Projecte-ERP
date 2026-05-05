<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LinearScale,
  Title,
  Tooltip
} from 'chart.js'
import PageHeader from '@/components/PageHeader.vue'
import { useInitialLoading } from '@/composables/useInitialLoading'
import {
  getDashboardGrafiques,
  getDashboardResum,
  getFacturesPendents,
  getQuotesProperes,
  type DashboardGrafiques,
  type DashboardResum,
  type FacturaPendent,
  type QuotaPropera
} from '@/services/dashboard'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement)

const router = useRouter()
const { initialLoading, runInitialLoad } = useInitialLoading()

const resum = ref<DashboardResum | null>(null)
const grafiques = ref<DashboardGrafiques | null>(null)
const facturesPendents = ref<FacturaPendent[]>([])
const quotesProperes = ref<QuotaPropera[]>([])

const chartEvolucioData = computed(() => {
  const items = grafiques.value?.evolucio_mensual ?? []
  return {
    labels: items.map((i) => i.mes),
    datasets: [
      {
        label: 'Ingressos',
        backgroundColor: '#22c55e',
        data: items.map((i) => Number(i.ingressos))
      },
      {
        label: 'Despeses',
        backgroundColor: '#ef4444',
        data: items.map((i) => Number(i.despeses))
      }
    ]
  }
})

const chartEvolucioOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' as const },
    title: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value: string | number) => value + ' €'
      }
    }
  }
}))

const chartCategoriesData = computed(() => {
  const items = grafiques.value?.distribucio_categories ?? []
  const colors = ['#3b82f6', '#ef4444', '#f59e0b', '#22c55e', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316']
  return {
    labels: items.map((i) => i.categoria),
    datasets: [
      {
        data: items.map((i) => Number(i.total)),
        backgroundColor: items.map((_, idx) => colors[idx % colors.length])
      }
    ]
  }
})

const chartCategoriesOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' as const }
  }
}))

const hasEvolucioData = computed(() => {
  const items = grafiques.value?.evolucio_mensual ?? []
  if (items.length === 0) {
    return false
  }

  return items.some((item) => Number(item.ingressos) !== 0 || Number(item.despeses) !== 0)
})

const hasDistribucioData = computed(() => {
  const items = grafiques.value?.distribucio_categories ?? []
  return items.length > 0
})

function formatMoneda(valor: number | string | null | undefined): string {
  return Number(valor ?? 0).toFixed(2) + ' €'
}

function formatData(data: string | null): string {
  if (!data) {
    return '—'
  }

  const parts = data.split('-')
  if (parts.length !== 3) {
    return data
  }

  return `${parts[2]}/${parts[1]}/${parts[0]}`
}

function formatPeriode(mes: string | null | undefined): string {
  if (!mes) {
    return '—'
  }

  const parts = mes.split('-')
  if (parts.length !== 2) {
    return mes
  }

  const mesosCatalans = [
    'Gener',
    'Febrer',
    'Març',
    'Abril',
    'Maig',
    'Juny',
    'Juliol',
    'Agost',
    'Setembre',
    'Octubre',
    'Novembre',
    'Desembre'
  ]

  const indexMes = Number(parts[1]) - 1
  if (indexMes < 0 || indexMes > 11) {
    return mes
  }

  return `${mesosCatalans[indexMes]} ${parts[0]}`
}

function rutaFactura(id: number): string {
  return router.resolve({ path: `/factures/${id}` }).fullPath
}

function rutaQuota(id: number): string {
  return router.resolve({ path: `/quotes/${id}` }).fullPath
}

function normalitzarResum(valor: DashboardResum): DashboardResum {
  return {
    mes_actual: {
      periode: valor.mes_actual?.periode ?? '',
      ingressos: Number(valor.mes_actual?.ingressos ?? 0),
      despeses: Number(valor.mes_actual?.despeses ?? 0),
      benefici: Number(valor.mes_actual?.benefici ?? 0)
    },
    mes_anterior: {
      periode: valor.mes_anterior?.periode ?? '',
      ingressos: Number(valor.mes_anterior?.ingressos ?? 0),
      despeses: Number(valor.mes_anterior?.despeses ?? 0),
      benefici: Number(valor.mes_anterior?.benefici ?? 0)
    }
  }
}

function normalitzarGrafiques(valor: DashboardGrafiques): DashboardGrafiques {
  return {
    evolucio_mensual: (valor.evolucio_mensual ?? []).map((item) => ({
      mes: item.mes,
      ingressos: Number(item.ingressos ?? 0),
      despeses: Number(item.despeses ?? 0)
    })),
    distribucio_categories: (valor.distribucio_categories ?? []).map((item) => ({
      categoria: item.categoria,
      total: Number(item.total ?? 0)
    }))
  }
}

function normalitzarFactures(valor: FacturaPendent[]): FacturaPendent[] {
  return (valor ?? []).map((item) => ({
    id: Number(item.id),
    numero_factura: item.numero_factura,
    data_emisio: item.data_emisio,
    data_venciment: item.data_venciment ?? null,
    total: Number(item.total ?? 0),
    estat: item.estat,
    client_nom: item.client_nom,
    import_cobrat: Number(item.import_cobrat ?? 0),
    import_pendent: Number(item.import_pendent ?? 0)
  }))
}

function normalitzarQuotes(valor: QuotaPropera[]): QuotaPropera[] {
  return (valor ?? []).map((item) => ({
    id: Number(item.id),
    nom: item.nom,
    import: Number(item.import ?? 0),
    periodicitat: item.periodicitat,
    periodes_pendents_count: Number(item.periodes_pendents_count ?? 0),
    proper_venciment: item.proper_venciment ?? null,
    categoria_nom: item.categoria_nom ?? null
  }))
}

onMounted(() => {
  runInitialLoad(async () => {
    const [resResum, resGrafiques, resFactures, resQuotes] = await Promise.allSettled([
      getDashboardResum(),
      getDashboardGrafiques(),
      getFacturesPendents(),
      getQuotesProperes()
    ])

    if (resResum.status === 'fulfilled' && resResum.value.status === 'ok') {
      resum.value = normalitzarResum(resResum.value.data)
    }

    if (resGrafiques.status === 'fulfilled' && resGrafiques.value.status === 'ok') {
      grafiques.value = normalitzarGrafiques(resGrafiques.value.data)
    }

    if (resFactures.status === 'fulfilled' && resFactures.value.status === 'ok') {
      facturesPendents.value = normalitzarFactures(resFactures.value.data)
    }

    if (resQuotes.status === 'fulfilled' && resQuotes.value.status === 'ok') {
      quotesProperes.value = normalitzarQuotes(resQuotes.value.data)
    }
  })
})
</script>

<template>
  <div>
    <div class="mb-6">
      <PageHeader title="Dashboard" />
    </div>

    <div v-if="initialLoading" class="space-y-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="index in 4" :key="`sk-card-${index}`" class="bg-white p-5 rounded-lg shadow">
          <div class="h-4 w-28 bg-gray-200 rounded animate-pulse mb-4"></div>
          <div class="h-8 w-36 bg-gray-200 rounded animate-pulse mb-2"></div>
          <div class="h-3 w-24 bg-gray-200 rounded animate-pulse"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white p-5 rounded-lg shadow">
          <div class="h-5 w-40 bg-gray-200 rounded animate-pulse mb-4"></div>
          <div class="h-80 bg-gray-200 rounded animate-pulse"></div>
        </div>
        <div class="bg-white p-5 rounded-lg shadow">
          <div class="h-5 w-40 bg-gray-200 rounded animate-pulse mb-4"></div>
          <div class="h-80 bg-gray-200 rounded animate-pulse"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-lg shadow">
          <div class="h-5 w-56 bg-gray-200 rounded animate-pulse mb-4"></div>
          <div class="space-y-3">
            <div v-for="index in 4" :key="`sk-fact-${index}`" class="h-10 bg-gray-200 rounded animate-pulse"></div>
          </div>
        </div>
        <div class="bg-white p-5 rounded-lg shadow">
          <div class="h-5 w-52 bg-gray-200 rounded animate-pulse mb-4"></div>
          <div class="space-y-3">
            <div v-for="index in 4" :key="`sk-quo-${index}`" class="h-14 bg-gray-200 rounded animate-pulse"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-5 rounded-lg shadow">
          <p class="text-sm text-gray-500 mb-2">Ingressos del mes</p>
          <p class="text-2xl font-bold text-green-600">
            {{ resum ? formatMoneda(resum.mes_actual.ingressos) : '0,00 €' }}
          </p>
          <p class="text-xs text-gray-500 mt-2">
            Mes anterior: {{ resum ? formatMoneda(resum.mes_anterior.ingressos) : '0,00 €' }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
          <p class="text-sm text-gray-500 mb-2">Despeses del mes</p>
          <p class="text-2xl font-bold text-red-600">
            {{ resum ? formatMoneda(resum.mes_actual.despeses) : '0,00 €' }}
          </p>
          <p class="text-xs text-gray-500 mt-2">
            Mes anterior: {{ resum ? formatMoneda(resum.mes_anterior.despeses) : '0,00 €' }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
          <p class="text-sm text-gray-500 mb-2">Benefici del mes</p>
          <p
            class="text-2xl font-bold"
            :class="(resum?.mes_actual.benefici ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'"
          >
            {{ resum ? formatMoneda(resum.mes_actual.benefici) : '0,00 €' }}
          </p>
          <p class="text-xs text-gray-500 mt-2">
            Mes anterior: {{ resum ? formatMoneda(resum.mes_anterior.benefici) : '0,00 €' }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
          <p class="text-sm text-gray-500 mb-2">Període</p>
          <p class="text-2xl font-bold text-gray-800">
            {{ formatPeriode(resum?.mes_actual.periode) }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white p-5 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Evolució mensual</h3>
          <div class="h-80">
            <Bar v-if="hasEvolucioData" :data="chartEvolucioData" :options="chartEvolucioOptions" />
            <div v-else class="h-full flex items-center justify-center text-gray-500 text-sm">
              No hi ha dades disponibles
            </div>
          </div>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Despeses per categoria</h3>
          <div class="h-80">
            <Doughnut v-if="hasDistribucioData" :data="chartCategoriesData" :options="chartCategoriesOptions" />
            <div v-else class="h-full flex items-center justify-center text-gray-500 text-sm">
              No hi ha despeses aquest mes
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Factures pendents de cobrament</h3>

          <p v-if="facturesPendents.length === 0" class="text-sm text-gray-500">No hi ha factures pendents</p>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-500 uppercase">
                <tr>
                  <th class="py-2 pr-3">Factura</th>
                  <th class="py-2 pr-3">Client</th>
                  <th class="py-2 pr-3">Total</th>
                  <th class="py-2 pr-3">Pendent</th>
                  <th class="py-2">Estat</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="factura in facturesPendents" :key="factura.id" class="border-b border-gray-100">
                  <td class="py-3 pr-3 font-medium">
                    <router-link :to="rutaFactura(factura.id)" class="text-blue-600 hover:underline">
                      {{ factura.numero_factura }}
                    </router-link>
                  </td>
                  <td class="py-3 pr-3 text-gray-700">{{ factura.client_nom }}</td>
                  <td class="py-3 pr-3 text-gray-700">{{ formatMoneda(factura.total) }}</td>
                  <td class="py-3 pr-3 text-red-600 font-medium">{{ formatMoneda(factura.import_pendent) }}</td>
                  <td class="py-3">
                    <span
                      class="inline-flex px-2 py-1 rounded-full text-xs font-medium"
                      :class="factura.estat === 'parcialment_cobrada'
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-yellow-100 text-yellow-800'"
                    >
                      {{ factura.estat }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Quotes pendents de pagament</h3>

          <p v-if="quotesProperes.length === 0" class="text-sm text-gray-500">No hi ha quotes pendents</p>

          <div v-else class="space-y-3">
            <div
              v-for="quota in quotesProperes"
              :key="quota.id"
              class="border border-gray-100 rounded-lg p-4"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <router-link :to="rutaQuota(quota.id)" class="text-blue-600 hover:underline font-medium">
                    {{ quota.nom }}
                  </router-link>
                  <p class="text-sm text-gray-500 mt-1">{{ quota.categoria_nom ?? 'Sense categoria' }}</p>
                </div>
                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  {{ quota.periodes_pendents_count }} pendents
                </span>
              </div>

              <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
                <p class="text-gray-700">
                  <span class="text-gray-500">Import:</span>
                  {{ formatMoneda(quota.import) }}
                </p>
                <p class="text-gray-700">
                  <span class="text-gray-500">Periodicitat:</span>
                  {{ quota.periodicitat }}
                </p>
                <p class="text-gray-700">
                  <span class="text-gray-500">Proper venciment:</span>
                  {{ formatData(quota.proper_venciment) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>