<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { archiveProject, unarchiveProject } from '@/api'
import { fetchProject } from '@/api/board'
import InvoiceRetryBanner from '@/components/InvoiceRetryBanner.vue'
import ProjectFormModal from '@/components/ProjectFormModal.vue'
import { customerName, ensureLoaded } from '@/stores/customers'
import { invoicingStore } from '@/stores/invoicing'
import { errorMessage } from '@/support/errors'
import { formatDate } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { invoiceTasks } from '@/support/invoicing'
import { PATHS, ROUTES } from '@/support/page'
import type { Notify } from '@/support/page'
import type { Project, ProjectStatus } from '@/types/project'

interface Tab {
  id: string
  label: string
  name: string
}

const props = defineProps<{
  /** The route param, which arrives as a string. */
  id: string
  client: AxiosInstance
  notify: Notify
  router: Router
}>()

const ROUTE = ROUTES.project

const t = useTranslate()

/**
 * The host router, held as a local.
 *
 * `push` on a prop reads to the linter as mutating an array, and the router is
 * a stable singleton the host hands every module page, so naming it once is
 * both clearer and quieter.
 */
const hostRouter = props.router

const project = ref<Project | null>(null)
const loading = ref(true)
const busy = ref(false)
const modalOpen = ref(false)

const projectId = computed(() => Number(props.id))

const tabs = computed<Tab[]>(() => [
  { id: 'overview', label: t('tasks_projects.project.tabs.overview'), name: `${ROUTE}.overview` },
  { id: 'tasks', label: t('tasks_projects.project.tabs.tasks'), name: `${ROUTE}.tasks` },
  { id: 'time', label: t('tasks_projects.project.tabs.time'), name: `${ROUTE}.time` },
  { id: 'members', label: t('tasks_projects.project.tabs.members'), name: `${ROUTE}.members` },
])

/**
 * The active tab, read off the host router rather than `useRoute()`, which a
 * module bundle cannot call. The router runs on the host's Vue, which is the
 * same instance the module renders on, so the ref stays reactive here.
 */
const currentRouteName = computed(() => String(props.router.currentRoute.value.name ?? ''))

const title = computed(() => project.value?.name ?? t('tasks_projects.projects.title'))

/**
 * The board, opened on this project.
 *
 * The board lives under Tasks now and reads its project from the query string,
 * which the Tasks screen shares with every view, so the link lands on the same
 * screen a person would reach by picking the project there themselves.
 */
const boardLink = computed(() => ({ path: PATHS.board, query: { project: String(projectId.value) } }))

/**
 * The contact name for the header, from the company-wide map rather than a
 * lookup of its own: every other screen already needs the same map, and a
 * contact deleted since keeps its id as its label.
 */
const customerLabel = computed(() => customerName(project.value?.customer_id ?? null))

const invoicing = computed<boolean>(() => invoicingStore.busy)

/**
 * Whether invoicing this project would mean anything.
 *
 * Only a project that belongs to a customer and still has money waiting: an
 * internal project and a settled one keep the header they have rather than
 * offering an action the server would only refuse.
 */
const canInvoice = computed<boolean>(
  () =>
    invoicingStore.allowed &&
    project.value !== null &&
    project.value.customer_id !== null &&
    (project.value.totals?.unbilled_amount ?? 0) > 0,
)

watch(projectId, () => {
  void load()
})

/**
 * Land on the Overview tab when the page itself is the target.
 *
 * Resolving the parent route by name matches the parent alone, so nothing
 * would render below the tabs. Going to the index child by name is the same
 * URL, and it is what a link straight to the detail page ends up on.
 */
watch(currentRouteName, (name) => ensureTab(name))

onMounted(() => {
  ensureTab(currentRouteName.value)
  void load()
})

function ensureTab(name: string): void {
  if (name !== ROUTE) {
    return
  }

  void props.router.replace({ name: `${ROUTE}.overview`, params: { id: props.id } })
}

async function load(): Promise<void> {
  loading.value = true

  try {
    project.value = await fetchProject(props.client, projectId.value)

    if (typeof project.value?.customer_id === 'number') {
      await ensureLoaded(props.client)
    }
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.project.load_failed')))
  } finally {
    loading.value = false
  }
}

function tabTo(tab: Tab): { name: string; params: { id: string } } {
  return { name: tab.name, params: { id: props.id } }
}

function isActive(tab: Tab): boolean {
  return currentRouteName.value === tab.name
}

/**
 * Re-read the project rather than keep what the write returned.
 *
 * Only the detail endpoint carries the totals the Overview tab renders, so a
 * saved or archived project has to come back through `load()`.
 */
function onSaved(saved: Project): void {
  modalOpen.value = false
  props.notify('success', t('tasks_projects.projects.updated', { name: saved.name }))
  void load()
}

