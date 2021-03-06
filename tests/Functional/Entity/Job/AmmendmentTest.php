<?php

namespace App\Tests\Functional\Entity\Job;

use App\Tests\Services\ConstraintFactory;
use App\Tests\Services\JobFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Services\PlanFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Job\Ammendment;

class AmmendmentTest extends AbstractBaseTestCase
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

        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
    }

    public function testUtf8Reason()
    {
        $ammendmentRepository = $this->entityManager->getRepository(Ammendment::class);

        $reason = 'ɸ';

        $ammendment = new Ammendment();
        $ammendment->setJob($this->jobFactory->create());
        $ammendment->setReason($reason);

        $this->entityManager->persist($ammendment);
        $this->entityManager->flush();

        $ammendmentId = $ammendment->getId();

        $this->entityManager->clear();

        $this->assertEquals(
            $reason,
            $ammendmentRepository->find($ammendmentId)->getReason()
        );
    }

    public function testPersistWithNoConstraint()
    {
        $ammendment = new Ammendment();
        $ammendment->setJob($this->jobFactory->create());
        $ammendment->setReason('url-count-limited');

        $this->entityManager->persist($ammendment);
        $this->entityManager->flush();

        $this->assertNotNull($ammendment->getId());
    }

    public function testPersistWithConstraint()
    {
        $planFactory = self::$container->get(PlanFactory::class);
        $plan = $planFactory->create([]);

        $constraintFactory = self::$container->get(ConstraintFactory::class);

        $constraint = $constraintFactory->create($plan, [
            ConstraintFactory::KEY_NAME => 'constraint-name',
            ConstraintFactory::KEY_LIMIT => 1,
        ]);

        $ammendment = new Ammendment();
        $ammendment->setJob($this->jobFactory->create());
        $ammendment->setReason('url-count-limited');
        $ammendment->setConstraint($constraint);

        $this->entityManager->persist($ammendment);
        $this->entityManager->flush();

        $this->assertNotNull($ammendment->getId());
    }

    public function testJobAmmendmentCountWithOneAmmendment()
    {
        $job = $this->jobFactory->create();
        $ammendment = new Ammendment();
        $ammendment->setJob($job);
        $ammendment->setReason('url-count-limited');

        $this->entityManager->persist($ammendment);
        $this->entityManager->flush();

        $this->assertCount(1, $job->getAmmendments());
    }

    public function testJobAmmendmentCountWithMultipleAmmendments()
    {
        $job = $this->jobFactory->create();

        $ammendments = array();

        for ($ammendmentIndex = 0; $ammendmentIndex < 10; $ammendmentIndex++) {
            $ammendment = new Ammendment();
            $ammendment->setJob($job);
            $ammendment->setReason('url-count-limited-' . $ammendmentIndex);
            $this->entityManager->persist($ammendment);
            $ammendments[] = $ammendment;
        }

        $this->entityManager->flush();

        $this->assertCount(count($ammendments), $job->getAmmendments());
    }
}
