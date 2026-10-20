<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCustomerStore } from '../../features/company/customers/store'
import { useModalStore } from '../../stores/modal.store'
import { useUserStore } from '../../stores/user.store'
import { ABILITIES } from '../../config/abilities'
import CustomerModal from '../../features/company/customers/components/CustomerModal.vue'

interface Props {
  modelValue?: string | number | Record<string, unknown> | null
  fetchAll?: boolean
  showAction?: boolean
  /** For an optional customer, such as on an expense */
  canDeselect?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  fetchAll: false,
  showAction: false,
  canDeselect: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | Record<string, unknown> | null): void
}>()

const { t } = useI18n()
const modalStore = useModalStore()
const customerStore = useCustomerStore()
const userStore = useUserStore()

const selectedCustomer = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value as string | number | Record<string, unknown> | null)
  },
})

const cachedCustomer = ref<Record<string, unknown> | null>(null)

function onCustomerSelect(_val: unknown, option: unknown): void {
  if (option && typeof option === 'object') {
    cachedCustomer.value = option as Record<string, unknown>
  }
}

function onCustomerClear(): void {
  cachedCustomer.value = null
}

function onCustomerDeselect(): void {
  cachedCustomer.value = null
}

watch(
  () => props.modelValue,
  async (newVal) => {
    if (!newVal) {
      cachedCustomer.value = null
      return
    }

    if (typeof newVal === 'object' && newVal !== null && 'id' in newVal) {
      cachedCustomer.value = newVal as Record<string, unknown>
      return
    }

    const id = Number(newVal)
    if (!id || isNaN(id)) return

    if (cachedCustomer.value && cachedCustomer.value.id === id) return

    const existing = customerStore.customers.find((c) => c.id === id)
    if (existing) {
      cachedCustomer.value = existing as unknown as Record<string, unknown>
      return
    }

    try {
      const { customerService } = await import('../../api/services/customer.service')
      const res = await customerService.get(id)
      if (res?.data) {
        cachedCustomer.value = res.data as unknown as Record<string, unknown>
        reloadKey.value++
      }
    } catch {
      // ignore
    }
  },
  { immediate: true },
)

async function searchCustomers(search: string) {
  const data: Record<string, unknown> = { search }

  if (props.fetchAll) {
    data.limit = 'all'
  }

  const response = await customerStore.fetchCustomers(data)
  const results = response.data ?? []

  // Ensure selected/cached customer is always in the options list so it never reverts to integer ID
  if (cachedCustomer.value && !results.some((c: Record<string, unknown>) => c.id === cachedCustomer.value?.id)) {
    results.unshift(cachedCustomer.value as typeof results[0])
  }

  // A customer just added here may sort past the first page of results
  if (created.value && !results.some((c: Record<string, unknown>) => c.id === created.value?.id)) {
    results.unshift(created.value as typeof results[0])
  }

  if (results.length > 0 && customerStore.editCustomer) {
    const customerFound = results.find(
      (c: Record<string, unknown>) => c.id === customerStore.editCustomer?.id
    )
    if (!customerFound) {
      const editCopy = { ...customerStore.editCustomer }
      results.unshift(editCopy as typeof results[0])
    }
  }

  return results
}

const created = ref<Record<string, unknown> | null>(null)

// Remounting the select reloads its options, so the new customer shows by name
const reloadKey = ref<number>(0)

function addCustomer(): void {
  customerStore.resetCurrentCustomer()

  modalStore.openModal({
    title: t('customers.add_new_customer'),
    componentName: 'CustomerModal',
    refreshData: (customer: unknown) => {
      const saved = customer as Record<string, unknown> | undefined

      if (saved?.id) {
        created.value = saved
        cachedCustomer.value = saved
        reloadKey.value++
        selectedCustomer.value = saved.id as number
      }
    },
  })
}
</script>

<template>
  <BaseMultiselect
    :key="reloadKey"
    v-model="selectedCustomer"
    v-bind="$attrs"
    track-by="name"
    value-prop="id"
    label="name"
    :filter-results="false"
    resolve-on-load
    :delay="500"
    :searchable="true"
    :options="searchCustomers"
    label-value="name"
    :placeholder="$t('customers.type_or_click')"
    :can-deselect="canDeselect"
    class="w-full"
    @select="onCustomerSelect"
    @clear="onCustomerClear"
    @deselect="onCustomerDeselect"
  >
    <template v-if="showAction" #action>
      <BaseSelectAction
        v-if="userStore.hasAbilities(ABILITIES.CREATE_CUSTOMER)"
        @click="addCustomer"
      >
        <BaseIcon
          name="UserPlusIcon"
          class="h-4 me-2 -ms-2 text-center text-primary-400"
        />

        {{ $t('customers.add_new_customer') }}
      </BaseSelectAction>
    </template>
  </BaseMultiselect>

  <CustomerModal />
</template>
