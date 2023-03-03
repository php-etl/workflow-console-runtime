<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Kiboko\Component\State;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Console implements WorkflowRuntimeInterface
{
    private readonly State\StateOutput\Workflow $state;

    /** @var list<RunnableInterface> */
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

    public function loadAction(string $filename): PipelineConsoleRuntime
    {
        $factory = require $filename;

        $pipeline = new Pipeline($this->pipelineRunner);

        return $factory(new PipelineConsoleRuntime($this->output, $pipeline, $this->state->withPipeline(basename($filename))));
    }

    public function job(RunnableInterface $job): self
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
