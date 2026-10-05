<script setup lang="ts">
/**
 * LorryPartySelectPopup — Party selector for Lorry Receipts.
 *
 * Mirrors the visual design of BaseCustomerSelectPopup: border-dashed trigger
 * button, glass card with avatar when selected, dropdown panel with search +
 * list + "Add New" button. Closes on click-outside and Escape.
 *
 * The dropdown panel is teleported to <body> so it is never clipped by parent
 * containers (BaseCard has overflow:hidden).
 */
import { ref, watch, computed, onMounted, onBeforeUnmount, nextTick, reactive } from 'vue'
import type { AxiosInstance } from 'axios'

export interface LorryPartyProfile {
  id: number
  type: string
  name: string
  address?: string
  phone?: string
  alternate_phone?: string
  pan_number?: string
  bank_account_no?: string
  licence_no?: string
  licence_date?: string
  rto_address?: string
  valid_up_to?: string
  advice_no?: string
  advice_date?: string
  [key: string]: unknown
}

const props = defineProps<{
  client: AxiosInstance
  type: 'OWNER' | 'DRIVER' | 'BROKER'
  label: string
  modelValue?: LorryPartyProfile | null
}>()

const emit = defineEmits<{
  (e: 'select', profile: LorryPartyProfile): void
  (e: 'clear'): void
}>()

const isOpen = ref(false)
const search = ref<string | null>(null)
const profiles = ref<LorryPartyProfile[]>([])
const suppliers = ref<Array<{ id: number; name: string; phone?: string | null; email?: string | null }>>([])
const loading = ref(false)
const selectedProfile = ref<LorryPartyProfile | null>(props.modelValue ?? null)

const showCreateModal = ref(false)
const newProfile = ref({ name: '', phone: '', email: '', address: '', bank_account_no: '', licence_no: '', pan_number: '' })

const trigger = ref<HTMLElement | null>(null)
const panel = ref<HTMLElement | null>(null)
const card = ref<HTMLElement | null>(null)
const searchField = ref<HTMLElement | null>(null)

// Panel position (computed from trigger's bounding rect on open)
const panelStyle = reactive<{ top: string; left: string; width: string }>({
  top: '0px',
  left: '0px',
  width: 'auto',
})

