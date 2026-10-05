<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** One invoice covers one customer in one currency. */
final class MixedBillingSelection extends TasksProjectsException
{
    /**
     * The customers the selection spans, named in the body as well as in the
     * message, so the screen that offered the selection can say which two
     * clients it just mixed.
     *
     * @param  list<int|string>  $customerIds
     */
    public static function customers(array $customerIds): self
    {
        return (new self('The selected time entries belong to more than one customer: '.implode(', ', $customerIds).'.'))
            ->withContext(['customer_ids' => array_values(array_map(intval(...), $customerIds))]);
    }

    /** @param list<int|string> $currencyIds */
    public static function currencies(array $currencyIds): self
    {
        return new self('The selected time entries use more than one currency: '.implode(', ', $currencyIds).'.');
    }

    public static function empty(): self
    {
        return new self('No time entries were selected.');
    }
}
