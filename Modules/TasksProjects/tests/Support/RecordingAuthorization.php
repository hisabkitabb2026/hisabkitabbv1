<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Support;

use InvoiceShelf\Modules\Contracts\Host\ModuleAuthorization;

/**
 * Allows everything except what a test explicitly denies, and records every
 * check so a test can assert which ability was consulted.
 */
final class RecordingAuthorization implements ModuleAuthorization
{
    /** @var list<array{user_id: int, company_id: int, ability: string, resource: string|null}> */
    public array $checks = [];

    /** @var list<string> abilities to refuse, as `ability` or `ability:resource` */
    public array $denied = [];

    public function allows(int $userId, int $companyId, string $ability, ?string $resource = null): bool
    {
        $this->checks[] = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'ability' => $ability,
            'resource' => $resource,
        ];

        return ! in_array($ability, $this->denied, true)
            && ! in_array($ability.':'.(string) $resource, $this->denied, true);
    }

    public function deny(string ...$abilities): self
    {
        foreach ($abilities as $ability) {
            $this->denied[] = $ability;
        }

        return $this;
    }
}
