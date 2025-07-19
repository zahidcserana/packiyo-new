<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase as LaravelLazilyRefreshDatabase;

/**
 * Migrates between tests, uses transactions.
 */
trait LazilyRefreshDatabase
{
    use LaravelLazilyRefreshDatabase {
        refreshDatabase as parentRefreshDatabase;
    }
    use TruncatesSafely;

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->assertTestDatabase();
        $this->parentRefreshDatabase();
    }
}
