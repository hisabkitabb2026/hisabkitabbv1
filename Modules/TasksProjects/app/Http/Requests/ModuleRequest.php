<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for the module's form requests.
 *
 * Authorization is deliberately not done here: every action checks its ability
 * against the host through Support\Authorizes, with the company taken from the
 * `company` header, so a request object only ever describes the shape of the
 * input.
 */
abstract class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
