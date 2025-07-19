<?php

// namespace Illuminate\Foundation\Testing;

/**
 * Checks that we're only wiping test databases.
 */
trait TruncatesSafely
{
    /**
     * Throws an exception if the db is not for testing.
     */
    protected function assertTestDatabase(): void
    {
        $database = $this->app->make('db');

        if (!str_starts_with($database->getDatabaseName(), 'test_')) {
            throw new RuntimeException('Preventing the wipe of a db not meant for testing.');
        }
    }

    /**
     * Throws an exception if the db is not for development.
     */
    protected function assertDevDatabase(): void
    {
        $database = $this->app->make('db');

        if (!str_starts_with($database->getDatabaseName(), 'dev_')) {
            throw new RuntimeException('Preventing the wipe of a db not meant for development.');
        }
    }
}
