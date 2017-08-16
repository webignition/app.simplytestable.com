<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\JobTypeService;
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
     * @dataProvider isJobLimitReachedForWebsiteDataProvider
     *
     * @param string $userName
     * @param array $jobValuesCollection
     * @param string $websiteUrl
     * @param bool $expectedIsFullSiteLimitReached
     * @param bool $expectedIsSingleUrlLimitReached
     */
    public function testIsJobLimitReachedForWebsite(
        $userName,
        $jobValuesCollection,
        $websiteUrl,
        $expectedIsFullSiteLimitReached,
        $expectedIsSingleUrlLimitReached
    ) {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');

        $jobFactory = new JobFactory($this->container);

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $user;
            $job = $jobFactory->create($jobValues);

            $job->setTimePeriod($this->createTimePeriod());
            $jobService->persistAndFlush($job);
        }

        $website = $websiteService->fetch($websiteUrl);

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $this->assertEquals(
            $expectedIsFullSiteLimitReached,
            $this->jobUserAccountPlanEnforcementService->isFullSiteJobLimitReachedForWebSite($website)
        );

        $this->assertEquals(
            $expectedIsSingleUrlLimitReached,
            $this->jobUserAccountPlanEnforcementService->isSingleUrlLimitReachedForWebsite($website)
        );
    }

    /**
     * @return array
     */
    public function isJobLimitReachedForWebsiteDataProvider()
    {
        return [
            'no limits not reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => false,
                'expectedIsSingleUrlLimitReached' => false,
            ],
            'no limits' => [
                'userName' => 'private',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => false,
                'expectedIsSingleUrlLimitReached' => false,
            ],
            'full site limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => true,
                'expectedIsSingleUrlLimitReached' => false,
            ],
            'single url limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => false,
                'expectedIsSingleUrlLimitReached' => true,
            ],
            'full site and single url limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => true,
                'expectedIsSingleUrlLimitReached' => true,
            ],
        ];
    }

    /**
     * @return TimePeriod
     */
    private function createTimePeriod()
    {
        $startDateTime = new \DateTime('first day of this month');
        $endDateTime = new \DateTime('last day of this month');
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime($startDateTime);
        $timePeriod->setEndDateTime($endDateTime);

        $entityManger = $this->container->get('doctrine.orm.entity_manager');
        $entityManger->persist($timePeriod);
        $entityManger->flush();

        return $timePeriod;
    }
}
