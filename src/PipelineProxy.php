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
    private $factory;

    public function __construct(
        callable $factory,
        private readonly ConsoleOutput $output,
        private readonly PipelineInterface&WalkableInterface $pipeline,
        private readonly State\StateOutput\Workflow $state,
        private readonly string $filename,
    ) {
        $this->factory = $factory;
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->pipeline->extract($extractor, $rejection, $state);

        return $this;
    }

    public function transform(
        TransformerInterface $transformer,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->pipeline->transform($transformer, $rejection, $state);

        return $this;
    }

    public function load(
        LoaderInterface $loader,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->pipeline->load($loader, $rejection, $state);

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $runtime = new PipelineConsoleRuntime($this->output, $this->pipeline, $this->state->withPipeline($this->filename));

        $pipeline = ($this->factory)($runtime);

        return $pipeline->run($interval);
    }
}
