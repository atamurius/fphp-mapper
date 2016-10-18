<?php

namespace Mapper\model;

class Group {
  private $name;

  function __construct(string $name) {
    $this->name = $name;
  }

  function getName(): string {
    return $this->name;
  }
}
