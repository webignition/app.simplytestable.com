<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Factory\ConstraintFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\PlanFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;

class RejectionReasonTest extends BaseSimplyTestableTestCase
{
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
    }

    public function testUtf8Reason()
    {
        $reason = 'ɸ';
        $job = $this->jobFactory->create();

        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason($reason);

        $this->getManager()->persist($rejectionReason);
        $this->getManager()->flush();

        $rejectionReasonId = $rejectionReason->getId();

        $this->getManager()->clear();

        $this->assertEquals(
            $reason,
            $this
                ->getManager()
                ->getRepository('SimplyTestable\ApiBundle\Entity\Job\RejectionReason')
                ->find($rejectionReasonId)->getReason()
        );
    }

    public function testPersistWithNoConstraint()
    {
        $job = $this->jobFactory->create();

        $rejectionReason = new RejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('insufficient-credit');

        $this->getManager()->persist($rejectionReason);
        $this->getManager()->flush();

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

        $this->getManager()->persist($rejectionReason);
        $this->getManager()->flush();

        $this->assertNotNull($rejectionReason->getId());
    }
}