/**
 * Invoice everything unbilled on this project.
 *
 * The same sequence the Overview tab runs, offered from the header so it is
 * reachable from the Tasks, Time and Members tabs as well. Failing leaves the
 * user here, with the totals read again.
 */
async function onInvoice(): Promise<void> {
  const record = project.value

  if (record === null || !canInvoice.value || invoicing.value) {
    return
  }

  if (!(await invoiceTasks(
    { client: props.client, router: hostRouter, notify: props.notify, t },
    { projectId: record.id },
  ))) {
    await load()
  }
}

async function onArchive(): Promise<void> {
  const record = project.value

  if (record === null || busy.value) {
    return
  }

  busy.value = true

  try {
    if (record.status === 'ARCHIVED') {
      await unarchiveProject(props.client, record.id)
      props.notify('success', t('tasks_projects.projects.unarchived', { name: record.name }))
    } else {
      await archiveProject(props.client, record.id)
      props.notify('success', t('tasks_projects.projects.archived', { name: record.name }))
    }

    await load()
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.projects.save_failed')))
  } finally {
    busy.value = false
  }
}

/**
 * The host badge carries its own colour classes and its stylesheet is loaded
 * after the module's, so the override has to be important to hold.
 */
function statusClass(status: ProjectStatus): string {
  return status === 'ACTIVE' ? 'bg-primary-50! text-primary-500!' : 'bg-surface-tertiary! text-muted!'
}

function statusLabel(status: ProjectStatus): string {
  return status === 'ACTIVE'
    ? t('tasks_projects.projects.status.active')
    : t('tasks_projects.projects.status.archived')
}
</script>

<template>
  <BasePage>
    <BasePageHeader :title="title">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('tasks_projects.general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="t('tasks_projects.projects.title')" :to="PATHS.projects" />
        <BaseBreadcrumbItem :title="title" to="#" active />
      </BaseBreadcrumb>

      <div v-if="project" class="mt-2 flex flex-wrap items-center gap-3 text-sm text-muted">
        <BaseBadge class="rounded-full" :class="statusClass(project.status)">
          {{ statusLabel(project.status) }}
        </BaseBadge>

        <span v-if="project.identifier" class="rounded-sm bg-surface-tertiary px-2 py-0.5 text-body">
          {{ project.identifier }}
        </span>

        <span v-if="project.customer_id">
          {{ t('tasks_projects.project.customer') }}:
          <span class="text-body">{{ customerLabel }}</span>
        </span>
        <span v-else class="text-subtle">{{ t('tasks_projects.projects.internal') }}</span>

        <span v-if="project.due_date">
          {{ t('tasks_projects.project.due_date') }}:
          <span class="text-body">{{ formatDate(project.due_date) }}</span>
        </span>
      </div>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <router-link :to="boardLink">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="ViewColumnsIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.project.board') }}
            </BaseButton>
          </router-link>

          <BaseButton
            v-if="canInvoice"
            variant="primary-outline"
            :loading="invoicing"
            :disabled="invoicing"
            @click="onInvoice"
          >
            <template #left="slotProps">
              <BaseIcon v-if="!invoicing" name="BanknotesIcon" :class="slotProps.class" />
            </template>
            {{ t('tasks_projects.project.invoice_project') }}
          </BaseButton>

          <BaseButton
            v-if="project"
            variant="primary-outline"
            :loading="busy"
            :disabled="busy"
            @click="onArchive"
          >
            {{
              project.status === 'ARCHIVED'
                ? t('tasks_projects.projects.unarchive')
                : t('tasks_projects.projects.archive')
            }}
          </BaseButton>

          <BaseButton v-if="project" variant="primary" @click="modalOpen = true">
            <template #left="slotProps">
              <BaseIcon name="PencilIcon" :class="slotProps.class" />
            </template>
            {{ t('tasks_projects.general.edit') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <InvoiceRetryBanner :client="client" :notify="notify" />

    <nav class="mt-6 flex overflow-x-auto border-b border-line-default">
      <router-link
        v-for="tab in tabs"
        :key="tab.id"
        v-slot="{ href, navigate }"
        :to="tabTo(tab)"
        custom
      >
        <a
          :href="href"
          :aria-current="isActive(tab) ? 'page' : undefined"
          class="relative -mb-px flex items-center border-b-2 px-5 py-2.5 text-sm leading-5 font-medium whitespace-nowrap transition-colors focus:outline-hidden"
          :class="
            isActive(tab)
              ? 'border-primary-400 text-heading'
              : 'border-transparent text-muted hover:border-line-strong hover:text-body'
          "
          @click="navigate"
        >
          {{ tab.label }}
        </a>
      </router-link>
    </nav>

    <div v-if="loading && project === null" class="flex justify-center py-16">
      <BaseSpinner class="h-8 w-8 text-primary-500" />
    </div>

    <router-view v-else :project="project" @refresh="load" />

    <ProjectFormModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :project="project"
      @close="modalOpen = false"
      @saved="onSaved"
    />
  </BasePage>
</template>
