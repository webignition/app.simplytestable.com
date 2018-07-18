<?php

namespace App\Tests\Functional\Entity\Job;

use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Factory\ConstraintFactory;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\PlanFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Job\RejectionReason;

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

        $this->jobFactory = new JobFactory(self::$container);
        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
    }

    public function testUtf8Reason()
    {
        $rejectionReasonRepository = $this->entityManager->getRepository(RejectionReason::class);

        $reason = 'ɸ';
        $job = $this->jobFactory->create();

        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason($reason);

        $this->entityManager->persist($rejectionReason);
        $this->entityManager->flush();

        $rejectionReasonId = $rejectionReason->getId();

        $this->entityManager->clear();

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
        $planFactory = new PlanFactory(self::$container);
        $plan = $planFactory->create([]);

        $constraintFactory = new ConstraintFactory(self::$container);
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
