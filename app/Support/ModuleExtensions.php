<?php

// HisabKitab feature

declare(strict_types=1);

namespace App\Support;

/**
 * Module-driven extension registry for the host's backend.
 * Modules register callbacks from their ServiceProvider::boot() method.
 * This is the PHP-side counterpart of the frontend extensionRegistry.
 */
final class ModuleExtensions
{
    /** @var array<string, callable> */
    private static array $dashboardCountProviders = [];

    /** @var array<string, callable> */
    private static array $invoiceFilters = [];

    /** @var array<string, callable> */
    private static array $estimateFilters = [];

    /** @var array<string, array{model: string, scope: array<string, mixed>}> */
    private static array $serialNumberTypes = [];

    /** @var array<string, callable> */
    private static array $invoiceResourceFields = [];

    /** @var array<string, callable> */
    private static array $invoiceItemResourceFields = [];

    /** @var array<string, callable> */
    private static array $estimateItemResourceFields = [];

    /** @var array<string, callable> */
    private static array $invoiceUniquenessRules = [];

    /** @var array<string, callable> */
    private static array $estimateUniquenessRules = [];

    /** @var array<string, callable> */
    private static array $menuFilters = [];

    public static function registerDashboardCountProvider(string $module, callable $callback): void
    {
        self::$dashboardCountProviders[$module] = $callback;
    }

    /** @return list<array{key: string, label: string, to: string, value: int}> */
    public static function dashboardCounts(int $companyId): array
    {
        $counts = [];
        foreach (self::$dashboardCountProviders as $provider) {
            $result = $provider($companyId);
            if (is_array($result)) {
                foreach ($result as $entry) {
                    $counts[] = $entry;
                }
            }
        }

        return $counts;
    }

    public static function registerInvoiceFilter(string $module, callable $callback): void
    {
        self::$invoiceFilters[$module] = $callback;
    }

    public static function applyInvoiceFilters(string $filter, mixed $value, mixed $query): bool
    {
        if (! isset(self::$invoiceFilters[$filter])) {
            return false;
        }
        (self::$invoiceFilters[$filter])($value, $query);

        return true;
    }

    public static function registerEstimateFilter(string $module, callable $callback): void
    {
        self::$estimateFilters[$module] = $callback;
    }

    public static function applyEstimateFilters(string $filter, mixed $value, mixed $query): bool
    {
        if (! isset(self::$estimateFilters[$filter])) {
            return false;
        }
        (self::$estimateFilters[$filter])($value, $query);

        return true;
    }

    /** @param array<string, mixed> $scope */
    public static function registerSerialNumberType(string $type, string $model, array $scope): void
    {
        self::$serialNumberTypes[$type] = ['model' => $model, 'scope' => $scope];
    }

    public static function hasSerialNumberType(string $type): bool
    {
        return isset(self::$serialNumberTypes[$type]);
    }

    /** @return array{model: string, scope: array<string, mixed>}|null */
    public static function registerInvoiceResourceFields(string $module, callable $callback): void
    {
        self::$invoiceResourceFields[$module] = $callback;
    }

    /** @return array<string, mixed> */
    public static function invoiceResourceFields(mixed $invoice): array
    {
        $fields = [];
        foreach (self::$invoiceResourceFields as $provider) {
            $result = $provider($invoice);
            if (is_array($result)) {
                $fields = array_merge($fields, $result);
            }
        }

        return $fields;
    }

    public static function registerInvoiceItemResourceFields(string $module, callable $callback): void
    {
        self::$invoiceItemResourceFields[$module] = $callback;
    }

    /** @return array<string, mixed> */
    public static function invoiceItemResourceFields(mixed $item): array
    {
        $fields = [];
        foreach (self::$invoiceItemResourceFields as $provider) {
            $result = $provider($item);
            if (is_array($result)) {
                $fields = array_merge($fields, $result);
            }
        }

        return $fields;
    }

    public static function registerEstimateItemResourceFields(string $module, callable $callback): void
    {
        self::$estimateItemResourceFields[$module] = $callback;
    }

    /** @return array<string, mixed> */
    public static function estimateItemResourceFields(mixed $item): array
    {
        $fields = [];
        foreach (self::$estimateItemResourceFields as $provider) {
            $result = $provider($item);
            if (is_array($result)) {
                $fields = array_merge($fields, $result);
            }
        }

        return $fields;
    }

    public static function registerInvoiceUniquenessRule(string $module, callable $callback): void
    {
        self::$invoiceUniquenessRules[$module] = $callback;
    }

    public static function buildInvoiceUniquenessRule(mixed $request): mixed
    {
        $templateName = $request->input('template_name');
        foreach (self::$invoiceUniquenessRules as $builder) {
            $rule = $builder($request, $templateName);
            if ($rule !== null) {
                return $rule;
            }
        }

        return null;
    }

    public static function registerEstimateUniquenessRule(string $module, callable $callback): void
    {
        self::$estimateUniquenessRules[$module] = $callback;
    }

    public static function buildEstimateUniquenessRule(mixed $request): mixed
    {
        $templateName = $request->input('template_name');
        foreach (self::$estimateUniquenessRules as $builder) {
            $rule = $builder($request, $templateName);
            if ($rule !== null) {
                return $rule;
            }
        }

        return null;
    }

    public static function registerMenuFilter(string $module, callable $callback): void
    {
        self::$menuFilters[$module] = $callback;
    }

    /**
     * Apply registered menu filters. Each filter receives the menu array
     * and returns a filtered array, letting modules hide or replace entries.
     *
     * @param  list<array<string, mixed>>  $menu
     * @return list<array<string, mixed>>
     */
    public static function applyMenuFilters(array $menu): array
    {
        foreach (self::$menuFilters as $filter) {
            $result = $filter($menu);
            if (is_array($result)) {
                $menu = $result;
            }
        }

        return $menu;
    }

    public static function flush(): void
    {
        self::$dashboardCountProviders = [];
        self::$invoiceFilters = [];
        self::$estimateFilters = [];
        self::$serialNumberTypes = [];
        self::$invoiceResourceFields = [];
        self::$invoiceItemResourceFields = [];
        self::$estimateItemResourceFields = [];
        self::$invoiceUniquenessRules = [];
        self::$estimateUniquenessRules = [];
        self::$menuFilters = [];
    }
}
