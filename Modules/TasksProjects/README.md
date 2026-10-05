# InvoiceShelf Tasks and Projects

The official Tasks, Projects and Time Tracking module for InvoiceShelf 3.x. It adds two sidebar
entries, Projects and Tasks, and builds invoicing the way Invoice Ninja does it: pick the work
(a task, a selection of tasks, or a whole project) and land on a draft invoice, rather than
picking a customer first and hunting for what to bill.

The module is `AGPL-3.0-only`.

## Requirements

- InvoiceShelf `>=3.0.0-alpha.2 <4.0.0`
- Module API `^1.3.0`
- PHP `^8.4.0`

## What it adds

- **Projects.** Name, optional customer, description, colour, status, default billable rate,
  budget and due date, reachable from its own sidebar entry. A project without a customer is
  internal and never reaches the billing screens.
- **Tasks, with three views over one filter.** The Tasks screen is the module's sidebar root: a
  view switcher moves between a sortable **List**, a **Board** with drag ordering across the
  company's task statuses, and a **Week** timesheet, and the project, member and status filters
  in the address bar survive every switch. A task also has its own page, with the time log
  underneath it.
- **Time tracking, everywhere a task appears.** Start or stop a task's timer from its row in the
  list, its card on the board, its own page, or the floating quick-start button that stays
  reachable from any screen (it search-picks a task by name and starts or stops on it without
  leaving the page you are on). Only one timer runs per user per company; starting a second one
  offers to stop the first. A header chip shows the elapsed time and opens the running task.
- **The task time log.** A task's own page lists every interval logged against it, hand-entered
  or from the timer: start, end, duration, description, billable, and who logged it. A row
  already on an invoice is marked and its time, billable flag and task cannot change; its
  description still can.
- **Rate resolution.** Task rate, then the assignee's project rate, then the project default,
  then the company default from module settings, written onto the time entry so a later rate
  change never rewrites history.
- **Invoicing from the work, not from a wizard.** "Invoice" on a task row, the bulk selection
  bar, a task's own page, or a project's header prepares a draft invoice, one line per task, and
  opens it on the host's own invoice edit screen, ready to review and send. A selection spanning
  two customers or two currencies is refused with a clear message instead of guessing. The
  **Unbilled time** page (linked from Reports and from the Projects header) answers the
  month-end question across every project and customer at once, and is where a single entry can
  still be left off an invoice on purpose.
- **Settings**, under **Company Settings → Tasks and Projects**: the default hourly rate, the
  rounding increment and whether a stopped entry rounds to the nearest increment, up, or down,
  the first day of the week, whether members see each other's time, whether creating a task
  starts its creator's timer, whether an invoiced task locks against further edits, whether an
  invoiced task drops off the board, and which parts of an invoice line an invoiced task writes
  (a project heading, the task's own description, and each entry's date, time range, hours and
  description). The module's own settings page under the module menu shows the current value of
  every one of these next to a link to the form that edits them.
- **Abilities.** `view-project`, `create-project`, `edit-project` and `delete-project`;
  `view-task`, `create-task`, `edit-task`, `delete-task` and `manage-task-status`; `view-own-time`,
  `view-all-time` and `edit-all-time`; and `invoice-tasks`, which also requires the host's own
  `create-invoice` and `edit-invoice` abilities, because invoicing a task ends on the host's
  invoice edit page.

See [`specs/tasks-projects.md`](../specs/tasks-projects.md) in the private specs repository for the
full scope and data model.

## Install and configure

1. Sign in as a super administrator and open **Administration → Modules**.
2. Pair the application with the InvoiceShelf marketplace if it is not already paired, then install
   and enable **Tasks and Projects**.
3. Open **Company Settings → Tasks and Projects** to set the default hourly rate, the rounding
   increment and direction, the first day of the week, who may see other members' time, and the
   task and invoice-line behaviour described above.

## Disable and uninstall

Disabling the module turns off its UI and routes but retains its data. Uninstalling it removes the
package. If an administrator also selects **Remove module data**, the module deletes its per-company
settings and reverses its migrations. This data removal is permanent.

## Development

The package ships committed files in `dist/` because InvoiceShelf installs immutable packages
without running Composer or a JavaScript package manager. See [CONTRIBUTING.md](CONTRIBUTING.md)
for the exact local checks, generated-asset workflow, and release process.

## License

The Tasks and Projects module is licensed under [AGPL-3.0-only](LICENSE).
