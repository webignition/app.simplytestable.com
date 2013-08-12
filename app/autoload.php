<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';

//        require_once(realpath(__DIR__ . '/../../../../vendor/stripe/stripe-php/lib/Stripe.php'));
//        
////        var_dump(realpath(__DIR__ . '/../../../../vendor/stripe/stripe-php/lib/Stripe.php'));
////        exit();
        
        if (!class_exists('Stripe')) {
            
            
            var_dump("Class 'Stripe' not found");
            
            if (!class_exists('\\Stripe')) {
                var_dump("Class '\Stripe' not found");
            }             
            
            if (!class_exists('Stripe_Customer')) {
                var_dump("Class 'Stripe_Customer' not found");
            }            
            
            if (!class_exists('\\Stripe_Customer')) {
                var_dump("Class '\Stripe_Customer' not found");
            }      
            
            if (!class_exists('QueryPath')) {
                var_dump("Class 'QueryPath' not found");
            }                  
            
            if (!class_exists('\\QueryPath')) {
                var_dump("Class '\QueryPath' not found");
            }               
            exit();
        }

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
