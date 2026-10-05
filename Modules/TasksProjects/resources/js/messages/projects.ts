/**
 * Strings for the project index and the project detail tabs.
 *
 * These live beside the slice that owns them rather than in `messages.ts`, so
 * two slices of the module never edit the same catalogue. The host merges
 * every bundle recursively, so `tasks_projects.project` here and
 * `tasks_projects.tasks` there end up in one namespace.
 */
export const projectMessages = {
  en: {
    tasks_projects: {
      project: {
        load_failed: 'Unable to load the project.',
        customer: 'Customer',
        identifier: 'Identifier',
        due_date: 'Due date',
        board: 'Board',
        tasks: 'Tasks',
        invoice_project: 'Invoice project',
        tabs: {
          overview: 'Overview',
          tasks: 'Tasks',
          time: 'Time',
          members: 'Members',
        },
        overview: {
          tasks: 'Tasks',
          open_tasks: '{count} open',
          closed_tasks: '{count} done',
          logged: 'Logged',
          billable: 'Billable',
          billable_amount: 'Billable value',
          unbilled_amount: 'Unbilled',
          budget: 'Budget',
          budget_used: '{used} of {total}',
          budget_over: 'Over budget by {amount}',
          no_budget: 'No budget set.',
          description: 'Description',
          no_description: 'No description yet.',
        },
        time: {
          title: 'Time log',
          add_entry: 'Add entry',
          columns: {
            date: 'Date',
            member: 'Member',
            task: 'Task',
            minutes: 'Duration',
            billable: 'Billable',
            amount: 'Amount',
          },
          running: 'Running',
          removed_member: 'Removed member',
          load_failed: 'Unable to load the time entries.',
        },
        members: {
          title: 'Members',
          member: 'Member',
          rate: 'Rate / hour',
          rate_help: 'Per hour on this project. Leave empty to use the project default.',
          attach: 'Add member',
          attach_placeholder: 'Choose a member',
          attached: '{name} was added to the project.',
          detached: '{name} was removed from the project.',
          detach_confirm: 'Remove {name} from this project? Their time entries stay.',
          empty: 'Nobody is on this project yet.',
          all_attached: 'Every company member is already on this project.',
          load_failed: 'Unable to load the project members.',
          attach_failed: 'Unable to add the member.',
          detach_failed: 'Unable to remove the member.',
        },
      },
    },
  },
}
