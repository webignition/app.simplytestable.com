<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare\AmmendmentCases;

use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

/**
 * Test preparing a full-site test with the public user for a site with
 * more urls than is permitted creates an url count ammendment
 */
class UrlCountTest extends BaseSimplyTestableTestCase
{
    const CANONICAL_URL = 'http://example.com';

    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->create();
        $jobFactory->resolve($this->job);

        $this->setRequiredSitemapXmlUrlCount(11);
        $this->queuePrepareHttpFixturesForJob(self::DEFAULT_CANONICAL_URL);

        $this->getJobPreparationService()->prepare($this->job);
    }

    public function testHasAmmendment()
    {
        $this->assertEquals(1, $this->job->getAmmendments()->count());
    }

    public function testAmmendmentReason()
    {
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-11', $this->getAmmendment()->getReason());
    }

    /**
     * @return Ammendment
     */
    private function getAmmendment()
    {
        return $this->job->getAmmendments()->first();
    }
}
