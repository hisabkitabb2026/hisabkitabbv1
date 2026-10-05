<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Support;

use InvoiceShelf\Modules\Registry;
use InvoiceShelf\Modules\Settings\FieldType;
use Modules\TasksProjects\Application\Rounding;

final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('tasks-projects', $modulePath.'/dist/init.js');
        Registry::registerStyle('tasks-projects', $modulePath.'/dist/style.css');

        self::registerMenu();
        Registry::registerSettings('tasks-projects', self::settingsSchema());
        self::registerAbilities();
    }

    /**
     * Two sidebar entries, in the host's own main group.
     *
     * Projects and Tasks are two ways into the same module, not one feature and
     * its sub-page: people either plan work or do work. They join `main` after
     * Items (priorities 10, 20, 30) because a firm that installs this module
     * lives in it all day, and a "Modules" heading would file it away as an
     * add-on. The registry keys are separate, so `menuFor('tasks-projects')`
     * still answers with the module's primary entry.
     */
    private static function registerMenu(): void
    {
        Registry::registerMenu('tasks-projects.projects', [
            'title' => 'tasksprojects::menu.projects',
            'link' => '/admin/modules/tasks-projects/projects',
            'icon' => 'FolderIcon',
            'group' => 'main',
            'group_label' => '',
            // Lower sorts first within the group; the core entries end at 30.
            'priority' => 40,
        ]);

        Registry::registerMenu('tasks-projects', [
            'title' => 'tasksprojects::menu.tasks',
            'link' => '/admin/modules/tasks-projects',
            'icon' => 'ClipboardDocumentListIcon',
            'group' => 'main',
            'group_label' => '',
            'priority' => 50,
        ]);
    }

    /**
     * The per-company settings the host renders and validates.
     *
     * General holds how time is measured and who may see it; the second section
     * is only about what an invoice line says, which is a different question and
     * a different audience.
     *
     * @return array<string, mixed>
     */
    private static function settingsSchema(): array
    {
        return [
            'sections' => [
                [
                    'title' => 'tasksprojects::settings.general_section',
                    'fields' => [
                        [
                            'key' => 'default_rate',
                            'type' => FieldType::Number->value,
                            'label' => 'tasksprojects::settings.default_rate',
                            'default' => 0,
                            'rules' => ['integer', 'min:0'],
                        ],
                        [
                            'key' => 'rounding_minutes',
                            'type' => FieldType::Select->value,
                            'label' => 'tasksprojects::settings.rounding_minutes',
                            'default' => ModuleSettings::DEFAULT_ROUNDING_MINUTES,
                            'options' => self::roundingOptions(),
                        ],
                        [
                            'key' => 'rounding_direction',
                            'type' => FieldType::Select->value,
                            'label' => 'tasksprojects::settings.rounding_direction',
                            'default' => ModuleSettings::DEFAULT_ROUNDING_DIRECTION,
                            'options' => self::directionOptions(),
                        ],
                        [
                            'key' => 'week_start',
                            'type' => FieldType::Select->value,
                            'label' => 'tasksprojects::settings.week_start',
                            'default' => ModuleSettings::DEFAULT_WEEK_START,
                            'options' => [
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ],
                        ],
                        self::switchField('members_see_all_time'),
                        self::switchField('auto_start_tasks'),
                        self::switchField('lock_invoiced_tasks'),
                        self::switchField('hide_invoiced_on_board'),
                    ],
                ],
                [
                    'title' => 'tasksprojects::settings.invoice_section',
                    'fields' => [
                        self::switchField('invoice_project_heading'),
                        self::switchField('invoice_task_description'),
                        self::switchField('invoice_entry_dates'),
                        self::switchField('invoice_entry_times'),
                        self::switchField('invoice_entry_hours'),
                        self::switchField('invoice_entry_descriptions'),
                    ],
                ],
            ],
        ];
    }

    /**
     * One stored switch, taking its default from the same table the readers use.
     *
     * @return array<string, mixed>
     */
    private static function switchField(string $key): array
    {
        return [
            'key' => $key,
            'type' => FieldType::Switch_->value,
            'label' => 'tasksprojects::settings.'.$key,
            'default' => ModuleSettings::FLAGS[$key],
        ];
    }

    /**
     * The rounding directions, already in the reader's language.
     *
     * The host translates a field's label and a section's title, but hands a
     * select's options to the form as they were registered, so an option that
     * named a translation key would reach the screen as the key itself.
     *
     * @return array<string, string>
     */
    private static function directionOptions(): array
    {
        return [
            Rounding::NEAREST => __('tasksprojects::settings.rounding_nearest'),
            Rounding::UP => __('tasksprojects::settings.rounding_up'),
            Rounding::DOWN => __('tasksprojects::settings.rounding_down'),
        ];
    }

    /** @return array<int, string> */
    private static function roundingOptions(): array
    {
        $options = [];

        foreach (ModuleSettings::ROUNDING_INCREMENTS as $minutes) {
            $options[$minutes] = (string) $minutes;
        }

        return $options;
    }

    /**
     * Contribute the module's ability catalogue to the host role editor.
     *
     * The registry namespaces every name as `tasks-projects:{ability}`, so the
     * ids below can never collide with a host ability. Dependencies on a host
     * ability stay bare; dependencies on a module ability are namespaced with
     * Registry::abilityId(). See specs/tasks-projects.md "Authorization".
     */
    private static function registerAbilities(): void
    {
        $viewProject = Registry::abilityId(Abilities::SLUG, Abilities::VIEW_PROJECT);
        $viewTask = Registry::abilityId(Abilities::SLUG, Abilities::VIEW_TASK);
        $viewOwnTime = Registry::abilityId(Abilities::SLUG, Abilities::VIEW_OWN_TIME);
        $viewAllTime = Registry::abilityId(Abilities::SLUG, Abilities::VIEW_ALL_TIME);

        $abilities = [
            [Abilities::VIEW_PROJECT, 'View projects', []],
            [Abilities::CREATE_PROJECT, 'Create projects', [$viewProject, Abilities::HOST_VIEW_CUSTOMER]],
            [Abilities::EDIT_PROJECT, 'Edit projects', [$viewProject, Abilities::HOST_VIEW_CUSTOMER]],
            [Abilities::DELETE_PROJECT, 'Delete projects', [$viewProject]],
            [Abilities::VIEW_TASK, 'View tasks', [$viewProject]],
            [Abilities::CREATE_TASK, 'Create tasks', [$viewTask]],
            [Abilities::EDIT_TASK, 'Edit tasks', [$viewTask]],
            [Abilities::DELETE_TASK, 'Delete tasks', [$viewTask]],
            [Abilities::MANAGE_TASK_STATUS, 'Manage task statuses', [$viewTask]],
            [Abilities::VIEW_OWN_TIME, 'View own time', []],
            [Abilities::VIEW_ALL_TIME, 'View all time', [$viewOwnTime]],
            [Abilities::EDIT_ALL_TIME, 'Edit all time', [$viewAllTime]],
            // Invoicing a task ends on the host invoice edit page, so the role
            // that may raise the invoice must also be allowed to open it.
            [Abilities::INVOICE_TASKS, 'Invoice tasks', [
                $viewAllTime,
                Abilities::HOST_CREATE_INVOICE,
                Abilities::HOST_EDIT_INVOICE,
            ]],
        ];

        foreach ($abilities as [$ability, $name, $dependsOn]) {
            Registry::registerAbility(Abilities::SLUG, [
                'ability' => $ability,
                'name' => $name,
                'depends_on' => $dependsOn,
            ]);
        }
    }
}
