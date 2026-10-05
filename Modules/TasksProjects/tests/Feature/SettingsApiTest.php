<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Modules\TasksProjects\Application\Rounding;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class SettingsApiTest extends TestCase
{
    private const COMPANY = 9;

    public function test_a_company_that_has_never_saved_a_setting_gets_the_defaults(): void
    {
        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/settings');

        $response->assertOk();
        $response->assertExactJson(['data' => [
            'default_rate' => 0,
            'rounding_minutes' => ModuleSettings::DEFAULT_ROUNDING_MINUTES,
            'rounding_direction' => Rounding::NEAREST,
            'week_start' => ModuleSettings::DEFAULT_WEEK_START,
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
            'rounding_increments' => ModuleSettings::ROUNDING_INCREMENTS,
        ]]);
    }

    public function test_the_offered_increments_cover_the_way_firms_actually_bill(): void
    {
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/settings')
            ->assertOk()
            ->assertJsonPath('data.rounding_increments', [1, 5, 6, 15, 30, 60]);
    }

    public function test_the_new_switches_and_the_direction_come_back_as_stored(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_direction', Rounding::UP);
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'auto_start_tasks', 'YES');
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'lock_invoiced_tasks', 1);
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'hide_invoiced_on_board', true);
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'invoice_task_description', false);
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'invoice_entry_times', 'true');

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/settings')
            ->assertOk()
            ->assertJsonPath('data.rounding_direction', Rounding::UP)
            ->assertJsonPath('data.auto_start_tasks', true)
            ->assertJsonPath('data.lock_invoiced_tasks', true)
            ->assertJsonPath('data.hide_invoiced_on_board', true)
            ->assertJsonPath('data.invoice_task_description', false)
            ->assertJsonPath('data.invoice_entry_times', true);
    }

    public function test_a_direction_the_module_does_not_know_falls_back_to_nearest(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_direction', 'sideways');

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/settings')
            ->assertJsonPath('data.rounding_direction', Rounding::NEAREST);
    }

    public function test_stored_values_come_back_typed_whatever_the_host_wrote(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'default_rate', '12000');
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', '15');
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'week_start', '0');
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'members_see_all_time', 'YES');

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/settings');

        $response->assertOk();
        $response->assertJsonPath('data.default_rate', 12000);
        $response->assertJsonPath('data.rounding_minutes', 15);
        $response->assertJsonPath('data.week_start', 0);
        $response->assertJsonPath('data.members_see_all_time', true);
    }

    public function test_an_unusable_stored_value_falls_back_to_the_default(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 7);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/settings')
            ->assertJsonPath('data.rounding_minutes', ModuleSettings::DEFAULT_ROUNDING_MINUTES);
    }

    public function test_settings_are_per_company(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'default_rate', 12000);

        $this->asCompany(10)
            ->getJson('/api/v1/tasks-projects/settings')
            ->assertJsonPath('data.default_rate', 0);
    }

    public function test_reading_the_settings_needs_the_project_view_ability(): void
    {
        $this->authorization->deny(Authorizes::id(Abilities::VIEW_PROJECT));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/settings')
            ->assertForbidden();
    }
}
