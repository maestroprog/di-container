<?php
ini_set('display_errors', true);
error_reporting(E_ALL);

use Maestroprog\Container\Container;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/CacheInterface.php';
require_once __DIR__ . '/FileCache.php';
require_once __DIR__ . '/InMemoryCache.php';
require_once __DIR__ . '/CacheDecorator.php';
require_once __DIR__ . '/SampleContainer.php';
require_once __DIR__ . '/SampleOverrideContainer.php';
require_once __DIR__ . '/SampleService.php';
require_once __DIR__ . '/SampleServiceUsingFileCache.php';

$time = microtime(true);
$container = new Container();
$container->register(new SampleOverrideContainer());
$container->register(new SampleContainer());

var_dump($container->get(\TestNamespace\SampleServiceUsingFileCache::class));
$service = $container->get('MyCustomSampleService');
var_dump($service);
var_dump($service->getSample1());
var_dump($container->getSampleService()->getSample1());
var_dump($container->get('SampleServiceAutoWire'));
var_dump(microtime(true) - $time);