/**
 * Strings for the reports page.
 *
 * These live beside the slice that owns them rather than in `messages.ts`, so
 * two slices of the module never edit the same catalogue. The host merges
 * every bundle recursively, so `tasks_projects.reports` here and
 * `tasks_projects.projects` there end up in one namespace.
 */
export const reportMessages = {
  en: {
    tasks_projects: {
      reports: {
        title: 'Reports',
        load_failed: 'Unable to load the report.',
        empty_title: 'Nothing logged in this range',
        empty_description: 'Pick a wider range, or log some time against a task.',
        range: {
          this_week: 'This week',
          this_month: 'This month',
          last_month: 'Last month',
          this_quarter: 'This quarter',
          this_year: 'This year',
          custom: 'Custom',
          from: 'From',
          to: 'To',
        },
        summary: {
          logged: 'Logged',
          billable: 'Billable',
          amount: 'Amount',
          unbilled: 'Unbilled',
          currency: 'Currency #{id}',
          base_currency: 'Company currency',
        },
        split: {
          title: 'Billable against the rest',
          billable: 'Billable',
          non_billable: 'Not billable',
          nothing: 'No time logged in this range.',
        },
        tables: {
          by_project: 'By project',
          by_member: 'By member',
          by_customer: 'By customer',
          project: 'Project',
          member: 'Member',
          customer: 'Customer',
          no_project: 'No project',
          no_customer: 'Internal',
          unknown_member: 'Removed member',
          currency: 'Currency',
          logged: 'Logged',
          billable: 'Billable',
          amount: 'Amount',
          unbilled: 'Unbilled',
        },
      },
    },
  },
}
