<?php

namespace SimplyTestable\ApiBundle\Command;

use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->getContainer()->get('logger');
    }

    /**
     * @param int $number
     *
     * @return boolean
     */
    protected function isHttpStatusCode($number)
    {
        return strlen($number) == 3;
    }

    /**
     * @return ApplicationStateService
     */
    protected function getApplicationStateService()
    {
        if (is_null($this->applicationStateService)) {
            $this->applicationStateService = $this->getContainer()->get(
                'simplytestable.services.applicationStateService'
            );
        }

        return $this->applicationStateService;
    }

    /**
     * @return string
     */
    private function getStateResourcePath()
    {
        $kernel = $this->getContainer()->get('kernel');

        return sprintf(
            '%s%s',
            $kernel->locateResource('@SimplyTestableApiBundle/Resources/config/state/'),
            $kernel->getEnvironment()
        );
    }
}
