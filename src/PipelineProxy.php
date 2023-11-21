<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Kiboko\Component\Runtime\Pipeline\MemoryState;
use Kiboko\Component\Runtime\Pipeline\PipelineRuntimeInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RejectionInterface;
use Kiboko\Contract\Pipeline\StateInterface;
use Kiboko\Contract\Pipeline\StepCodeInterface;
use Kiboko\Contract\Pipeline\StepRejectionInterface;
use Kiboko\Contract\Pipeline\StepStateInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Kiboko\Contract\Satellite\CodeInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class PipelineProxy implements PipelineRuntimeInterface
{
    /** @var list<callable> */
    private array $queuedCalls = [];

    public function __construct(
        callable $factory,
        private readonly ConsoleOutput $output,
        private readonly PipelineRunnerInterface $pipelineRunner,
        private readonly Workflow $state,
        private readonly CodeInterface $code,
    ) {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($factory): void {
            $factory($runtime);
        };
    }

    public function extract(
        StepCodeInterface $step,
        ExtractorInterface $extractor,
        StepRejectionInterface $rejection,
        StepStateInterface $state,
    ): self {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($step, $extractor, $rejection, $state): void {
            $runtime->extract($step, $extractor, $rejection, $state);
        };

        return $this;
    }

    public function transform(
        StepCodeInterface $step,
        TransformerInterface $transformer,
        StepRejectionInterface $rejection,
        StepStateInterface $state,
    ): self {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($step, $transformer, $rejection, $state): void {
            $runtime->transform($step, $transformer, $rejection, $state);
        };

        return $this;
    }

    public function load(
        StepCodeInterface $step,
        LoaderInterface $loader,
        StepRejectionInterface $rejection,
        StepStateInterface $state,
    ): self {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($step, $loader, $rejection, $state): void {
            $runtime->load($step, $loader, $rejection, $state);
        };

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $state = $this->state->withPipeline((string) $this->code);
        $pipeline = new Pipeline($this->pipelineRunner, new MemoryState());

        $runtime = new PipelineConsoleRuntime($this->output, $pipeline, $state);

        foreach ($this->queuedCalls as $queuedCall) {
            $queuedCall($runtime);
        }

        $this->queuedCalls = [];

        return $runtime->run($interval);
    }
}
