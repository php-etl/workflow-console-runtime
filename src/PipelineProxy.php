<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Kiboko\Component\Runtime\Pipeline\PipelineRuntimeInterface;
use Kiboko\Component\State;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\PipelineInterface;
use Kiboko\Contract\Pipeline\RejectionInterface;
use Kiboko\Contract\Pipeline\StateInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Kiboko\Contract\Pipeline\WalkableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class PipelineProxy implements PipelineRuntimeInterface
{
    /** @var list<callable> $queuedCalls */
    private array $queuedCalls = [];

    public function __construct(
        callable $factory,
        private readonly ConsoleOutput $output,
        private readonly PipelineInterface&WalkableInterface $pipeline,
        private readonly State\StateOutput\Workflow $state,
        private readonly string $filename,
    ) {
        $this->queuedCalls [] = static function (PipelineConsoleRuntime $runtime) {
            $factory = null;
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
        $runtime = new PipelineConsoleRuntime($this->output, $this->pipeline, $this->state->withPipeline($this->filename));

        foreach ($this->queuedCalls as $queuedCall) {
            $queuedCall($this);
        }

        $this->queuedCalls = [];

        return $runtime->run($interval);
    }
}
