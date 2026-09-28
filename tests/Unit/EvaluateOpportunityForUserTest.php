<?php

namespace Tests\Unit;

use App\Jobs\EvaluateOpportunityForUser;
use Tests\TestCase;

class EvaluateOpportunityForUserTest extends TestCase
{
    public function test_transient_failures_use_progressive_backoff(): void
    {
        $job = new EvaluateOpportunityForUser(10, 20);

        $this->assertSame(5, $job->tries);
        $this->assertSame([60, 180, 600, 1800], $job->backoff());
    }
}
