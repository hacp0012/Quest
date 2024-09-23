<?php

namespace Hacp0012\Quest\core;

use Exception;

class Obstacle extends Exception
{
  function __construct(string $message, ?string $file = null, ?int $line = null)
  {
    parent::__construct(message: $message);

    if ($line) $this->line = $line;
    if ($file) $this->file = $file;
  }
}
