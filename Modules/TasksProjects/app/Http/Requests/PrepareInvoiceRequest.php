<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Application\BillingService;

/**
 * What to invoice, in exactly one of the three shapes the UI can offer.
 *
 * `entry_ids` is the unbilled time page ticking rows off; `task_ids` is one
 * task row, a task page or a bulk selection; `project_id` is "invoice this
 * project". They are mutually exclusive rather than additive, so a screen can
 * never half-say what it meant, and the grouping is optional because a line
 * per task is what every entry point wants.
 */
final class PrepareInvoiceRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'entry_ids' => ['array', 'min:1', 'required_without_all:task_ids,project_id', 'prohibits:task_ids,project_id'],
            'entry_ids.*' => ['integer', 'min:1'],
            'task_ids' => ['array', 'min:1', 'required_without_all:entry_ids,project_id', 'prohibits:entry_ids,project_id'],
            'task_ids.*' => ['integer', 'min:1'],
            'project_id' => ['integer', 'min:1', 'required_without_all:entry_ids,task_ids', 'prohibits:entry_ids,task_ids'],
            'grouping' => ['sometimes', 'string', 'in:'.implode(',', BillingService::GROUPINGS)],
        ];
    }
}
