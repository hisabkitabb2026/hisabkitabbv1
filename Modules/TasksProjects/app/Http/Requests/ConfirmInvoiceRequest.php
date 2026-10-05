<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

/**
 * The ids the host gave back after the browser posted the prepared invoice.
 *
 * Each item pairs one invoice line with the entries behind it, which is exactly
 * the shape `prepare` returned, so the caller never has to re-derive the
 * grouping.
 */
final class ConfirmInvoiceRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'integer', 'min:1'],
            'items.*.entry_ids' => ['required', 'array', 'min:1'],
            'items.*.entry_ids.*' => ['integer', 'min:1'],
        ];
    }
}
