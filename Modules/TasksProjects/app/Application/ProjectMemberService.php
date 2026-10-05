<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\TasksProjects\Models\ProjectMember;

/**
 * Project membership.
 *
 * Detaching a member leaves their time entries alone: entries keep `user_id`
 * and the UI renders a removed member, so history survives a leaver.
 */
final class ProjectMemberService
{
    public function __construct(private readonly ProjectService $projects) {}

    /** @return Collection<int, ProjectMember> */
    public function listFor(int $companyId, int $projectId): Collection
    {
        $this->projects->findForCompany($companyId, $projectId);

        return ProjectMember::query()
            ->forCompany($companyId)
            ->where('project_id', $projectId)
            ->orderBy('user_id')
            ->get();
    }

    /** Attach a member, or update the rate of one already attached. */
    public function attach(int $companyId, int $projectId, int $userId, ?int $rate = null): ProjectMember
    {
        $this->projects->findForCompany($companyId, $projectId);

        $member = ProjectMember::query()
            ->forCompany($companyId)
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();

        if ($member === null) {
            return ProjectMember::query()->create([
                'company_id' => $companyId,
                'project_id' => $projectId,
                'user_id' => $userId,
                'rate' => $rate,
            ]);
        }

        $member->rate = $rate;
        $member->save();

        return $member;
    }

    public function detach(int $companyId, int $projectId, int $userId): void
    {
        $member = ProjectMember::query()
            ->forCompany($companyId)
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();

        if ($member === null) {
            throw (new ModelNotFoundException)->setModel(ProjectMember::class, [$userId]);
        }

        $member->delete();
    }
}
