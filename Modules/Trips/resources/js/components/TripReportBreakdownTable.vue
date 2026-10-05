<script setup lang="ts">
import { computed } from 'vue'
import { formatMoney } from '@/support/format'

interface BreakdownRow {
  id: string | number
  label: string
  trip_count: number
  revenue: number
  cost: number
  profit: number
}

const props = defineProps<{
  title: string
  labelHeading: string
  rows: BreakdownRow[]
}>()

const columns = computed(() => [
  { key: 'label', label: props.labelHeading, thClass: 'text-start', tdClass: 'font-medium text-heading' },
  { key: 'trip_count', label: 'Trips', dataType: 'numeric' },
  { key: 'revenue', label: 'Revenue', dataType: 'numeric' },
  { key: 'cost', label: 'Cost', dataType: 'numeric' },
  { key: 'profit', label: 'Profit', dataType: 'numeric' },
])
</script>

<template>
  <section class="mt-6">
    <h3 class="text-sm font-semibold tracking-wider text-muted uppercase">{{ title }}</h3>

    <div class="relative mt-2 overflow-x-auto rounded-xl border border-line-default bg-surface">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-line-default text-xs uppercase text-muted">
            <th class="p-3 text-start font-medium">{{ labelHeading }}</th>
            <th class="p-3 text-end font-medium">Trips</th>
            <th class="p-3 text-end font-medium">Revenue</th>
            <th class="p-3 text-end font-medium">Cost</th>
            <th class="p-3 text-end font-medium">Profit</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-line-light last:border-b-0">
            <td class="p-3 font-medium text-heading">{{ row.label }}</td>
            <td class="p-3 text-end text-body">{{ row.trip_count }}</td>
            <td class="p-3 text-end text-body">{{ formatMoney(row.revenue) }}</td>
            <td class="p-3 text-end text-body">{{ formatMoney(row.cost) }}</td>
            <td
              class="p-3 text-end font-semibold"
              :class="row.profit >= 0 ? 'text-status-green' : 'text-status-red'"
            >
              {{ formatMoney(row.profit) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
