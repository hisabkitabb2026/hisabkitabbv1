<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class SearchItemsTool extends AiTool
{
    private const DEFAULT_LIMIT = 10;

    private const MAX_LIMIT = 50;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'search_items';
    }

    public function description(): string
    {
        return 'Search catalog items (products/services) for the current company by free-text query (matches name and description). Returns id, name, unit price, and description.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Free-text search against name and description.'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT]], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-item', 'item'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $limit = min((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), self::MAX_LIMIT);

        return ['items' => $this->data->searchItems($companyId, empty($arguments['query']) ? null : (string) $arguments['query'], $limit)];
    }
}
