import type { AxiosInstance } from 'axios'
import type { Customer, Paginated, Wrapped } from '@/types/api'
import type { CompanyMember } from '@/types/member'
import type { ModuleSettings } from '@/types/settings'
import type { Project, ProjectInput, ProjectListParams } from '@/types/project'

/** Every module path hangs off the slug prefix, so a core route can never collide. */
export const BASE = '/api/v1/tasks-projects'

/** Every endpoint the module owns. */
export const TASKS_PROJECTS_API = {
  projects: `${BASE}/projects`,
  project: (id: number): string => `${BASE}/projects/${id}`,
  archiveProject: (id: number): string => `${BASE}/projects/${id}/archive`,
  unarchiveProject: (id: number): string => `${BASE}/projects/${id}/unarchive`,
  members: `${BASE}/members`,
  settings: `${BASE}/settings`,
} as const

/** Host endpoints the module reads through the same client. */
export const HOST_API = {
  customers: '/api/v1/customers',
} as const

export type SortOrder = 'asc' | 'desc'

/** The columns `GET projects` orders by. Mirrors `ProjectService::SORT_KEYS`. */
export const PROJECT_SORT_KEYS = [
  'name',
  'status',
  'due_date',
  'created_at',
  'default_rate',
] as const

export type ProjectSortKey = (typeof PROJECT_SORT_KEYS)[number]

/** The ordering half of a list request, which every list endpoint accepts. */
export interface SortParams<TKey extends string> {
  sort_by?: TKey
  sort_order?: SortOrder
}

/**
 * What `BaseTable` hands a server-side fetcher.
 *
 * `fieldName` is the key of the column whose header was clicked, and `order`
 * is empty until one has been, which is the unsorted state the table starts
 * in.
 */
export interface TableSort {
  fieldName: string
  order: SortOrder | ''
}

/**
 * The list parameters a table sort asks for, or none at all.
 *
 * The table reports its own column key, so the caller passes the map from
 * those to the keys the endpoint takes. A column the endpoint cannot order by,
 * and a table nobody has sorted yet, add nothing and leave the endpoint on its
 * own opening order.
 */
export function sortParams<TKey extends string>(
  sort: TableSort | undefined,
  keys: Record<string, TKey>,
): SortParams<TKey> {
  if (sort === undefined || sort.order === '') {
    return {}
  }

  const key = keys[sort.fieldName]

  return key === undefined ? {} : { sort_by: key, sort_order: sort.order }
}

export async function listProjects(
  client: AxiosInstance,
  params: ProjectListParams & SortParams<ProjectSortKey>,
): Promise<Paginated<Project>> {
  const { data } = await client.get<Paginated<Project>>(TASKS_PROJECTS_API.projects, { params })

  return data
}

export async function createProject(client: AxiosInstance, input: ProjectInput): Promise<Project> {
  const { data } = await client.post<Wrapped<Project>>(TASKS_PROJECTS_API.projects, input)

  return data.data
}

export async function updateProject(
  client: AxiosInstance,
  id: number,
  input: ProjectInput,
): Promise<Project> {
  const { data } = await client.put<Wrapped<Project>>(TASKS_PROJECTS_API.project(id), input)

  return data.data
}

export async function archiveProject(client: AxiosInstance, id: number): Promise<Project> {
  const { data } = await client.post<Wrapped<Project>>(TASKS_PROJECTS_API.archiveProject(id))

  return data.data
}

export async function unarchiveProject(client: AxiosInstance, id: number): Promise<Project> {
  const { data } = await client.post<Wrapped<Project>>(TASKS_PROJECTS_API.unarchiveProject(id))

  return data.data
}

export async function deleteProject(client: AxiosInstance, id: number): Promise<void> {
  await client.delete(TASKS_PROJECTS_API.project(id))
}

/** The company's members, for the assignee and project member pickers. */
export async function listMembers(client: AxiosInstance): Promise<CompanyMember[]> {
  const { data } = await client.get<Wrapped<CompanyMember[]>>(TASKS_PROJECTS_API.members)

  return data.data
}

export async function fetchSettings(client: AxiosInstance): Promise<ModuleSettings> {
  const { data } = await client.get<Wrapped<ModuleSettings>>(TASKS_PROJECTS_API.settings)

  return data.data
}

/**
 * The company's contacts, for the project form's customer picker. This is a
 * host endpoint, scoped by the same `company` header the client already sends.
 */
export async function listCustomers(client: AxiosInstance, limit = 100): Promise<Customer[]> {
  const { data } = await client.get<Wrapped<Customer[]>>(HOST_API.customers, { params: { limit } })

  return data.data
}
