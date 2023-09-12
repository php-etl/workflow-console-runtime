<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Contract\Pipeline\SchedulingInterface;
use Kiboko\Contract\Satellite\RunnableInterface;

interface WorkflowRuntimeInterface extends SchedulingInterface, RunnableInterface {}
