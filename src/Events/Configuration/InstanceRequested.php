<?php

namespace Cognesy\Instructor\Events\Configuration;

use Cognesy\Instructor\Events\Event;

class InstanceRequested extends Event
{
    public function __construct(
        public string $name
    ) {
        parent::__construct();
    }

    public function __toString() : string {
        return $this->name;
    }
}
