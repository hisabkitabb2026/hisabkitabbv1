<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'

interface Props {
  client: AxiosInstance
  path: string | null
}

const props = defineProps<Props>()

const status = ref<'idle' | 'loading' | 'ready' | 'error'>('idle')
const previewUrl = ref<string | null>(null)

let loadedBlob: Blob | null = null
let loadedPath: string | null = null
let request = 0

function release(): void {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = null
  loadedBlob = null
  loadedPath = null
}

async function load(path: string): Promise<void> {
  const ticket = ++request
  release()
  status.value = 'loading'

  try {
    const response = await props.client.get<Blob>(path, { responseType: 'blob' })
    if (ticket !== request) return

    const blob = response.data
    if (blob.type.startsWith('text/html') || blob.type.startsWith('application/json')) {
      throw new Error('Server returned a message instead of a PDF')
    }

    loadedBlob = blob
    loadedPath = path
    previewUrl.value = URL.createObjectURL(blob)
    status.value = 'ready'
  } catch {
    if (ticket === request) status.value = 'error'
  }
}

function retry(): void {
  if (props.path) void load(props.path)
}

async function view(path?: string | null): Promise<void> {
  const target = path ?? props.path
  if (!target) return

  if (loadedBlob && loadedPath === target) {
    const url = URL.createObjectURL(loadedBlob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 30_000)
    return
  }

  try {
    const response = await props.client.get<Blob>(target, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 30_000)
  } catch {
    status.value = 'error'
  }
}

watch(
  () => props.path,
  (path) => {
    if (path) { void load(path); return }
    request++
    release()
    status.value = 'idle'
  },
  { immediate: true },
)

onBeforeUnmount(release)
defineExpose({ view })
</script>

<template>
  <div>
    <div
      v-if="status === 'loading' || status === 'idle'"
      class="flex items-center justify-center w-full h-screen border border-line-default border-solid rounded bg-surface"
    >
      <BaseSpinner class="w-8 h-8 text-primary-400" />
    </div>

    <div
      v-else-if="status === 'error'"
      class="flex flex-col items-center justify-center gap-4 w-full h-screen border border-line-default border-solid rounded bg-surface"
    >
      <BaseIcon name="ExclamationCircleIcon" class="w-12 h-12 text-muted" />
      <p class="text-sm text-muted">Unable to load the report.</p>
      <BaseButton variant="primary-outline" size="sm" @click="retry">Retry</BaseButton>
    </div>

    <iframe
      v-else
      :src="previewUrl ?? undefined"
      title="Trip Report PDF"
      class="w-full h-screen border border-line-default border-solid rounded bg-surface"
    />
  </div>
</template>