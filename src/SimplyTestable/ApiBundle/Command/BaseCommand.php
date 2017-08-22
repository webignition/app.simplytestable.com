<?php

namespace SimplyTestable\ApiBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{
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
}
