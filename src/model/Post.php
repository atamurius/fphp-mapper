<?php

namespace Mapper\model;

class Post {
  private $subject;
  private $posted;
  private $body;
  private $author;

  function __construct(string $subject, string $body, User $author) {
    $this->subject = $subject;
    $this->body = $body;
    $this->posted = time();
    $this->author = $author;
  }

  function getSubject(): string {
    return $this->subject;
  }

  function getBody(): string {
    return $this->body;
  }

  function getPosted(): int {
    return $this->posted;
  }

  function getAuthor(): User {
    return $this->author;
  }
}
