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
    /** @var list<callable> $callbacks */
    private $callback;

    private array $callbacks = [];
    private readonly PipelineRuntimeInterface $decorated;

    public function __construct(
        callable $callback,
        ConsoleOutput $output,
        PipelineInterface&WalkableInterface $pipeline,
        State\StateOutput\Workflow $state,
        string $filename,
    )
    {
        $this->decorated = new PipelineConsoleRuntime($output, $pipeline, $state->withPipeline($filename));
        $this->callback = $callback;
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self
    {
        $this->callbacks[] = function () use ($extractor, $rejection, $state): void {
            $this->decorated->extract($extractor, $rejection, $state);
        };

        return $this;
    }

    public function transform(
        TransformerInterface $transformer,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self
    {
        $this->callbacks[] = function () use ($transformer, $rejection, $state): void {
            $this->decorated->transform($transformer, $rejection, $state);
        };

        return $this;
    }

    public function load(
        LoaderInterface $loader,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self
    {
        $this->callbacks[] = function () use ($loader, $rejection, $state): void {
            $this->decorated->load($loader, $rejection, $state);
        };

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        ($this->callback)($this->decorated);

        foreach ($this->callbacks as $callback) {
            $callback($this->callback);
        }

        $this->callbacks = [];

        return $this->decorated->run($interval);
    }
}
