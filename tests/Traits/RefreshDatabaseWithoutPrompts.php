<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase as BaseRefreshDatabase;

trait RefreshDatabaseWithoutPrompts
{
    use BaseRefreshDatabase;

    /**
     * Refresh the test database without interactive prompts.
     */
    protected function refreshTestDatabase(): void
    {
        if (!$this->shouldRefreshTestDatabase()) {
            return;
        }

        // Use the parent implementation which properly sets up SQLite
        $this->artisan("migrate:fresh", [
            "--force" => true,
        ]);
    }

    /**
     * Determine if the test database should be refreshed.
     */
    protected function shouldRefreshTestDatabase(): bool
    {
        return true;
    }
}