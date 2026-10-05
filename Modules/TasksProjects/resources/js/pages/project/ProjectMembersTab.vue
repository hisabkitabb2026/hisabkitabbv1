<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { listMembers } from '@/api'
import { attachProjectMember, detachProjectMember, listProjectMembers } from '@/api/board'
import { errorMessage, fieldErrors } from '@/support/errors'
import { majorToMinor } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import type { SelectOption } from '@/types/board'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { ProjectMember } from '@/types/project-member'

const props = defineProps<{
  /** The route param, which arrives as a string. */
  id: string
  client: AxiosInstance
  notify: Notify
  project: Project | null
}>()

const t = useTranslate()

const attached = ref<ProjectMember[]>([])
const members = ref<CompanyMember[]>([])
const loading = ref(true)
const saving = ref(false)
const busyUserId = ref<number | null>(null)
const chosen = ref<SelectOption | null>(null)
const rate = ref('')
const errors = ref<Record<string, string>>({})

const projectId = computed(() => props.project?.id ?? Number(props.id))

/** Only members who are not on the project yet can be added to it. */
const available = computed<SelectOption[]>(() =>
  members.value
    .filter((member) => !attached.value.some((record) => record.user_id === member.id))
    .map((member) => ({ id: member.id, label: member.name })),
)

onMounted(() => {
  void load()
})

async function load(): Promise<void> {
  loading.value = true

  try {
    members.value = await listMembers(props.client)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.members_failed')))
  }

  try {
    attached.value = await listProjectMembers(props.client, projectId.value)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.project.members.load_failed')))
  } finally {
    loading.value = false
  }
}

function memberName(userId: number): string {
  return (
    members.value.find((member) => member.id === userId)?.name ??
    t('tasks_projects.project.time.removed_member')
  )
}

async function attach(): Promise<void> {
  const option = chosen.value

  if (option === null || saving.value) {
    return
  }

  saving.value = true
  errors.value = {}

  try {
    await attachProjectMember(props.client, projectId.value, {
      user_id: option.id,
      rate: majorToMinor(rate.value),
    })

    props.notify('success', t('tasks_projects.project.members.attached', { name: option.label }))
    chosen.value = null
    rate.value = ''
    await load()
  } catch (error: unknown) {
    errors.value = fieldErrors(error)
    props.notify('error', errorMessage(error, t('tasks_projects.project.members.attach_failed')))
  } finally {
    saving.value = false
  }
}

async function detach(member: ProjectMember): Promise<void> {
  const name = memberName(member.user_id)

  if (!window.confirm(t('tasks_projects.project.members.detach_confirm', { name }))) {
    return
  }

  busyUserId.value = member.user_id

  try {
    await detachProjectMember(props.client, projectId.value, member.user_id)
    props.notify('success', t('tasks_projects.project.members.detached', { name }))
    await load()
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.project.members.detach_failed')))
  } finally {
    busyUserId.value = null
  }
}
</script>

<template>
  <div class="py-4">
    <div class="rounded-xl border border-line-default bg-surface p-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
        <BaseInputGroup
          :label="t('tasks_projects.project.members.member')"
          :error="errors.user_id"
          class="flex-1"
        >
          <BaseSelectInput
            v-model="chosen"
            :options="available"
            :placeholder="t('tasks_projects.project.members.attach_placeholder')"
            label-key="label"
          />
        </BaseInputGroup>

        <BaseInputGroup
          :label="t('tasks_projects.project.members.rate')"
          :error="errors.rate"
          :help-text="t('tasks_projects.project.members.rate_help')"
          class="flex-1"
        >
          <BaseInput v-model="rate" type="number" step="0.01" min="0" />
        </BaseInputGroup>

        <BaseButton
          variant="primary"
          class="lg:mb-1"
          :loading="saving"
          :disabled="saving || chosen === null"
          @click="attach"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ t('tasks_projects.project.members.attach') }}
        </BaseButton>
      </div>

      <p v-if="available.length === 0 && !loading" class="mt-3 text-xs text-subtle">
        {{ t('tasks_projects.project.members.all_attached') }}
      </p>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-line-default bg-surface">
      <div v-if="loading" class="flex justify-center py-10">
        <BaseSpinner class="h-6 w-6 text-primary-500" />
      </div>

      <p v-else-if="attached.length === 0" class="px-5 py-8 text-center text-sm text-muted">
        {{ t('tasks_projects.project.members.empty') }}
      </p>

      <ul v-else class="divide-y divide-line-light">
        <li
          v-for="member in attached"
          :key="member.id"
          class="flex items-center justify-between px-5 py-4"
        >
          <div>
            <p class="text-sm font-medium text-heading">{{ memberName(member.user_id) }}</p>
            <p class="text-xs text-muted">
              {{ t('tasks_projects.project.members.rate') }}:
              <BaseFormatMoney v-if="member.rate !== null" :amount="member.rate" />
              <span v-else class="text-subtle">{{ t('tasks_projects.tasks.none') }}</span>
            </p>
          </div>

          <BaseButton
            variant="danger"
            size="sm"
            :loading="busyUserId === member.user_id"
            :disabled="busyUserId === member.user_id"
            @click="detach(member)"
          >
            {{ t('tasks_projects.general.delete') }}
          </BaseButton>
        </li>
      </ul>
    </div>
  </div>
</template>
