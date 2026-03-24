import { ref } from 'vue'

export function useInitialLoading() {
  const initialLoading = ref(true)

  async function runInitialLoad(task: () => Promise<void>) {
    initialLoading.value = true

    try {
      await task()
    } finally {
      initialLoading.value = false
    }
  }

  return {
    initialLoading,
    runInitialLoad
  }
}
