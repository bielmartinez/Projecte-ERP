<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-2xl font-semibold">Clients</h2>
      <RouterLink
        to="/clients/nou"
        class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
      >
        Nou client
      </RouterLink>
    </div>

    <!-- Div de càrrega  de la pagina-->
    <div v-if="initialLoading" class="space-y-6" aria-busy="true" aria-live="polite">
      <section class="bg-white rounded shadow p-4 space-y-4 animate-pulse">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
          <div class="h-10 rounded bg-gray-200 w-full md:max-w-md"></div>
          <div class="h-10 rounded bg-gray-200 w-24"></div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4"><div class="h-4 w-16 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-20 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-12 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-14 rounded bg-gray-200"></div></th>
                <th class="py-2 pr-4"><div class="h-4 w-16 rounded bg-gray-200"></div></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in 5" :key="row" class="border-b">
                <td class="py-2 pr-4"><div class="h-4 w-36 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-28 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-20 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-32 rounded bg-gray-200"></div></td>
                <td class="py-2 pr-4"><div class="h-4 w-24 rounded bg-gray-200"></div></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between">
          <div class="h-4 w-32 rounded bg-gray-200"></div>
          <div class="flex gap-2">
            <div class="h-8 w-20 rounded bg-gray-200"></div>
            <div class="h-8 w-20 rounded bg-gray-200"></div>
          </div>
        </div>
      </section>
    </div>

    <template v-else>

      <section class="bg-white rounded shadow p-4 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
          <input
            v-model="search"
            type="text"
            class="border rounded px-3 py-2 w-full md:max-w-md"
            placeholder="Cercar per nom, empresa, NIF o email"
            @keyup.enter="loadClients(1)"
          />
          <button
            type="button"
            class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800"
            @click="loadClients(1)"
          >
            Cercar
          </button>
        </div>

        <p v-if="listError" class="text-sm text-red-600">{{ listError }}</p>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 pr-4">Nom</th>
                <th class="py-2 pr-4">Empresa</th>
                <th class="py-2 pr-4">NIF</th>
                <th class="py-2 pr-4">Email</th>
                <th class="py-2 pr-4">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in clients" :key="client.id" class="border-b">
                <td class="py-2 pr-4">{{ client.nom }} {{ client.cognoms ?? '' }}</td>
                <td class="py-2 pr-4">{{ client.nom_empresa ?? '-' }}</td>
                <td class="py-2 pr-4">{{ client.nif ?? '-' }}</td>
                <td class="py-2 pr-4">{{ client.email ?? '-' }}</td>
                <td class="py-2 pr-4 flex gap-2">
                  <RouterLink
                    :to="`/clients/${client.id}`"
                    class="text-blue-600 hover:underline"
                  >
                    Veure
                  </RouterLink>
                  <RouterLink :to="`/clients/${client.id}/editar`" class="text-gray-700 hover:underline">
                    Editar
                  </RouterLink>
                  <button type="button" class="text-red-600 hover:underline" @click="handleDelete(client.id)">
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && clients.length === 0">
                <td colspan="5" class="py-4 text-gray-500">No hi ha clients</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Pàgina {{ meta.page }} de {{ meta.total_pages || 1 }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page <= 1"
              @click="loadClients(meta.page - 1)"
            >
              Anterior
            </button>
            <button
              type="button"
              class="px-3 py-1 border rounded disabled:opacity-50"
              :disabled="meta.page >= (meta.total_pages || 1)"
              @click="loadClients(meta.page + 1)"
            >
              Següent
            </button>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { useInitialLoading } from '@/composables/useInitialLoading'
import { deleteClient, getClients, type Client } from '@/services/clients'

const loading = ref(false)
const listError = ref('')
const search = ref('')
const clients = ref<Client[]>([])

const meta = reactive({
  page: 1,
  limit: 10,
  total: 0,
  total_pages: 1
})
const { initialLoading, runInitialLoad } = useInitialLoading()

async function loadClients(page = 1) {
  loading.value = true
  listError.value = ''

  try {
    const response = await getClients({ page, limit: meta.limit, search: search.value.trim() })
    clients.value = response.data ?? []

    const responseMeta = response.meta ?? {}
    meta.page = responseMeta.page ?? page
    meta.limit = responseMeta.limit ?? meta.limit
    meta.total = responseMeta.total ?? 0
    meta.total_pages = responseMeta.total_pages ?? 1
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'han pogut carregar els clients.'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number) {
  if (!window.confirm('Vols eliminar aquest client?')) {
    return
  }

  listError.value = ''

  try {
    await deleteClient(id)
    await loadClients(meta.page)
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'No s\'ha pogut eliminar el client.'
  }
}

onMounted(() => {
  runInitialLoad(() => loadClients(1))
})
</script>
