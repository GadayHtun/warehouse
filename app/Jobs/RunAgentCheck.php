<?php

namespace App\Jobs;

use App\Services\AgentOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAgentCheck implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param string $checkClass The fully-qualified class name of the agent check
     */
    public function __construct(
        private string $checkClass,
    ) {}

    public function handle(AgentOrchestrator $orchestrator): void
    {
        $orchestrator->runCheckByName($this->checkClass);
    }
}
