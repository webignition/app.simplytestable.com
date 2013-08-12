<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

/**
 * Explicitly include some dependencies that otherwise result in 'class not found'
 * errors when running on Jenkins.
 * 
 * Works on local dev environment. Works on live. Works on Travis. Doesn't work on Jenkins.
 */
$jenkinsNeedsThese = array(
    'Stripe' => '/stripe/stripe-php/lib/Stripe.php',
    'ExpressiveDate' => '/jasonlewis/expressive-date/src/ExpressiveDate.php'
);    

foreach ($jenkinsNeedsThese as $class => $path) {
    if (!class_exists($class)) {
        require_once(realpath(__DIR__ . '/../vendor' . $path));
    }
}  

return $loader;