<?php

namespace App\Tests\Services;

use App\Entity\Job\Ammendment;
use Doctrine\ORM\EntityManagerInterface;

class JobAmmendmentFactory
{
    const KEY_JOB = 'job';
    const KEY_REASON = 'reason';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $ammendmentValues
     *
     * @return Ammendment
     */
    public function create($ammendmentValues)
    {
        $ammendment = new Ammendment();
        $ammendment->setJob($ammendmentValues[self::KEY_JOB]);
        $ammendment->setReason($ammendmentValues[self::KEY_REASON]);

        $this->entityManager->persist($ammendment);
        $this->entityManager->flush();

        return $ammendment;
    }
}
