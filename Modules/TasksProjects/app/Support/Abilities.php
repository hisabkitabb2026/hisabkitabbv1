<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Support;

/**
 * Ability names the Tasks and Projects module contributes to the host catalogue.
 *
 * The constants hold the bare, un-namespaced names: `Registry::registerAbility()`
 * namespaces every module ability as `{slug}:{ability}` at registration time and
 * rejects a name that already carries a colon. Build the stored id with
 * `Registry::abilityId(Abilities::SLUG, Abilities::VIEW_PROJECT)` wherever the
 * namespaced form is needed, such as a frontend route's `meta.ability`.
 *
 * See specs/tasks-projects.md "Authorization" for the dependency table.
 */
final class Abilities
{
    public const SLUG = 'tasks-projects';

    public const VIEW_PROJECT = 'view-project';

    public const CREATE_PROJECT = 'create-project';

    public const EDIT_PROJECT = 'edit-project';

    public const DELETE_PROJECT = 'delete-project';

    public const VIEW_TASK = 'view-task';

    public const CREATE_TASK = 'create-task';

    public const EDIT_TASK = 'edit-task';

    public const DELETE_TASK = 'delete-task';

    public const MANAGE_TASK_STATUS = 'manage-task-status';

    public const VIEW_OWN_TIME = 'view-own-time';

    public const VIEW_ALL_TIME = 'view-all-time';

    public const EDIT_ALL_TIME = 'edit-all-time';

    public const INVOICE_TASKS = 'invoice-tasks';

    /** Host abilities the module's own abilities depend on. */
    public const HOST_VIEW_CUSTOMER = 'view-customer';

    public const HOST_CREATE_INVOICE = 'create-invoice';

    public const HOST_EDIT_INVOICE = 'edit-invoice';
}
