<!-- HisabKitab feature -->
<script setup lang="ts">
import { computed } from 'vue'
import { extensionRegistry, extensionItems } from './runtime'
import type { RichEditorContext } from './types'

const props = defineProps<{
  name: 'header-actions' | 'company-layout-overlays' | 'rich-editor-toolbar-actions' | 'invoice-form-sections' | 'estimate-form-sections' | 'invoice-permission-missing'
  context?: RichEditorContext
  // HisabKitab feature
  templateName?: string
  store?: Record<string, unknown>
  viewModeLabel?: string
}>()

const contributions = computed(() => {
  const items = {
    'header-actions': extensionRegistry.headerActions.value,
    'company-layout-overlays': extensionRegistry.companyLayoutOverlays.value,
    'rich-editor-toolbar-actions': extensionRegistry.richEditorToolbarActions.value,
    'invoice-form-sections': extensionRegistry.invoiceFormSections.value,
    'estimate-form-sections': extensionRegistry.estimateFormSections.value,
    'invoice-permission-missing': extensionRegistry.invoicePermissionMissing.value,
  }[props.name]

  return extensionItems(items)
})

function componentProps(props_: Record<string, unknown> | undefined): Record<string, unknown> {
  const extra: Record<string, unknown> = {}
  if (props.context !== undefined) extra.context = props.context
  if (props.templateName !== undefined) extra.templateName = props.templateName
  if (props.store !== undefined) extra.store = props.store
  if (props.viewModeLabel !== undefined) extra.viewModeLabel = props.viewModeLabel
  return { ...props_, ...extra }
}
</script>

<template>
  <component
    :is="contribution.component"
    v-for="contribution in contributions"
    :key="contribution.id"
    v-bind="componentProps(contribution.props)"
  />
</template>
