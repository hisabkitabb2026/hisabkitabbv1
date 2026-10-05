<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

use RuntimeException;

/**
 * Base class for every domain rule the module refuses to break.
 *
 * Besides the message, a refusal may carry a little structured context: the
 * ids that made the request impossible, for instance, so the UI can name them
 * instead of asking the reader to parse the sentence. DomainExceptionRenderer
 * merges that context into the JSON body next to `message` and `error`.
 */
abstract class TasksProjectsException extends RuntimeException
{
    /** @var array<string, mixed> */
    private array $context = [];

    /**
     * @param  array<string, mixed>  $context
     * @return $this
     */
    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }
}
