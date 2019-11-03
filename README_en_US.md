# Guanzy

[中文](./README.md)

Guanzy is a PHP micro framework that helps you quickly write simple yet powerful web applications and APIs. It is created with ADR.

## Installation

It's recommended that you use Composer to install Guanzy.

```sh
$ composer require long/guanzy
```

This will install Guanzy and all required dependencies. It requires PHP 7.2 or newer.

## How to use

For example, \<your application folder\>/public/index.php

```php
use Lib\Application; // Your Application class，it extends or overrides something.

// Your other codes

$classLoader = require_once __DIR__ . '/vendor/autoload.php';

// Your other codes

$app = new Application();

$app->run();
```

## Others

[Slim](https://github.com/slimphp/Slim/): The source for Guanzy, It is a micro PHP framework.

[FastRoute](https://github.com/nikic/FastRoute): Fast request router for PHP.
