<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Kiboko\Component\Runtime\Pipeline\PipelineRuntimeInterface;
use Kiboko\Component\State;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RejectionInterface;
use Kiboko\Contract\Pipeline\StateInterface;
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
        private readonly State\StateOutput\Workflow $state,
        private readonly CodeInterface $code,
    ) {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($factory): void {
            $factory($runtime);
        };
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($extractor, $rejection, $state): void {
            $runtime->extract($extractor, $rejection, $state);
        };

        return $this;
    }

    public function transform(
        TransformerInterface $transformer,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($transformer, $rejection, $state): void {
            $runtime->transform($transformer, $rejection, $state);
        };

        return $this;
    }

    public function load(
        LoaderInterface $loader,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->queuedCalls[] = static function (PipelineConsoleRuntime $runtime) use ($loader, $rejection, $state): void {
            $runtime->load($loader, $rejection, $state);
        };

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $state = $this->state->withPipeline((string) $this->code);
        $pipeline = new Pipeline($this->pipelineRunner, new State\MemoryState());

        $runtime = new PipelineConsoleRuntime($this->output, $pipeline, $state);

        foreach ($this->queuedCalls as $queuedCall) {
            $queuedCall($runtime);
        }

        $this->queuedCalls = [];

        return $runtime->run($interval);
    }
}
