<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

/**
 * The optional window the unbilled time page narrows the roll-up to.
 *
 * There is no customer here on purpose: this is the list of customers, so
 * naming one would be asking the wrong question.
 */
final class UnbilledCustomersRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ];
    }
}
