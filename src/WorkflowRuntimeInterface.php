<?php

declare(strict_types=1);

namespace Kiboko\Component\Runtime\Workflow;

use Kiboko\Contract\Satellite\RunnableInterface;
use Kiboko\Contract\Satellite\SchedulingInterface;

interface WorkflowRuntimeInterface extends SchedulingInterface, RunnableInterface {}
