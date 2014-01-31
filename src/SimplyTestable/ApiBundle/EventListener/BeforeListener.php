<?php

namespace SimplyTestable\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

class BeforeListener
{
    
    /**
     *
     * @var Logger
     */
    private $logger;
    
    /**
     *
     * @param Logger $logger
     */
    public function __construct(
            Logger $logger
    )
    {        
        $this->logger = $logger;
    }    

    public function onKernelController(FilterControllerEvent $event)
    {     
        $controllerCallable = $event->getController();
        
        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controllerCallable)) {
            return;
        }
        
        $controller = $controllerCallable[0];
        $methodName = $controllerCallable[1];
        
        if ($controller instanceof ApiController) {
            /* @var $controller ApiController */            
            $methodArguments = $controller->getArguments($methodName);
            foreach ($controller->getInputDefinition($methodName)->getArguments() as $inputArgument) {
                /* @var $inputArgument InputArgument */
                if ($inputArgument->isRequired()) {
                    if (!$methodArguments->has($inputArgument->getName())) {
                        $this->logger->warn('BeforeListener' . get_class($controller) . '::' . $methodName.': missing required argument ['.$inputArgument->getName().']');
                        throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
                    }
                }
            }           
        }
        

    }

}