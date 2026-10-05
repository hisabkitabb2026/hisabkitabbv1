import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'
import { projectMessages } from '@/messages/projects'
import ProjectDetailPage from '@/pages/ProjectDetailPage.vue'
import ProjectsIndexPage from '@/pages/ProjectsIndexPage.vue'
import ProjectMembersTab from '@/pages/project/ProjectMembersTab.vue'
import ProjectOverviewTab from '@/pages/project/ProjectOverviewTab.vue'
import ProjectTasksTab from '@/pages/project/ProjectTasksTab.vue'
import ProjectTimeTab from '@/pages/project/ProjectTimeTab.vue'
import { MODULE, injectedPage } from '@/support/page'

const ability = {
  viewProject: `${MODULE}:view-project`,
  editProject: `${MODULE}:edit-project`,
  viewTask: `${MODULE}:view-task`,
  viewOwnTime: `${MODULE}:view-own-time`,
} as const

/**
 * The project index and one project's detail tabs.
 *
 * Projects sits beside Tasks rather than above it: a project groups work,
 * bills it and holds a budget, but the day's question is "what am I doing",
 * which is a task. So the index moved off the module root and onto its own
 * path, and the sidebar carries both.
 *
 * Registered from here rather than from `init.ts` so that a slice of the
 * module owns one file: adding a screen never means editing the same lines
 * another slice is editing. The strings come along for the ride, because the
 * host merges message bundles recursively.
 */
export function registerProjectPages(extensions: InvoiceShelfExtensionApi): void {
  extensions.addMessages(projectMessages)

  extensions.registerPage({
    id: 'projects',
    module: MODULE,
    path: 'projects',
    component: injectedPage(extensions, ProjectsIndexPage),
    meta: {
      ability: ability.viewProject,
      title: 'tasks_projects.projects.title',
    },
  })

  extensions.registerPage({
    id: 'project',
    module: MODULE,
    path: 'projects/:id',
    component: injectedPage(extensions, ProjectDetailPage),
    meta: {
      ability: ability.viewProject,
      title: 'tasks_projects.projects.title',
    },
    children: [
      {
        id: 'overview',
        path: '',
        component: injectedPage(extensions, ProjectOverviewTab),
        meta: {
          ability: ability.viewProject,
          title: 'tasks_projects.project.tabs.overview',
        },
      },
      {
        id: 'tasks',
        path: 'tasks',
        component: injectedPage(extensions, ProjectTasksTab),
        meta: {
          ability: ability.viewTask,
          title: 'tasks_projects.project.tabs.tasks',
        },
      },
      {
        id: 'time',
        path: 'time',
        component: injectedPage(extensions, ProjectTimeTab),
        meta: {
          ability: ability.viewOwnTime,
          title: 'tasks_projects.project.tabs.time',
        },
      },
      {
        id: 'members',
        path: 'members',
        component: injectedPage(extensions, ProjectMembersTab),
        meta: {
          ability: ability.editProject,
          title: 'tasks_projects.project.tabs.members',
        },
      },
    ],
  })
}
