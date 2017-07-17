<?php

namespace SimplyTestable\ApiBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BeforeListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param FilterControllerEvent $event
     */
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
                        $this->logger->warning(sprintf(
                            'BeforeListener %s::%s: missing required argument [%s]',
                            get_class($controller),
                            $methodName,
                            $inputArgument->getName()
                        ));
                        throw new HttpException(400);
                    }
                }
            }
        }
    }
}
