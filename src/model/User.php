<?php

namespace Mapper\model;

class User {
  private $username;
  private $lastLogin;
  private $group;

  function __construct(string $username, Group $group) {
    $this->username = $username;
    $this->group = $group;
    $this->lastLogin = time();
  }

  function getUsername(): string {
    return $this->username;
  }

  function getLastLogin(): int {
    return $this->lastLogin;
  }

  function getGroup(): Group {
    return $this->group;
  }
}
