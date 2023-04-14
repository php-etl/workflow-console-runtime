<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Contract\Job\RunnableInterface;
use Kiboko\Contract\Job\SchedulingInterface;

interface WorkflowRuntimeInterface extends SchedulingInterface, RunnableInterface
{
}
