<?php

use Doctrine\Common\Annotations\AnnotationRegistry;


if (getenv('IS_JENKINS') === 'true') {
    $jenkinsNeedsThese = array(
        'Stripe' => '/stripe/stripe-php/lib/Stripe.php'
    );    
    
    foreach ($jenkinsNeedsThese as $class => $path) {
        if (!class_exists($class)) {
            $fullPath = realpath(__DIR__ . '/../../../../vendor' . $path);
            
            var_dump($fullPath);
            exit();
        }
    }    
}


$loader = require __DIR__.'/../vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
