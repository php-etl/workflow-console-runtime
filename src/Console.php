<?php declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Kiboko\Component\State;
use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Console implements WorkflowRuntimeInterface
{
    private State\StateOutput\Workflow $state;

    /** @var list<RunnableInterface> */
    private array $jobs = [];

    public function __construct(
        private ConsoleOutput $output,
        private PipelineRunnerInterface $pipelineRunner,
    ) {
        $this->state = new State\StateOutput\Workflow($output);
    }

    public function loadPipeline(string $filename): PipelineConsoleRuntime
    {
        $factory = require $filename;

        $pipeline = new Pipeline($this->pipelineRunner);
        $this->job($pipeline);

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
