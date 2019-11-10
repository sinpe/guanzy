# Guanzy

[English](./README_en_US.md)

Guanzy（管子）是一个简单的应用在web上的框架，可以快速用来搭建web程序或者api程序，它基于ADR模式和PSR规范实现！正如其名，它只担当自己作为一个管子的角色。

## 安装

推荐你使用Composer来安装。

```sh
$ composer require long/guanzy
```

执行此命令后，它将安装框架文件和少量的依赖包。请在PHP7.2或者更高版本运行。

## 如何使用

比如：<你的应用文件夹>/public/index.php

```php
use Lib\Application; // 你的Application子类，对父类做必要的改写或填充

// 您的其他扩展代码

$classLoader = require_once __DIR__ . '/vendor/autoload.php';

// 您的其他扩展代码

$app = new Application();

$app->run();
```

## 其他相关

[Slim](https://github.com/slimphp/Slim/) 本框架的来源，它是一个微型PHP框架。

[FastRoute](https://github.com/nikic/FastRoute) 本框架路由的解析器。

[Psr](https://www.php-fig.org/) PSR。


## 推荐包

[Flysystem](https://flysystem.thephpleague.com/docs/) 文件系统

[TWIG](https://github.com/twigphp/Twig) 模板解析
