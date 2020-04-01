# git-webhooks

![Build Status](https://github.com/sunkan/git-webhooks/workflows/CI/badge.svg?branch=sunkan&event=push)

normalise webhook events for github, gitlab and bitbucket

Installation
------------

```bash
composer require davidbadura/git-webhooks
```

Example
-------

```php
use DavidBadura\GitWebhooks\EventFactory;

$request = Psr17Factory::createFromGlobals();
$factory = EventFactory::createDefault();

if ($event = $factory->create($request)) {
    // ...
}
```