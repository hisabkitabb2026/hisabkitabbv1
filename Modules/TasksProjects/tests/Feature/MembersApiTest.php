<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Tests\TestCase;

final class MembersApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    public function test_it_returns_the_host_member_list_of_the_header_company(): void
    {
        $this->companyData
            ->withMember(self::COMPANY, 7, 'Ada Lovelace')
            ->withMember(self::COMPANY, 8, 'Grace Hopper')
            ->withMember(self::OTHER_COMPANY, 9, 'Someone Else');

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/members');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', 7);
        $response->assertJsonPath('data.0.name', 'Ada Lovelace');
        $response->assertJsonPath('data.1.name', 'Grace Hopper');
    }

    public function test_a_company_without_members_returns_an_empty_list(): void
    {
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/members')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_the_picker_needs_the_project_view_ability(): void
    {
        $this->authorization->deny(Authorizes::id(Abilities::VIEW_PROJECT));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/members')
            ->assertForbidden();
    }
}
