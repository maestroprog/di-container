<?php
ini_set('display_errors', true);
error_reporting(E_ALL);

use Qwerty\Container\Container;
use Qwerty\Container\ContainerCompiler;

require_once '../vendor/autoload.php';

require_once 'CacheInterface.php';
require_once 'FileCache.php';
require_once 'InMemoryCache.php';
require_once 'SampleContainer.php';
require_once 'SampleOverrideContainer.php';
require_once 'SampleService.php';

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