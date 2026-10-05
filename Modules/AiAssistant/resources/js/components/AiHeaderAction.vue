<script setup lang="ts">
defineEmits<{ open: [] }>()
</script>

<template>
  <li>
    <button type="button" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-body hover:bg-hover" title="AI Assistant" aria-label="Open AI Assistant" @click="$emit('open')">
      <BaseIcon name="SparklesIcon" class="h-5 w-5 text-primary-500" />
      <span class="hidden lg:inline">AI</span>
    </button>
  </li>
</template>
