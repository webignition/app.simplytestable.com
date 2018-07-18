<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Job\Ammendment;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobAmmendmentFactory
{
    const KEY_JOB = 'job';
    const KEY_REASON = 'reason';

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
     * @param array $ammendmentValues
     *
     * @return Ammendment
     */
    public function create($ammendmentValues)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $ammendment = new Ammendment();
        $ammendment->setJob($ammendmentValues[self::KEY_JOB]);
        $ammendment->setReason($ammendmentValues[self::KEY_REASON]);

        $entityManager->persist($ammendment);
        $entityManager->flush($ammendment);

        return $ammendment;
    }
}
