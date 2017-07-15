<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class AmmendmentTest extends BaseSimplyTestableTestCase
{
    public function testUtf8Reason()
    {
        $reason = 'ɸ';

        $ammendment = new Ammendment();
        $ammendment->setJob($this->createJobFactory()->create());
        $ammendment->setReason($reason);

        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();

        $ammendmentId = $ammendment->getId();

        $this->getManager()->clear();

        $this->assertEquals(
            $reason,
            $this
                ->getManager()
                ->getRepository('SimplyTestable\ApiBundle\Entity\Job\Ammendment')
                ->find($ammendmentId)->getReason()
        );
    }

    public function testPersistWithNoConstraint()
    {
        $ammendment = new Ammendment();
        $ammendment->setJob($this->createJobFactory()->create());
        $ammendment->setReason('url-count-limited');

        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();

        $this->assertNotNull($ammendment->getId());
    }

    public function testPersistWithConstraint()
    {
        $ammendment = new Ammendment();
        $ammendment->setJob($this->createJobFactory()->create());
        $ammendment->setReason('url-count-limited');
        $ammendment->setConstraint($this->createAccountPlanConstraint());

        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();

        $this->assertNotNull($ammendment->getId());
    }

    public function testJobAmmendmentCountWithOneAmmendment()
    {
        $job = $this->createJobFactory()->create();
        $ammendment = new Ammendment();
        $ammendment->setJob($job);
        $ammendment->setReason('url-count-limited');

        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();

        $this->assertCount(1, $job->getAmmendments());
    }

    public function testJobAmmendmentCountWithMultipleAmmendments()
    {
        $job = $this->createJobFactory()->create();

        $ammendments = array();

        for ($ammendmentIndex = 0; $ammendmentIndex < 10; $ammendmentIndex++) {
            $ammendment = new Ammendment();
            $ammendment->setJob($job);
            $ammendment->setReason('url-count-limited-' . $ammendmentIndex);
            $this->getManager()->persist($ammendment);
            $ammendments[] = $ammendment;
        }

        $this->getManager()->flush();

        $this->assertCount(count($ammendments), $job->getAmmendments());
    }
}