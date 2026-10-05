<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Support;

use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use Modules\TasksProjects\Application\Rounding;

/**
 * Typed reader for the module's per-company settings.
 *
 * Values live in the host settings store under `module.tasks-projects.<key>`
 * and come back as whatever the host wrote, so every getter coerces and clamps
 * rather than trusting the stored type.
 */
final class ModuleSettings
{
    public const PREFIX = 'module.tasks-projects.';

    /** @var list<int> */
    public const ROUNDING_INCREMENTS = [1, 5, 6, 15, 30, 60];

    public const DEFAULT_ROUNDING_MINUTES = 1;

    public const DEFAULT_ROUNDING_DIRECTION = Rounding::NEAREST;

    public const DEFAULT_WEEK_START = 1;

    /** Every switch the module stores, with the value a company starts from. */
    public const FLAGS = [
        'members_see_all_time' => false,
        'auto_start_tasks' => false,
        'lock_invoiced_tasks' => false,
        'hide_invoiced_on_board' => false,
        'invoice_project_heading' => false,
        'invoice_task_description' => true,
        'invoice_entry_dates' => true,
        'invoice_entry_times' => false,
        'invoice_entry_hours' => true,
        'invoice_entry_descriptions' => false,
    ];

    public function __construct(private readonly SettingsStore $settings) {}

    /** Company default hourly rate, in minor units per hour. */
    public function defaultRate(int $companyId): int
    {
        $rate = (int) $this->read($companyId, 'default_rate', 0);

        return max(0, $rate);
    }

    /** Billing increment applied when a time entry is saved. */
    public function roundingMinutes(int $companyId): int
    {
        $minutes = (int) $this->read($companyId, 'rounding_minutes', self::DEFAULT_ROUNDING_MINUTES);

        return in_array($minutes, self::ROUNDING_INCREMENTS, true) ? $minutes : self::DEFAULT_ROUNDING_MINUTES;
    }

    /** Which way the increment is taken: nearest, up or down. */
    public function roundingDirection(int $companyId): string
    {
        $direction = (string) $this->read($companyId, 'rounding_direction', self::DEFAULT_ROUNDING_DIRECTION);

        return in_array($direction, Rounding::DIRECTIONS, true) ? $direction : self::DEFAULT_ROUNDING_DIRECTION;
    }

    /** First day of the timesheet week, 0 (Sunday) through 6 (Saturday). */
    public function weekStart(int $companyId): int
    {
        $day = (int) $this->read($companyId, 'week_start', self::DEFAULT_WEEK_START);

        return $day >= 0 && $day <= 6 ? $day : self::DEFAULT_WEEK_START;
    }

    /** Whether members without the view-all-time ability still see other members' time. */
    public function membersSeeAllTime(int $companyId): bool
    {
        return $this->flag($companyId, 'members_see_all_time');
    }

    /** Whether creating a task starts its creator's timer straight away. */
    public function autoStartTasks(int $companyId): bool
    {
        return $this->flag($companyId, 'auto_start_tasks');
    }

    /** Whether a fully invoiced task refuses edits, status moves and deletion. */
    public function lockInvoicedTasks(int $companyId): bool
    {
        return $this->flag($companyId, 'lock_invoiced_tasks');
    }

    /** Whether a fully invoiced task drops off the board. */
    public function hideInvoicedOnBoard(int $companyId): bool
    {
        return $this->flag($companyId, 'hide_invoiced_on_board');
    }

    /**
     * The invoice line toggles, as the composer reads them.
     *
     * @return array{project_heading: bool, task_description: bool, entry_dates: bool, entry_times: bool, entry_hours: bool, entry_descriptions: bool}
     */
    public function invoiceLineOptions(int $companyId): array
    {
        return [
            'project_heading' => $this->flag($companyId, 'invoice_project_heading'),
            'task_description' => $this->flag($companyId, 'invoice_task_description'),
            'entry_dates' => $this->flag($companyId, 'invoice_entry_dates'),
            'entry_times' => $this->flag($companyId, 'invoice_entry_times'),
            'entry_hours' => $this->flag($companyId, 'invoice_entry_hours'),
            'entry_descriptions' => $this->flag($companyId, 'invoice_entry_descriptions'),
        ];
    }

    /** One stored switch, read the way the host may have written it. */
    public function flag(int $companyId, string $key): bool
    {
        $value = $this->read($companyId, $key, self::FLAGS[$key] ?? false);

        if (is_string($value)) {
            return in_array(strtoupper($value), ['YES', 'TRUE', '1', 'ON'], true);
        }

        return (bool) $value;
    }

    private function read(int $companyId, string $key, mixed $default): mixed
    {
        $value = $this->settings->getCompany($companyId, self::PREFIX.$key, $default);

        return $value ?? $default;
    }
}
