<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use Modules\AiAssistant\Rules\PublicHttpUrl;
use Modules\AiAssistant\Support\Net\BlockedUrlException;
use Modules\AiAssistant\Support\Net\PrivateNetworkGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrivateNetworkGuardTest extends TestCase
{
    #[DataProvider('blockedUrls')]
    public function test_it_blocks_private_and_reserved_targets(string $url): void
    {
        self::assertNotNull(PrivateNetworkGuard::blockedReason($url));
    }

    public static function blockedUrls(): array
    {
        return [['http://127.0.0.1'], ['http://10.0.0.1'], ['http://169.254.169.254'], ['http://[::1]'], ['http://[::ffff:10.0.0.1]'], ['ftp://1.1.1.1'], ['file:///etc/passwd']];
    }

    public function test_it_allows_public_addresses_and_unresolvable_hosts(): void
    {
        self::assertNull(PrivateNetworkGuard::blockedReason('https://1.1.1.1/api'));
        self::assertNull(PrivateNetworkGuard::blockedReason('https://surely-not-a-real-host.invalid/api'));
    }

    public function test_it_handles_cidr_boundaries_and_mapped_addresses(): void
    {
        self::assertTrue(PrivateNetworkGuard::ipInCidr('172.31.255.255', '172.16.0.0/12'));
        self::assertFalse(PrivateNetworkGuard::ipInCidr('172.32.0.0', '172.16.0.0/12'));
        self::assertTrue(PrivateNetworkGuard::ipIsBlocked('::ffff:192.168.0.1'));
        self::assertFalse(PrivateNetworkGuard::ipIsBlocked('1.1.1.1'));
    }

    public function test_assert_allowed_throws_for_a_blocked_target(): void
    {
        $this->expectException(BlockedUrlException::class);
        PrivateNetworkGuard::assertAllowed('http://169.254.169.254');
    }

    public function test_public_url_rule_rejects_private_targets_and_allows_public_ones(): void
    {
        $rule = new PublicHttpUrl;
        $errors = [];
        $rule->validate('base_url', 'http://127.0.0.1:11434', static function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        self::assertCount(1, $errors);
        $rule->validate('base_url', 'https://1.1.1.1/api', static function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        self::assertCount(1, $errors);
    }
}