function initials(name: string): string {
  return name
    .split(' ')
    .map((n) => n.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

async function fetchProfiles(): Promise<void> {
  loading.value = true
  try {
    const [profilesRes, suppliersRes] = await Promise.all([
      props.client.get('/api/v1/lorry-receipts/lorry-party-profiles', {
        params: { type: props.type, search: search.value, limit: 'all' },
      }),
      // The host's suppliers, so one entered at /admin/suppliers is pickable
      // here too. A 403 (no view-supplier ability) just hides the section.
      props.client.get('/api/v1/suppliers', { params: { limit: 100 } }).catch(() => null),
    ])
    profiles.value = profilesRes.data.data || []
    suppliers.value = suppliersRes?.data?.data || []
  } catch {
    profiles.value = []
    suppliers.value = []
  } finally {
    loading.value = false
  }
}

/**
 * Suppliers without a profile of this type, shown only while searching: type
 * a name and, if it exists among the company's suppliers, it shows up here —
 * picking it creates the profile on the fly, so nobody is entered twice.
 */
const unprofiledSuppliers = computed(() => {
  if (!search.value) {
    return []
  }

  const profiled = new Set(
    profiles.value.map((p) => (p as Record<string, unknown>).supplier_id).filter(Boolean),
  )
  const term = search.value.toLowerCase()
  return suppliers.value.filter(
    (s) =>
      !profiled.has(s.id)
      && (s.name.toLowerCase().includes(term) || (s.phone || '').includes(term)),
  )
})

/** Pick a bare Supplier: create the party profile behind it, then select it. */
async function selectSupplier(supplier: { id: number; name: string; phone?: string | null; email?: string | null }): Promise<void> {
  try {
    const { data } = await props.client.post('/api/v1/lorry-receipts/lorry-party-profiles', {
      type: props.type,
      supplier_id: supplier.id,
      name: supplier.name,
      phone: supplier.phone ?? '',
    })
    if (data?.data) {
      selectProfile(data.data)
    }
  } catch (e) {
    console.error('Failed to create profile from supplier', e)
  }
}

let debounceTimer: ReturnType<typeof setTimeout>
function debounceSearch(): void {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchProfiles(), 400)
}

function updatePanelPosition(): void {
  if (!trigger.value) return
  const rect = trigger.value.getBoundingClientRect()
  panelStyle.top = (rect.bottom + window.scrollY + 8) + 'px'
  panelStyle.left = (rect.left + window.scrollX) + 'px'
  panelStyle.width = rect.width + 'px'
}

function openPicker(): void {
  isOpen.value = true
  // Always refresh: suppliers and profiles may have been added since the
  // last open.
  fetchProfiles()
  nextTick(() => {
    updatePanelPosition()
    searchField.value?.querySelector('input')?.focus()
  })
}

async function closePicker(): Promise<void> {
  const wasOpen = isOpen.value
  isOpen.value = false
  if (wasOpen) {
    await nextTick()
    ;(card.value ?? trigger.value)?.focus()
  }
}

function selectProfile(profile: LorryPartyProfile): void {
  selectedProfile.value = profile
  isOpen.value = false
  search.value = null
  emit('select', profile)
}

function clearSelection(): void {
  selectedProfile.value = null
  emit('clear')
}

function openCreateModal(): void {
  isOpen.value = false
  newProfile.value = { name: '', phone: '', email: '', address: '', bank_account_no: '', licence_no: '', pan_number: '' }
  showCreateModal.value = true
}

async function saveNewProfile(): Promise<void> {
  try {
    const payload: Record<string, unknown> = {
      type: props.type,
      name: newProfile.value.name,
      phone: newProfile.value.phone,
      email: newProfile.value.email || null,
      address: newProfile.value.address,
      bank_account_no: newProfile.value.bank_account_no,
    }
    if (props.type === 'DRIVER') {
      payload.licence_no = newProfile.value.licence_no
    }
    if (props.type === 'OWNER' || props.type === 'BROKER') {
      payload.pan_number = newProfile.value.pan_number
    }

    const { data } = await props.client.post('/api/v1/lorry-receipts/lorry-party-profiles', payload)
    showCreateModal.value = false
    if (data?.data) {
      selectProfile(data.data)
    }
  } catch (e) {
    console.error('Failed to create profile', e)
  }
}

// --- Ability gating ---
// The host exposes extensions.hasAbilities on window during module boot.
// Without the module's manage-party-profiles ability the write controls
// (Add New / Edit) stay hidden; the backend refuses the writes anyway.
const canManageParties = computed<boolean>(() => {
  const w = window as any
  return typeof w.__lorryReceiptCan === 'function'
    ? !!w.__lorryReceiptCan('lorry-receipt:manage-party-profiles')
    : true
})

// --- Edit the selected profile ---
const showEditModal = ref(false)
const editForm = ref({
  name: '',
  phone: '',
  address: '',
  bank_account_no: '',
  pan_number: '',
  licence_no: '',
  licence_date: '',
  rto_address: '',
  valid_up_to: '',
})

function openEditModal(): void {
  const p = selectedProfile.value
  if (!p) return
  editForm.value = {
    name: p.name ?? '',
    phone: (p.phone as string) ?? '',
    address: (p.address as string) ?? '',
    bank_account_no: (p.bank_account_no as string) ?? '',
    pan_number: (p.pan_number as string) ?? '',
    licence_no: (p.licence_no as string) ?? '',
    licence_date: (p.licence_date as string) ?? '',
    rto_address: (p.rto_address as string) ?? '',
    valid_up_to: (p.valid_up_to as string) ?? '',
  }
  showEditModal.value = true
}

async function saveEdit(): Promise<void> {
  const p = selectedProfile.value
  if (!p || !p.id) return
  try {
    const { data } = await props.client.put(
      `/api/v1/lorry-receipts/lorry-party-profiles/${p.id}`,
      { type: props.type, ...editForm.value },
    )
    showEditModal.value = false
    if (data?.data) {
      // Refresh the list and re-select so the parent re-fills the form fields
      fetchProfiles()
      selectProfile(data.data)
    }
  } catch (e) {
    console.error('Failed to update profile', e)
  }
}

// Click-outside + Escape handling
function handleClickOutside(e: MouseEvent): void {
  if (!isOpen.value) return
  const target = e.target as Node
  if (panel.value?.contains(target)) return
  if (trigger.value?.contains(target)) return
  closePicker()
}

function handleEscape(e: KeyboardEvent): void {
  if (e.key === 'Escape' && isOpen.value) {
    closePicker()
  }
}

// Reposition panel on scroll/resize while open
function handleScrollResize(): void {
  if (isOpen.value) {
    updatePanelPosition()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleScrollResize, true)
  window.addEventListener('resize', handleScrollResize)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleScrollResize, true)
  window.removeEventListener('resize', handleScrollResize)
})

