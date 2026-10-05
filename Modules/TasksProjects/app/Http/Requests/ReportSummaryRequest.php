<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class ReportSummaryRequest extends ModuleRequest
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
