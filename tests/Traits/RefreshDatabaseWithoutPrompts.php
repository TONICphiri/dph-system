<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase as BaseRefreshDatabase;

trait RefreshDatabaseWithoutPrompts
{
    use BaseRefreshDatabase {
        BaseRefreshDatabase::refreshTestDatabase as baseRefreshTestDatabase;
    }

    /**
     * Refresh the test database without interactive prompts.
     *
     * Delegates to the standard implementation: migrate once per process,
     * then wrap EVERY test in a transaction that is rolled back.
     *
     * The previous version ran `migrate:fresh` on every test WITHOUT
     * opening a transaction, so each Feature test COMMITTED its rows to
     * the shared in-memory SQLite database. Those rows leaked into later
     * test classes (e.g. discharge sync rows appearing inside SyncTest
     * counts) and the repeated migrate:fresh corrupted the shared PDO
     * state ("no such table" errors). Standard behavior fixes both.
     */
    protected function refreshTestDatabase(): void
    {
        if (!$this->shouldRefreshTestDatabase()) {
            return;
        }

        $this->baseRefreshTestDatabase();
    }

    /**
     * Determine if the test database should be refreshed.
     */
    protected function shouldRefreshTestDatabase(): bool
    {
        return true;
    }
}