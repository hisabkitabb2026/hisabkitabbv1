<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\TasksProjects\Support\CompanyContext;

/**
 * Attaching a member.
 *
 * The user has to be a member of the company the request is scoped to, which is
 * a host fact, so it is read through the company data contract rather than by
 * touching a host table.
 */
final class AttachProjectMemberRequest extends ModuleRequest
{
    /** @return array<string, list<string|In>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1', Rule::in($this->companyMemberIds())],
            'rate' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.in' => 'The selected user is not a member of this company.',
        ];
    }

    /** @return list<int> */
    private function companyMemberIds(): array
    {
        $context = CompanyContext::fromRequest($this);

        return array_map(
            static fn (array $member): int => (int) $member['id'],
            app(CompanyDataReader::class)->companyMembers($context->companyId),
        );
    }
}
