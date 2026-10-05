/**
 * Every string invoicing renders, wherever it is started from.
 *
 * Kept beside the slice that owns it rather than in `messages.ts`, so two
 * slices of the module never edit the same catalogue. The host merges each
 * bundle recursively, so these land under the same `tasks_projects` namespace
 * as the rest.
 */
export const billingMessages = {
  en: {
    tasks_projects: {
      billing: {
        title: 'Unbilled time',
        subtitle: 'Time that has not reached an invoice yet, by customer.',
        back: 'Back to customers',
        create: 'Create invoice',
        busy: 'Creating the invoice',

        // What the sequence says when it cannot finish.
        prepare_failed: 'Unable to prepare the invoice.',
        create_failed: 'Unable to create the invoice.',
        rate_failed: 'Unable to read the exchange rate; the invoice was created without one.',
        forbidden: 'You are not allowed to invoice time.',
        nothing_to_invoice: 'No unbilled billable time on the selected tasks.',
        mixed_customers: 'Select tasks of one customer. This selection spans {count} customers.',
        mixed_selection: 'One invoice covers one customer in one currency. Narrow the selection.',
        pending_stamp:
          'Finish marking the last invoice as billed before creating another one.',
        created: 'Invoice {number} was created.',
        stamped:
          '{count} time entry was marked as invoiced. | {count} time entries were marked as invoiced.',
        stamp_failed: 'Unable to mark the time as invoiced.',
        stamp_failed_notice:
          'Invoice {number} was created, but its time is not marked as invoiced yet.',
        stamp_unmatched:
          'Invoice {number} was created, but its lines could not be matched back to the time behind them.',

        number: {
          title: 'Invoice number',
          description:
            'This company numbers its invoices by hand, so the draft needs a number before it can be created.',
          label: 'Number',
          save: 'Create invoice',
        },

        retry: {
          title: 'The invoice was created, but the time is not marked yet',
          description:
            'Invoice {number} exists. Its time entries still count as unbilled until they are marked, which is safe to run again.',
          action: 'Retry stamping',
          open_invoice: 'Open the invoice',
          dismiss: 'Forget this invoice',
          dismiss_confirm:
            'Forget this invoice? Its time stays unbilled and can reach a second invoice.',
        },

        customer: {
          title: 'Who has time waiting?',
          description: 'Customers with billable time that has not reached an invoice yet.',
          entries: '{count} entry | {count} entries',
          empty_title: 'Nothing to invoice',
          empty_description:
            'Billable time appears here once it has been logged against a task that belongs to a customer.',
          load_failed: 'Unable to load the customers with unbilled time.',
          from: 'From',
          to: 'To',
          clear_range: 'Clear dates',
        },

        entries: {
          title: 'Which time goes on the invoice?',
          grouping: 'Group lines by',
          group_by: {
            task: 'Task',
            project: 'Project',
            member: 'Member',
            summary: 'One summary line',
          },
          select_all: 'Select all',
          selected: '{count} of {total} entries selected',
          selected_total: 'Selected: {hours}',
          no_description: 'No description',
          columns: {
            date: 'Date',
            task: 'Task',
            project: 'Project',
            member: 'Member',
            duration: 'Duration',
            amount: 'Amount',
          },
          empty_title: 'No unbilled time',
          empty_description: 'This customer has nothing waiting to be invoiced in this range.',
          load_failed: 'Unable to load the unbilled time.',
          none_selected: 'Select at least one entry.',
        },
      },
    },
  },
}
