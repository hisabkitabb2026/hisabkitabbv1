import { defineComponent, onMounted } from 'vue'
import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'
import { taskMessages } from '@/messages/tasks'
import TaskPage from '@/pages/TaskPage.vue'
import TasksPage from '@/pages/TasksPage.vue'
import TasksBoardView from '@/pages/tasks/TasksBoardView.vue'
import TasksListView from '@/pages/tasks/TasksListView.vue'
import TasksWeekView from '@/pages/tasks/TasksWeekView.vue'
import { MODULE, PATHS, injectedPage } from '@/support/page'

const ability = {
  viewTask: `${MODULE}:view-task`,
  viewOwnTime: `${MODULE}:view-own-time`,
} as const

/**
 * The Tasks screen, its three views and one task's own page.
 *
 * Tasks is the module root: it is the screen people open to see what there is
 * to do, and List, Board and Week are three ways of reading the same filtered
 * set rather than three destinations. They are children of one route so the
 * header, the filters and the pickers are loaded once and a view switch costs
 * one request.
 *
 * Registered from here rather than from `init.ts` so that a slice of the
 * module owns one file: adding a screen never means editing the same lines
 * another slice is editing. The strings come along for the ride, because the
 * host merges message bundles recursively.
 */
export function registerTaskPages(extensions: InvoiceShelfExtensionApi): void {
  extensions.addMessages(taskMessages)

  extensions.registerPage({
    id: 'tasks',
    module: MODULE,
    path: '',
    component: injectedPage(extensions, TasksPage),
    meta: {
      ability: ability.viewTask,
      title: 'tasks_projects.tasks.title',
    },
    children: [
      {
        id: 'list',
        path: '',
        component: injectedPage(extensions, TasksListView),
        meta: {
          ability: ability.viewTask,
          title: 'tasks_projects.tasks.views.list',
        },
      },
      {
        id: 'board',
        path: 'board',
        component: injectedPage(extensions, TasksBoardView),
        meta: {
          ability: ability.viewTask,
          title: 'tasks_projects.board.title',
        },
      },
      {
        id: 'week',
        path: 'week',
        component: injectedPage(extensions, TasksWeekView),
        meta: {
          ability: ability.viewOwnTime,
          title: 'tasks_projects.time.title',
        },
      },
    ],
  })

  extensions.registerPage({
    id: 'task',
    module: MODULE,
    path: 'tasks/:id',
    component: injectedPage(extensions, TaskPage),
    meta: {
      ability: ability.viewTask,
      title: 'tasks_projects.tasks.title',
    },
  })

  extensions.registerPage({
    id: 'time',
    module: MODULE,
    path: 'time',
    component: timeRedirect(extensions),
    meta: {
      ability: ability.viewOwnTime,
      title: 'tasks_projects.time.title',
    },
  })
}

/**
 * The old Time page, kept as a forward for one release.
 *
 * The timesheet is the Week view of Tasks now. Bookmarks, the release notes
 * and anything that linked to `/time` keep working rather than landing on a
 * "page not found" the host would blame the module for.
 */
function timeRedirect(extensions: InvoiceShelfExtensionApi): ReturnType<typeof defineComponent> {
  return defineComponent({
    setup: () => {
      onMounted(() => {
        void extensions.router.replace(PATHS.week)
      })

      return () => null
    },
  })
}
