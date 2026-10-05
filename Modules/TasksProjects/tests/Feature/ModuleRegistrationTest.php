<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\Registry;
use Modules\TasksProjects\Application\Rounding;
use Modules\TasksProjects\Lifecycle\DataCleanup;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\ModuleRegistration;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class ModuleRegistrationTest extends TestCase
{
    public function test_it_registers_a_local_script_and_style(): void
    {
        $modulePath = dirname(__DIR__, 2);

        ModuleRegistration::register($modulePath);

        self::assertSame(realpath($modulePath.'/dist/init.js'), Registry::scriptFor('tasks-projects'));
        self::assertSame(realpath($modulePath.'/dist/style.css'), Registry::styleFor('tasks-projects'));
    }

    public function test_it_registers_projects_and_tasks_as_two_entries_of_the_core_main_group(): void
    {
        ModuleRegistration::register(dirname(__DIR__, 2));

        self::assertSame([
            'group' => 'main',
            'group_label' => '',
            'priority' => 40,
            'title' => 'tasksprojects::menu.projects',
            'link' => '/admin/modules/tasks-projects/projects',
            'icon' => 'FolderIcon',
        ], Registry::menuFor('tasks-projects.projects'));

        self::assertSame([
            'group' => 'main',
            'group_label' => '',
            'priority' => 50,
            'title' => 'tasksprojects::menu.tasks',
            'link' => '/admin/modules/tasks-projects',
            'icon' => 'ClipboardDocumentListIcon',
        ], Registry::menuFor('tasks-projects'));

        // The primary slug still answers, which is what the host's module page
        // lookup uses; the second key only ever adds a row to the sidebar.
        self::assertSame(
            ['tasks-projects.projects', 'tasks-projects'],
            array_keys(Registry::allMenu()),
        );
    }

    public function test_the_menu_titles_are_translation_keys_that_exist(): void
    {
        $menu = require dirname(__DIR__, 2).'/lang/en/menu.php';

        self::assertSame('Projects', $menu['projects']);
        self::assertSame('Tasks', $menu['tasks']);
        self::assertArrayHasKey('title', $menu, 'The original key stays for compatibility.');
    }

    public function test_the_settings_schema_covers_every_stored_key(): void
    {
        ModuleRegistration::register(dirname(__DIR__, 2));

        $settings = Registry::settingsFor('tasks-projects');

        self::assertNotNull($settings);

        $fields = array_column($settings->fields(), null, 'key');

        self::assertSame([
            'default_rate',
            'rounding_minutes',
            'rounding_direction',
            'week_start',
            'members_see_all_time',
            'auto_start_tasks',
            'lock_invoiced_tasks',
            'hide_invoiced_on_board',
            'invoice_project_heading',
            'invoice_task_description',
            'invoice_entry_dates',
            'invoice_entry_times',
            'invoice_entry_hours',
            'invoice_entry_descriptions',
        ], array_keys($fields));

        self::assertSame(0, $fields['default_rate']['default']);
        self::assertSame(
            ['1' => '1', '5' => '5', '6' => '6', '15' => '15', '30' => '30', '60' => '60'],
            $fields['rounding_minutes']['options'],
        );
        self::assertSame(ModuleSettings::DEFAULT_ROUNDING_MINUTES, $fields['rounding_minutes']['default']);
        self::assertSame(Rounding::NEAREST, $fields['rounding_direction']['default']);
        self::assertSame(
            ['nearest', 'up', 'down'],
            array_keys($fields['rounding_direction']['options']),
        );
        self::assertSame(ModuleSettings::DEFAULT_WEEK_START, $fields['week_start']['default']);

        foreach (ModuleSettings::FLAGS as $key => $default) {
            self::assertSame('switch', $fields[$key]['type'], "Setting {$key} is not a switch.");
            self::assertSame($default, $fields[$key]['default'], "Setting {$key} has the wrong default.");
        }
    }

    public function test_every_select_option_reaches_the_form_translated(): void
    {
        ModuleRegistration::register(dirname(__DIR__, 2));

        $settings = Registry::settingsFor('tasks-projects');

        self::assertNotNull($settings);

        $fields = array_column($settings->fields(), null, 'key');

        self::assertSame(
            [Rounding::NEAREST => 'Nearest', Rounding::UP => 'Up', Rounding::DOWN => 'Down'],
            $fields['rounding_direction']['options'],
        );

        // The host translates a field's label and a section's title and hands
        // the options over as they were registered, so an option naming a key
        // would reach the screen as the key.
        foreach ($fields as $key => $field) {
            foreach ($field['options'] ?? [] as $label) {
                self::assertStringNotContainsString(
                    '::',
                    (string) $label,
                    "Setting {$key} offers an option the reader would see as a translation key.",
                );
            }
        }
    }

    public function test_the_schema_and_the_cleanup_keys_never_drift_apart(): void
    {
        ModuleRegistration::register(dirname(__DIR__, 2));

        $settings = Registry::settingsFor('tasks-projects');

        self::assertNotNull($settings);
        self::assertSame(
            array_column($settings->fields(), 'key'),
            DataCleanup::settingKeys(),
        );
    }

    public function test_it_contributes_the_whole_ability_catalogue_namespaced_by_slug(): void
    {
        ModuleRegistration::register(dirname(__DIR__, 2));

        $abilities = Registry::abilitiesFor(Abilities::SLUG);

        self::assertSame([
            'tasks-projects:view-project',
            'tasks-projects:create-project',
            'tasks-projects:edit-project',
            'tasks-projects:delete-project',
            'tasks-projects:view-task',
            'tasks-projects:create-task',
            'tasks-projects:edit-task',
            'tasks-projects:delete-task',
            'tasks-projects:manage-task-status',
            'tasks-projects:view-own-time',
            'tasks-projects:view-all-time',
            'tasks-projects:edit-all-time',
            'tasks-projects:invoice-tasks',
        ], array_column($abilities, 'ability'));

        self::assertSame([
            'View projects',
            'Create projects',
            'Edit projects',
            'Delete projects',
            'View tasks',
            'Create tasks',
            'Edit tasks',
            'Delete tasks',
            'Manage task statuses',
            'View own time',
            'View all time',
            'Edit all time',
            'Invoice tasks',
        ], array_column($abilities, 'name'));
    }

    public function test_billing_depends_on_seeing_all_time_and_on_both_host_invoice_abilities(): void
    {
        ModuleRegistration::register(dirname(__DIR__, 2));

        $abilities = array_column(Registry::abilitiesFor(Abilities::SLUG), 'depends_on', 'ability');

        self::assertSame(
            ['tasks-projects:view-all-time', 'create-invoice', 'edit-invoice'],
            $abilities['tasks-projects:invoice-tasks'],
        );
        self::assertSame(
            ['tasks-projects:view-project', 'view-customer'],
            $abilities['tasks-projects:create-project'],
        );
        self::assertSame([], $abilities['tasks-projects:view-project']);
    }

    public function test_its_data_cleanup_deletes_every_company_setting_and_is_safe_to_repeat(): void
    {
        $settings = new class implements SettingsStore
        {
            /** @var list<string> */
            public array $removedCompanyKeys = [];

            public function getGlobal(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function putGlobal(string $key, mixed $value): void {}

            public function deleteGlobal(string $key): void {}

            public function getCompany(int $companyId, string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function putCompany(int $companyId, string $key, mixed $value): void {}

            public function deleteCompany(int $companyId, string $key): void {}

            public function deleteCompanyForAll(string $key): void
            {
                $this->removedCompanyKeys[] = $key;
            }
        };

        $cleanup = new DataCleanup($settings);

        $cleanup->cleanup();
        $cleanup->cleanup();

        $expected = array_map(
            static fn (string $key): string => ModuleSettings::PREFIX.$key,
            DataCleanup::settingKeys(),
        );

        self::assertSame([...$expected, ...$expected], $settings->removedCompanyKeys);
        self::assertContains(ModuleSettings::PREFIX.'rounding_direction', $settings->removedCompanyKeys);
        self::assertContains(ModuleSettings::PREFIX.'lock_invoiced_tasks', $settings->removedCompanyKeys);
        self::assertContains(ModuleSettings::PREFIX.'invoice_entry_hours', $settings->removedCompanyKeys);
    }
}