watch(() => props.modelValue, (val) => {
  selectedProfile.value = val ?? null
})
</script>

<template>
  <div>
    <!-- Selected profile card (mirrors BaseCustomerSelectPopup selected state) -->
    <div
      v-if="selectedProfile"
      ref="card"
      tabindex="-1"
      :aria-label="label + ': ' + selectedProfile.name"
      class="flex flex-col gap-4 p-4 border md:p-5 glass rounded-xl focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus"
    >
      <div class="flex items-start gap-3">
        <span
          class="flex items-center justify-center w-11 h-11 text-sm font-semibold rounded-xl shrink-0 bg-btn-primary text-on-primary"
          aria-hidden="true"
        >
          {{ initials(selectedProfile.name) }}
        </span>

        <div class="flex-1 min-w-0">
          <p class="text-xs font-medium text-muted">{{ label }}</p>
          <p class="text-base font-semibold truncate text-heading">{{ selectedProfile.name }}</p>
          <p v-if="selectedProfile.phone || selectedProfile.address" class="text-sm truncate text-muted">
            {{ selectedProfile.phone || selectedProfile.address }}
          </p>
        </div>

        <div class="flex items-center gap-1 -me-1 shrink-0">
          <button
            v-if="selectedProfile.id && canManageParties"
            type="button"
            class="flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading"
            :aria-label="'Edit ' + label"
            @click="openEditModal"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            type="button"
            class="flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading"
            :aria-label="'Change ' + label"
            @click="clearSelection"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- No profile selected: border-dashed trigger button -->
    <div v-else>
      <button
        ref="trigger"
        type="button"
        :aria-expanded="isOpen"
        aria-haspopup="dialog"
        class="flex items-center w-full gap-4 p-4 text-start transition-colors border-2 border-dashed md:p-5 rounded-xl bg-surface/50 hover:border-primary-400 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus"
        @click="isOpen ? closePicker() : openPicker()"
      >
        <span
          class="flex items-center justify-center w-11 h-11 rounded-xl shrink-0 bg-primary-50 text-primary-600"
          aria-hidden="true"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </span>

        <span class="flex flex-col flex-1 min-w-0">
          <span class="text-base font-semibold text-heading">{{ label }}</span>
          <span class="text-sm text-muted">Click to select</span>
        </span>

        <svg class="w-5 h-5 text-subtle shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
    </div>

    <!-- Dropdown panel — teleported to body so it's never clipped by parent overflow -->
    <Teleport to="body">
      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        leave-active-class="transition duration-100 ease-in"
        leave-to-class="translate-y-1 opacity-0"
      >
        <div
          v-if="isOpen"
          ref="panel"
          role="dialog"
          :aria-label="label"
          :style="{ position: 'absolute', top: panelStyle.top, left: panelStyle.left, width: panelStyle.width, zIndex: 9999 }"
          class="overflow-hidden border glass-strong rounded-xl shadow-xl"
        >
          <div ref="searchField" class="p-3">
            <div class="relative">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="search"
                type="search"
                placeholder="Search..."
                class="w-full py-2 pl-9 pr-3 text-sm border border-line-default rounded-lg bg-surface text-heading placeholder:text-subtle focus:outline-none focus:ring-2 focus:ring-focus"
                @input="debounceSearch"
              />
            </div>
          </div>

          <ul class="flex flex-col overflow-y-auto border-t border-line-light max-h-80 overscroll-contain">
            <li v-for="profile in profiles" :key="'p' + profile.id">
              <button
                type="button"
                class="flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong"
                @click="selectProfile(profile)"
              >
                <span
                  class="flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-primary-50 text-primary-700"
                  aria-hidden="true"
                >
                  {{ initials(profile.name) }}
                </span>
                <span class="flex flex-col min-w-0">
                  <span class="flex items-center gap-2">
                    <span class="text-sm font-medium truncate text-heading">{{ profile.name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 font-medium">
                      {{ profile.type }}
                    </span>
                  </span>
                  <span v-if="profile.phone || profile.address" class="text-sm truncate text-muted">
                    {{ profile.phone || profile.address }}
                  </span>
                </span>
              </button>
            </li>

            <li v-for="supplier in unprofiledSuppliers" :key="'s' + supplier.id">
              <button
                type="button"
                class="flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong"
                :title="'Use this supplier as the ' + label"
                @click="selectSupplier(supplier)"
              >
                <span
                  class="flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-surface-secondary text-muted"
                  aria-hidden="true"
                >
                  {{ initials(supplier.name) }}
                </span>
                <span class="flex flex-col min-w-0">
                  <span class="flex items-center gap-2">
                    <span class="text-sm font-medium truncate text-heading">{{ supplier.name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-surface-secondary text-muted font-medium">
                      Supplier
                    </span>
                  </span>
                  <span v-if="supplier.phone || supplier.email" class="text-sm truncate text-muted">
                    {{ supplier.phone || supplier.email }}
                  </span>
                </span>
              </button>
            </li>

            <li v-if="loading" class="px-4 py-8 text-sm text-center text-muted" role="status">
              Loading...
            </li>

            <li
              v-else-if="profiles.length === 0 && unprofiledSuppliers.length === 0"
              class="flex flex-col gap-1 px-4 py-8 text-sm text-center"
              role="status"
            >
              <template v-if="search">
                <span class="text-muted">No matches found</span>
              </template>
              <template v-else>
                <span class="font-medium text-heading">No {{ label }} profiles yet</span>
                <span class="text-muted">Add one to get started</span>
              </template>
            </li>
          </ul>

          <button
            v-if="canManageParties"
            type="button"
            class="flex items-center justify-center w-full gap-2 text-sm font-medium transition-colors border-t h-12 border-line-light text-primary-600 hover:bg-primary-50/60"
            @click="openCreateModal"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Add New {{ label }}
          </button>
        </div>
      </transition>
    </Teleport>

    <!-- Create modal — teleported to body so it's never clipped -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50" @click.self="showCreateModal = false">
        <div class="w-full max-w-md p-6 bg-surface rounded-xl">
          <h3 class="mb-4 text-lg font-semibold text-heading">New {{ label }}</h3>
          <div class="space-y-3">
            <input v-model="newProfile.name" placeholder="Name *" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <input v-model="newProfile.phone" placeholder="Phone" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <input v-model="newProfile.email" type="email" placeholder="Email" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <textarea v-model="newProfile.address" placeholder="Address" rows="2" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"></textarea>
            <input v-model="newProfile.bank_account_no" placeholder="Bank Account No" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <input v-if="type === 'DRIVER'" v-model="newProfile.licence_no" placeholder="Licence No" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <input v-if="type === 'OWNER' || type === 'BROKER'" v-model="newProfile.pan_number" placeholder="PAN No" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
          </div>
          <p class="mt-3 text-xs text-muted">
            Also saved as a Supplier, so bills and payments can be tracked against this {{ label }}.
          </p>
          <div class="flex gap-2 mt-4">
            <button type="button" class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700" @click="saveNewProfile">Save</button>
            <button type="button" class="px-4 py-2 text-sm border border-line-default rounded-lg text-heading" @click="showCreateModal = false">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Edit modal — edit the selected profile's details -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50" @click.self="showEditModal = false">
        <div class="w-full max-w-md p-6 bg-surface rounded-xl max-h-[90vh] overflow-y-auto">
          <h3 class="mb-4 text-lg font-semibold text-heading">Edit {{ label }}</h3>
          <div class="space-y-3">
            <input v-model="editForm.name" placeholder="Name *" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <input v-model="editForm.phone" placeholder="Phone" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <textarea v-model="editForm.address" placeholder="Address" rows="2" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"></textarea>
            <input v-model="editForm.bank_account_no" placeholder="Bank Account No" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <input v-if="type === 'OWNER' || type === 'BROKER'" v-model="editForm.pan_number" placeholder="PAN No" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            <template v-if="type === 'DRIVER'">
              <input v-model="editForm.licence_no" placeholder="Licence No" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
              <input v-model="editForm.licence_date" type="date" placeholder="Licence Date" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
              <input v-model="editForm.rto_address" placeholder="RTO" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
              <input v-model="editForm.valid_up_to" type="date" placeholder="Valid Up To" class="w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading" />
            </template>
          </div>
          <div class="flex gap-2 mt-4">
            <button type="button" class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700" @click="saveEdit">Save</button>
            <button type="button" class="px-4 py-2 text-sm border border-line-default rounded-lg text-heading" @click="showEditModal = false">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
