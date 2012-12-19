<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2012, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

include '../src/Go/Core/AspectKernel.php';
include 'DemoAspectKernel.php';

// Initialize demo aspect container
DemoAspectKernel::getInstance()->init(array(
    // Configuration for autoload namespaces
    'autoload' => array(
        'Go'               => realpath(__DIR__ . '/../src'),
        'TokenReflection'  => realpath(__DIR__ . '/../vendor/andrewsville/php-token-reflection/'),
        'Doctrine\\Common' => realpath(__DIR__ . '/../vendor/doctrine/common/lib/')
    ),
    // Default application directory
    'appDir' => __DIR__ . '/../demos',
    // Cache directory for Go! generated classes
    'cacheDir' => null, //__DIR__ . '/../cache',
    // Include paths for aspect weaving
    'includePaths' => array(),
    'debug' => true
));

$class = new Example('test');
$class->publicHello();
