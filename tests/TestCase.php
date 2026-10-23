<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \LogicException('Las pruebas de GanTek solo pueden ejecutarse en SQLite :memory:.');
        }

        $opciones = ['--database' => 'sqlite', '--force' => true, '--no-interaction' => true];
        if ($this->migrationPaths() !== []) {
            $opciones['--path'] = $this->migrationPaths();
        }
        $this->artisan('migrate', $opciones)->assertExitCode(0);
    }

    /** @return list<string> */
    protected function migrationPaths(): array
    {
        return [];
    }
}
