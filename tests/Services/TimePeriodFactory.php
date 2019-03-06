<?php

namespace App\Tests\Services;

use App\Entity\TimePeriod;
use Doctrine\ORM\EntityManagerInterface;

class TimePeriodFactory
{
    const KEY_START_DATE_TIME = 'start-date-time';
    const KEY_END_DATE_TIME = 'end-date-time';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $timePeriodValues
     *
     * @return TimePeriod
     */
    public function create($timePeriodValues)
    {
        $timePeriod = new TimePeriod();

        $timePeriod->setStartDateTime($timePeriodValues[self::KEY_START_DATE_TIME]);

        if (isset($timePeriodValues[self::KEY_END_DATE_TIME])) {
            $timePeriod->setEndDateTime($timePeriodValues[self::KEY_END_DATE_TIME]);
        }

        $this->entityManager->persist($timePeriod);
        $this->entityManager->flush();

        return $timePeriod;
    }
}
