<?php
ini_set('display_errors', true);
error_reporting(E_ALL);

use Maestroprog\Container\Container;
use Maestroprog\Container\ContainerCompiler;

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/CacheInterface.php';
require_once __DIR__ . '/FileCache.php';
require_once __DIR__ . '/InMemoryCache.php';
require_once __DIR__ . '/CacheDecorator.php';
require_once __DIR__ . '/SampleContainer.php';
require_once __DIR__ . '/SampleOverrideContainer.php';
require_once __DIR__ . '/SampleService.php';

$container = Container::instance();
$container->register(new SampleOverrideContainer());
$container->register(new SampleContainer());

$compiler = new ContainerCompiler($container);
$compiler->compile(__DIR__ . '/container.php');

require_once __DIR__ . '/container.php';
$container = new CompiledContainer($container);

/** @var SampleService $service */
$service = $container->get(SampleService::class);

var_dump($service->getSample1());
var_dump($container->getSampleService()->getSample1());