<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Action\Action;
use Kiboko\Component\Runtime\Action\Console as ActionConsoleRuntime;
use Kiboko\Contract\Satellite\CodeInterface;
use Kiboko\Contract\Satellite\RunnableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ActionProxy implements RunnableInterface
{
    /** @var list<callable> */
    private array $queuedCalls = [];

    public function __construct(
        callable $factory,
        private readonly ConsoleOutput $output,
        private readonly Workflow $state,
        private readonly CodeInterface $code,
    ) {
        $this->queuedCalls[] = static function (ActionConsoleRuntime $runtime) use ($factory): void {
            $factory($runtime);
        };
    }

    public function run(int $interval = 1000): int
    {
        $state = $this->state->withAction((string) $this->code);
        $action = new Action();

        $runtime = new ActionConsoleRuntime($this->output, $action, $state);

        foreach ($this->queuedCalls as $queuedCall) {
            $queuedCall($runtime);
        }

        $this->queuedCalls = [];

        return $runtime->run($interval);
    }
}
