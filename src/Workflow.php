<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Component\Runtime\Action\Action;
use Kiboko\Component\Runtime\Pipeline\Pipeline;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Workflow
{
    /** @var list<Action|Pipeline> */
    private array $jobs = [];
    private string $index = 'A';

    public function __construct(
        private readonly ConsoleOutput $output,
    ) {
    }

    public function withPipeline(string $label): Pipeline
    {
        return $this->jobs[] = new Pipeline($this->output, $this->index++, $label);
    }

    public function withAction(string $label): Action
    {
        return $this->jobs[] = new Action($this->output, $this->index++, $label);
    }

    public function update(): void
    {
        foreach ($this->jobs as $job) {
            $job->update();
        }
    }
}
