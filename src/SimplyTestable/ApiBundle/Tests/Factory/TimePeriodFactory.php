<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\TimePeriod;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TimePeriodFactory
{
    const KEY_START_DATE_TIME = 'start-date-time';
    const KEY_END_DATE_TIME = 'end-date-time';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $timePeriodValues
     *
     * @return TimePeriod
     */
    public function create($timePeriodValues)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $timePeriod = new TimePeriod();

        $timePeriod->setStartDateTime($timePeriodValues[self::KEY_START_DATE_TIME]);

        if (isset($timePeriodValues[self::KEY_END_DATE_TIME])) {
            $timePeriod->setEndDateTime($timePeriodValues[self::KEY_END_DATE_TIME]);
        }

        $entityManager->persist($timePeriod);
        $entityManager->flush($timePeriod);

        return $timePeriod;
    }
}
