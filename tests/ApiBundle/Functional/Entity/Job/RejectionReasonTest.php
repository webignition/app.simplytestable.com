<?php

namespace Tests\ApiBundle\Functional\Entity\Job;

use Doctrine\ORM\EntityManagerInterface;
use Tests\ApiBundle\Factory\ConstraintFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\PlanFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;

class RejectionReasonTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
    }

    public function testUtf8Reason()
    {
        $reason = 'ɸ';
        $job = $this->jobFactory->create();

        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason($reason);

        $this->entityManager->persist($rejectionReason);
        $this->entityManager->flush();

        $rejectionReasonId = $rejectionReason->getId();

        $this->entityManager->clear();

        $rejectionReasonRepository = $this->entityManager->getRepository(RejectionReason::class);

        $this->assertEquals(
            $reason,
            $rejectionReasonRepository->find($rejectionReasonId)->getReason()
        );
    }

    public function testPersistWithNoConstraint()
    {
        $job = $this->jobFactory->create();

        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');

        $this->entityManager->persist($rejectionReason);
        $this->entityManager->flush();

        $this->assertNotNull($rejectionReason->getId());
    }

    public function testPersistWithConstraint()
    {
        $planFactory = new PlanFactory($this->container);
        $plan = $planFactory->create([]);

        $constraintFactory = new ConstraintFactory($this->container);
        $constraint = $constraintFactory->create($plan, [
            ConstraintFactory::KEY_NAME => 'constraint-name',
            ConstraintFactory::KEY_LIMIT => 1,
        ]);

        $job = $this->jobFactory->create();

        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');
        $rejectionReason->setConstraint($constraint);

        $this->entityManager->persist($rejectionReason);
        $this->entityManager->flush();

        $this->assertNotNull($rejectionReason->getId());
    }
}
