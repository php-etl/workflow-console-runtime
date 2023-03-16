<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Action\Action;
use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Kiboko\Component\Runtime\Action\Console as ActionConsoleRuntime;
use Kiboko\Component\State;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RunnableInterface as PipelineRunnableInterface;
use Kiboko\Contract\Action\RunnableInterface as ActionRunnableInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Console implements WorkflowRuntimeInterface
{
    private readonly State\StateOutput\Workflow $state;

    /** @var list<PipelineRunnableInterface|ActionRunnableInterface> */
    private array $jobs = [];

    public function __construct(
        private readonly ConsoleOutput $output,
        private readonly PipelineRunnerInterface $pipelineRunner,
    ) {
        $this->state = new State\StateOutput\Workflow($output);
    }

    public function loadPipeline(string $filename): PipelineConsoleRuntime
    {
        $factory = require $filename;

        $pipeline = new Pipeline($this->pipelineRunner);

        return $factory(new PipelineConsoleRuntime($this->output, $pipeline, $this->state->withPipeline(basename($filename))));
    }

    public function loadAction(string $filename): ActionConsoleRuntime
    {
        $factory = require $filename;

        $action = new Action();

        return $factory(new ActionConsoleRuntime($this->output, $action, $this->state->withAction(basename($filename))));
    }

    public function job(PipelineRunnableInterface|ActionRunnableInterface $job): self
    {
        $this->jobs[] = $job;

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $count = 0;
        foreach ($this->jobs as $job) {
            $count = $job->run($interval);
        }

        return $count;
    }
}
