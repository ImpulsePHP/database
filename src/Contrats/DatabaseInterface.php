<?php

declare(strict_types=1);

namespace Impulse\Database\Contrats;

use Cycle\Database\DatabaseInterface as CycleDatabaseInterface;
use Cycle\ORM\ORMInterface;

interface DatabaseInterface
{
    public function getDatabase(?string $name = null): CycleDatabaseInterface;
    public function getORM(): ORMInterface;
    public function testConnection(?string $database = null): bool;
    public function getConfig(): array;
}
