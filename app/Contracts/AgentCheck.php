<?php

namespace App\Contracts;

interface AgentCheck
{
    /**
     * Run the agent check and return an array of findings.
     *
     * Each finding should be an associative array with keys:
     *   - check_type: string
     *   - severity: 'info' | 'warning' | 'critical'
     *   - product_id: int|null
     *   - location_id: int|null
     *   - title: string
     *   - description: string
     *   - dedup_hash: string (unique hash for deduplication)
     *
     * @return array<int, array<string, mixed>>
     */
    public function run(): array;
}
