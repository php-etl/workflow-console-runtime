<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Runtime\Pipeline\PipelineRuntimeInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Satellite\CodeInterface;
use Kiboko\Contract\Satellite\RunnableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Console implements WorkflowRuntimeInterface
{
    private readonly Workflow $state;

    /** @var list<RunnableInterface> */
    private array $jobs = [];

    public function __construct(
        private readonly ConsoleOutput $output,
        private readonly PipelineRunnerInterface $pipelineRunner,
    ) {
        $this->state = new Workflow($output);
    }

    public function loadPipeline(CodeInterface $job, string $filename): PipelineRuntimeInterface
    {
        $factory = require $filename;

        return new PipelineProxy($factory, $this->output, $this->pipelineRunner, $this->state, $job);
    }

    public function loadAction(CodeInterface $job, string $filename): RunnableInterface
    {
        $factory = require $filename;

        return new ActionProxy($factory, $this->output, $this->state, $job);
    }

    public function job(CodeInterface $job, RunnableInterface $runnable): self
    {
        $this->jobs[(string) $job] = [$job, $runnable];

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $count = 0;
        foreach ($this->jobs as [$job, $runnable]) {
            $count = $runnable->run($interval);
        }

        return $count;
    }
}
