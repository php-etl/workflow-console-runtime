<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Runtime\Action\Console as ActionConsoleRuntime;
use Kiboko\Component\State;
use Kiboko\Contract\Action\ExecutingActionInterface;
use Kiboko\Contract\Satellite\RunnableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ActionProxy implements RunnableInterface
{
    /** @var list<callable> */
    private array $queuedCalls = [];

    public function __construct(
        callable $factory,
        private readonly ConsoleOutput $output,
        private readonly ExecutingActionInterface $action,
        private readonly State\StateOutput\Workflow $state,
        private readonly string $filename,
    ) {
        $this->queuedCalls[] = static function (ActionConsoleRuntime $runtime) use ($factory): void {
            $factory($runtime);
        };
    }

    public function run(int $interval = 1000): int
    {
        $runtime = new ActionConsoleRuntime($this->output, $this->action, $this->state->withAction($this->filename));

        foreach ($this->queuedCalls as $queuedCall) {
            $queuedCall($runtime);
        }

        $this->queuedCalls = [];

        return $runtime->run($interval);
    }
}
