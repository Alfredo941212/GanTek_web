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

        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
    }
}
