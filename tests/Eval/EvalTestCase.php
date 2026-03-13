<?php

namespace Tests\Eval;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Laravel\Ai\agent;

abstract class EvalTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! config('ai.providers.anthropic.key')) {
            $this->markTestSkipped('ANTHROPIC_API_KEY is not configured — skipping eval tests.');
        }
    }

    /**
     * Ask a judge agent to score an agent response against a given criteria.
     *
     * Returns a score from 1–5 and a reasoning string.
     * A score >= 3 is considered passing.
     *
     * @return array{score: int, reasoning: string, passed: bool}
     */
    protected function judge(string $agentResponse, string $criteria): array
    {
        $result = agent(
            instructions: 'You are a strict AI quality evaluator for an e-commerce support chatbot. '
                .'Your job is to score responses objectively and concisely.',
            schema: fn (JsonSchema $schema) => [
                'score' => $schema->integer()->min(1)->max(5)->required(),
                'reasoning' => $schema->string()->required(),
                'passed' => $schema->boolean()->required(),
            ],
        )->prompt(
            implode("\n\n", [
                "Evaluation criteria: {$criteria}",
                "Response to evaluate:\n{$agentResponse}",
                'Score 1–5 (1 = completely fails criteria, 3 = acceptable, 5 = excellent). '
                    .'Set passed = true if score >= 3.',
            ]),
            provider: 'anthropic',
        );

        return [
            'score' => $result['score'],
            'reasoning' => $result['reasoning'],
            'passed' => $result['passed'],
        ];
    }

    /**
     * Assert that an agent response passes the judge's evaluation.
     */
    protected function assertPassesEval(string $agentResponse, string $criteria): void
    {
        $result = $this->judge($agentResponse, $criteria);

        $this->assertTrue(
            $result['passed'],
            sprintf(
                "Eval failed (score %d/5): %s\n\nAgent response:\n%s",
                $result['score'],
                $result['reasoning'],
                $agentResponse,
            ),
        );
    }
}
