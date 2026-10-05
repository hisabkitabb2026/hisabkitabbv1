<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Drivers;

use InvalidArgumentException;
use InvoiceShelf\Modules\Ai\Contracts\AiDriver;
use InvoiceShelf\Modules\Registry;

final class AiDriverFactory
{
    /** @param array<string, mixed> $config */
    public static function make(string $driver, string $apiKey, array $config = []): AiDriver
    {
        $meta = Registry::driverMeta('ai', $driver);
        $class = $meta['class'] ?? null;
        if (! is_string($class) || ! is_a($class, AiDriver::class, true)) {
            throw new InvalidArgumentException("Unknown AI driver: {$driver}");
        }

        return new $class($apiKey, $config);
    }

    /** @return list<string> */
    public static function availableDrivers(): array
    {
        return array_keys(Registry::allDrivers('ai'));
    }
}
