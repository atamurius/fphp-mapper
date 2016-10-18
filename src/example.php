<?php

namespace Mapper;

require __DIR__.'/../vendor/autoload.php';

// example data

use Mapper\model\{User, Group, Post};

$users = new Group('users');
$admins = new Group('administrators');

$alice = new User('alice', $users);
$bob = new User('bob', $users);
$carl = new User('carl', $admins);

$data = [
  new Post('I like tea', 'Tea is very good', $alice),
  new Post('What is going on?', 'I don`t understant', $bob),
  new Post('Cupcakes', 'Also I like cupcakes', $alice),
  new Post('All is ok', 'Keep calm and write posts', $carl)
];

//print_r($data);

// 1.

$groupped = [];
foreach($data as $post) {
  $key = $post->getAuthor()->getUsername();
  $groupped[$key] = $groupped[$key] ?? [ $post->getAuthor(), [] ];
  $groupped[$key][1][] = $post;
}

$mapped = [];
foreach($groupped as list($user,$posts)) {
  $mappedPosts = [];
  usort($posts, function($a,$b) {
    return $a->getPosted() <=> $b->getPosted();
  });
  foreach ($posts as $post) {
    $mappedPosts[] = [
      'subject' => $post->getSubject(),
      'posted' => date('H:i', $post->getPosted())
    ];
  }
  $mapped[] = [
    'author' => [
      'username' => $user->getUsername(),
      'group' => $user->getGroup()->getName(),
    ],
    'posts' => $mappedPosts
  ];
}

//print json_encode($mapped, JSON_PRETTY_PRINT).PHP_EOL;

// 2.

function group(array $xs, callable $key, callable $arrayKey = null): array {
  $groupped = [];
  foreach ($xs as $x) {
    $k = $key($x);
    $ak = $arrayKey ? $arrayKey($k) : $k;
    $groupped[$ak] = $groupped[$ak] ?? [ $k, [] ];
    $groupped[$ak][1][] = $x;
  }
  return array_values($groupped);
}

$groupped = group(
  $data,
  function($post) { return $post->getAuthor(); },
  function($user) { return $user->getUsername(); });

function method(string $name) {
  return function($obj) use ($name) {
    return $obj->{$name}();
  };
}

$groupped = group($data, method('getAuthor'), method('getUsername'));

function map(array $xs, callable $f) {
  $mapped = [];
  foreach ($xs as $x) {
    $mapped[] = $f($x);
  }
  return $mapped;
}

$mapped = map($groupped, function($pair) {
  list ($user, $posts) = $pair;
  return [
    'author' => [
      'username' => $user->getUsername(),
      'group' => $user->getGroup()->getName(),
    ],
    'posts' => map($posts, function(Post $post) {
      return [
        'subject' => $post->getSubject(),
        'posted' => date('H:i', $post->getPosted())
      ];
    })
  ];
});

#print json_encode($mapped, JSON_PRETTY_PRINT).PHP_EOL;

// 3.

$postsByAuthor = group($data, method('getAuthor'), method('getUsername'));

function assign($name, $value, $assoc = null) {
  return array_merge($assoc ?? [], [ $name => $value ]);
}

$mapped = assign('subject', $posts[0]->getSubject());
$mapped = assign('body', $posts[0]->getBody(), $mapped);

function compose(callable ...$fs) {
  return function($value) use ($fs) {
    foreach ($fs as $f) {
      $value = $f($value);
    }
    return $value;
  };
}

function partial($f) {
  return function(...$first) use ($f) {
    return function(...$rest) use ($f, $first) {
      return call_user_func_array($f, array_merge($first,$rest));
    };
  };
}

$assign = partial('Mapper\\assign');

$build = compose(
  $assign('subject', $posts[0]->getSubject()),
  $assign('body', $posts[0]->getBody()));

$mapped = $build([]);

function transform(array $fs, $source) {
  $value = null;
  foreach ($fs as $f) {
    $value = $f($source)($value);
  }
  return $value;
}

$assignAs = partial($assign);

$mapped = transform([
  compose(method('getSubject'),$assignAs('subject')),
  compose(method('getBody'),$assignAs('body'))
], $posts[0]);

$transformation = partial('Mapper\\transform');

$mapped = map($data, $transformation([
  compose(method('getSubject'),$assignAs('subject')),
  compose(method('getBody'),$assignAs('body'))
]));

$map = partial(function($f,$xs) { return map($xs,$f); });
$group = partial(function($key,$hash,$xs) { return group($xs,$key,$hash); });
$at = partial(function($i,$xs) { return $xs[$i]; });

$authorMapper = $transformation([
  compose(method('getUsername'),$assignAs('username')),
  compose(method('getGroup'),method('getName'),$assignAs('group'))
]);

$postMapper = $transformation([
  compose(method('getSubject'), $assignAs('subject')),
  compose(method('getPosted'), partial('date')('H:i'), $assignAs('posted'))
]);

$mapper = compose(
  $group(method('getAuthor'),method('getUsername')),
  $map($transformation([
    compose($at(0),$authorMapper,$assignAs('author')),
    compose($at(1),$map($postMapper),$assignAs('posts'))
  ]))
);

$mapped = $mapper($data);

print json_encode($mapped, JSON_PRETTY_PRINT).PHP_EOL;
