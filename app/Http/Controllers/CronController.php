<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Background work for hosts that cannot run a system scheduler. An external
 * cron calls these endpoints with the shared secret; servers with cron should
 * use `php artisan schedule:run` instead.
 */
class CronController extends Controller
{
    public function reminders(Request $request): JsonResponse
    {
        $this->authorizeCron($request);

        $exitCode = Artisan::call('reminders:send');

        return $this->result([
            'reminders:send' => [
                'exit_code' => $exitCode,
                'output' => trim(Artisan::output()),
            ],
        ], $exitCode === 0);
    }

    public function maintenance(Request $request): JsonResponse
    {
        $this->authorizeCron($request);

        $results = [];
        $ok = true;

        foreach ([
            'backup:run' => ['--scheduled' => true],
            'patients:separate-adults' => [],
        ] as $command => $arguments) {
            $exitCode = Artisan::call($command, $arguments);

            $results[$command] = [
                'exit_code' => $exitCode,
                'output' => trim(Artisan::output()),
            ];

            $ok = $ok && $exitCode === 0;
        }

        return $this->result($results, $ok);
    }

    private function authorizeCron(Request $request): void
    {
        $secret = (string) config('health_passport.cron_secret');

        abort_unless(
            $secret !== '' && hash_equals($secret, (string) $request->bearerToken()),
            401,
            'The cron secret is missing or incorrect.',
        );
    }

    /**
     * @param  array<string, array{exit_code: int, output: string}>  $results
     */
    private function result(array $results, bool $ok): JsonResponse
    {
        return response()->json([
            'ok' => $ok,
            'results' => $results,
        ], $ok ? 200 : 500);
    }
}
