<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Ordering a list the services already hold in memory.
 *
 * The sort happens in PHP rather than in the query on purpose. A task's
 * priority ranks LOW, NORMAL, HIGH, URGENT, which is not its alphabetical
 * order, and the three supported databases disagree about where a NULL lands:
 * MySQL and SQLite put it first on an ascending sort, PostgreSQL puts it last.
 * Sorting here gives one answer everywhere without a CASE expression or a
 * `NULLS LAST` clause that SQLite would refuse. It costs nothing extra,
 * because every list endpoint already loads the whole collection and cuts the
 * page from it in `Controller::paginate`.
 *
 * Two rules hold whatever the caller asked for: a row with no value sorts last
 * in both directions, so an undated task never leads the page, and ties break
 * on the id in the direction of the sort, so the order is total and a page
 * boundary never drops or repeats a row.
 */
trait SortsLists
{
    /**
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $items
     * @param  callable(TModel): (int|string|null)  $value  the comparable value of one row
     * @param  string  $order  `asc` or `desc`; anything else reads as `asc`
     * @return Collection<int, TModel>
     */
    protected function sortList(Collection $items, callable $value, string $order): Collection
    {
        $descending = $order === 'desc';

        return $items
            ->sort(function (Model $left, Model $right) use ($value, $descending): int {
                /** @var callable(Model): (int|string|null) $value */
                $first = $value($left);
                $second = $value($right);

                if ($first === null || $second === null) {
                    return $first === $second
                        ? $this->compareIds($left, $right, $descending)
                        : ($first === null ? 1 : -1);
                }

                $comparison = is_int($first) && is_int($second)
                    ? $first <=> $second
                    : $this->compareText((string) $first, (string) $second);

                if ($comparison === 0) {
                    return $this->compareIds($left, $right, $descending);
                }

                return $descending ? -$comparison : $comparison;
            })
            ->values();
    }

    /**
     * The key and the direction to sort by, given what the caller asked for.
     *
     * A caller who names no column gets the list's own opening order, which is
     * newest first for projects and by number for tasks. A caller who does
     * name one gets it ascending unless they say otherwise, because that is
     * the direction "sort by name" means to the person asking. The form
     * request has already rejected an unknown key, so the fallback here is
     * only ever reached by an internal caller.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, callable>  $supported  sort key => value reader
     * @return array{0: string, 1: string}
     */
    protected function sortFor(array $filters, array $supported, string $defaultKey, string $defaultOrder): array
    {
        $key = $filters['sort_by'] ?? null;
        $order = $filters['sort_order'] ?? null;

        if (! is_string($key) || ! isset($supported[$key])) {
            return [$defaultKey, $order === 'asc' || $order === 'desc' ? $order : $defaultOrder];
        }

        return [$key, $order === 'desc' ? 'desc' : 'asc'];
    }

    /**
     * Names read the way a person reads them, so "apple" sits beside "Apple"
     * rather than in a separate uppercase block the way a byte comparison
     * would put it. Equal-but-for-case values fall through to the id.
     */
    private function compareText(string $first, string $second): int
    {
        return strcasecmp($first, $second) <=> 0;
    }

    private function compareIds(Model $left, Model $right, bool $descending): int
    {
        $comparison = (int) $left->getKey() <=> (int) $right->getKey();

        return $descending ? -$comparison : $comparison;
    }
}
