<?php

declare(strict_types=1);

namespace Impulse\Database;

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Provider\AbstractProvider;
use Impulse\Database\Contrats\DatabaseInterface;

final class DatabaseProvider extends AbstractProvider
{
    /**
     * @throws \JsonException
     */
    protected function registerServices(ImpulseContainer $container): void
    {
        $container->set(DatabaseInterface::class, fn () => new Database());
    }
}
