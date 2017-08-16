<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class JobUserAccountPlanEnforcementServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobUserAccountPlanEnforcementService = $this->container->get(
            'simplytestable.services.jobuseraccountplanenforcementservice'
        );
    }

    /**
     * @dataProvider isFullSiteJobLimitReachedForWebSiteDataProvider
     *
     * @param string $userName
     * @param array $jobValuesCollection
     * @param string $websiteUrl
     * @param bool $expectedIsLimitReached
     */
    public function testIsFullSiteJobLimitReachedForWebSite(
        $userName,
        $jobValuesCollection,
        $websiteUrl,
        $expectedIsLimitReached
    ) {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $startDateTime = new \DateTime('first day of this month');
        $endDateTime = new \DateTime('last day of this month');
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime($startDateTime);
        $timePeriod->setEndDateTime($endDateTime);

        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');

        $jobFactory = new JobFactory($this->container);

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $user;
            $job = $jobFactory->create($jobValues);

            $job->setTimePeriod($timePeriod);
            $jobService->persistAndFlush($job);
        }

        $website = $websiteService->fetch($websiteUrl);

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $this->assertEquals(
            $expectedIsLimitReached,
            $this->jobUserAccountPlanEnforcementService->isFullSiteJobLimitReachedForWebSite($website)
        );
    }

    /**
     * @return array
     */
    public function isFullSiteJobLimitReachedForWebSiteDataProvider()
    {
        return [
            'limit not reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [],
                'websiteUrl' => 'http://example.com/',
                'expectedIsLimitReached' => false,
            ],
            'no limit' => [
                'userName' => 'private',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsLimitReached' => false,
            ],
            'limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_USER => 'public',
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsLimitReached' => true,
            ],
        ];
    }
}
