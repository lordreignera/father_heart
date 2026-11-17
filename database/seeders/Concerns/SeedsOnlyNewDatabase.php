<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use LogicException;

trait SeedsOnlyNewDatabase
{
    protected function assertNewDatabase(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite'
            && $connection->getDatabaseName() !== config('admin.seed_database')) {
            throw new LogicException('Refusing to seed outside the configured new Laravel database.');
        }
    }
}
