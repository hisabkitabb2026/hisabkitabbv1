<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application;

use InvalidArgumentException;
use InvoiceShelf\Modules\Contracts\Host\ModuleAuthorization;
use Modules\AiAssistant\Application\Tools\AiTool;

final class AiToolRegistry
{
    /** @var array<string, AiTool> */
    private array $tools = [];

    /** @param list<AiTool> $tools */
    public function __construct(private readonly ModuleAuthorization $authorization, array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(AiTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /** @return list<array<string, mixed>> */
    public function schemas(int $userId, int $companyId): array
    {
        return array_values(array_map(
            static fn (AiTool $tool): array => $tool->toOpenAiToolSchema(),
            array_filter($this->tools, fn (AiTool $tool): bool => $this->allowed($tool, $userId, $companyId)),
        ));
    }

    /** @param array<string, mixed> $arguments */
    public function execute(string $name, array $arguments, int $companyId, int $userId): mixed
    {
        $tool = $this->tools[$name] ?? throw new InvalidArgumentException("Unknown AI tool: {$name}");
        if (! $this->allowed($tool, $userId, $companyId)) {
            return ['error' => 'unauthorized', 'message' => 'You do not have permission to access this data.'];
        }

        return $tool->execute($arguments, $companyId, $userId);
    }

    private function allowed(AiTool $tool, int $userId, int $companyId): bool
    {
        $required = $tool->requiredAbility();

        return $required === null || $this->authorization->allows($userId, $companyId, $required[0], $required[1]);
    }
}
