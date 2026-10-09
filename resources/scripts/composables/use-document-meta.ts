// HisabKitab feature
import { computed, type ComputedRef } from 'vue'
import { extensionRegistry, extensionItems } from '@/scripts/extensions/runtime'
import type { DocumentMetaContribution } from '../../../vendor/invoiceshelf/modules/frontend/index'

interface DocumentMeta {
  label: string
  labelPlural: string
  listLink: string
  createLink: string
  templateName: string
  hasItems: boolean
  // HisabKitab feature — per-template field labels
  dateLabel?: string
  numberLabel?: string
}

/**
 * Resolves the registered document metadata for a given template_name.
 * Used by invoice/estimate views to drive labels, breadcrumbs, and links
 * without each view repeating the same extensionRegistry lookup.
 */
export function useDocumentMeta(
  templateName: ComputedRef<string | undefined> | (() => string | undefined),
) {
  const resolved = computed<DocumentMeta | null>(() => {
    const tpl = typeof templateName === 'function' ? templateName() : templateName.value
    if (!tpl) return null
    return (extensionItems(extensionRegistry.invoiceDocumentMeta.value) as DocumentMetaContribution[])
      .find((m) => m.templateName === tpl) ?? null
  })

  return { currentDocMeta: resolved }
}

/**
 * Same as useDocumentMeta but reads from the estimate document meta registry.
 */
export function useEstimateDocumentMeta(
  templateName: ComputedRef<string | undefined> | (() => string | undefined),
) {
  const resolved = computed<DocumentMeta | null>(() => {
    const tpl = typeof templateName === 'function' ? templateName() : templateName.value
    if (!tpl) return null
    return (extensionItems(extensionRegistry.estimateDocumentMeta.value) as DocumentMetaContribution[])
      .find((m) => m.templateName === tpl) ?? null
  })

  return { currentDocMeta: resolved }
}
