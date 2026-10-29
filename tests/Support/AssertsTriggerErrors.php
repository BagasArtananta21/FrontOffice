<?php

namespace Tests\Support;

use Closure;
use Illuminate\Database\QueryException;

trait AssertsTriggerErrors
{
    /**
     * Runs $statement and expects a SIGNAL SQLSTATE '45000' from a trigger
     * whose message contains $messageFragment.
     */
    protected function assertTriggerRejects(Closure $statement, string $messageFragment): void
    {
        try {
            $statement();
        } catch (QueryException $e) {
            $this->assertSame('45000', (string) $e->getCode(), 'Bukan error SIGNAL dari trigger: '.$e->getMessage());
            $this->assertStringContainsString($messageFragment, $e->getMessage());

            return;
        }

        $this->fail('Trigger seharusnya menolak pernyataan ini dengan SQLSTATE 45000.');
    }
}
