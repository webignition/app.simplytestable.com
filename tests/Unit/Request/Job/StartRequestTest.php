<?php

namespace App\Tests\Unit\Request\Job;

use App\Entity\Job\Type as JobType;
use App\Entity\User;
use App\Entity\WebSite;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use App\Request\Job\StartRequest;
use App\Services\JobTypeService;
use App\Tests\Factory\ModelFactory;

class StartRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param User $user
     * @param WebSite $website
     * @param JobType $jobType
     * @param TaskConfigurationCollection $taskConfigurationCollection
     * @param array $jobParameters
     * @param User $expectedUser
     * @param WebSite $expectedWebsite
     * @param JobType $expectedJobType
     * @param TaskConfigurationCollection $expectedTaskConfigurationCollection
     * @param $expectedJobParameters
     */
    public function testCreate(
        User $user,
        WebSite $website,
        JobType $jobType,
        TaskConfigurationCollection $taskConfigurationCollection,
        $jobParameters,
        User $expectedUser,
        WebSite $expectedWebsite,
        JobType $expectedJobType,
        TaskConfigurationCollection $expectedTaskConfigurationCollection,
        $expectedJobParameters
    ) {
        $jobStartRequest = new StartRequest($user, $website, $jobType, $taskConfigurationCollection, $jobParameters);

        $this->assertEquals($expectedUser, $jobStartRequest->getUser());
        $this->assertEquals($expectedWebsite, $jobStartRequest->getWebsite());
        $this->assertEquals($expectedJobType, $jobStartRequest->getJobType());
        $this->assertEquals($expectedTaskConfigurationCollection, $jobStartRequest->getTaskConfigurationCollection());
        $this->assertEquals($expectedJobParameters, $jobStartRequest->getJobParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $website = ModelFactory::createWebsite([
            ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
        ]);

        $jobType = ModelFactory::createJobType([
            ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
        ]);

        $taskConfigurationCollection = ModelFactory::createTaskConfigurationCollection();

        $parameters = [
            'foo' => 'bar',
        ];

        return [
            'populated' => [
                'user' => $user,
                'website' => $website,
                'jobType' => $jobType,
                'taskConfigurationCollection' => $taskConfigurationCollection,
                'parameters' => $parameters,
                'expectedUser' => $user,
                'expectedWebsite' => $website,
                'expectedJobType' => $jobType,
                'expectedTaskConfigurationCollection' => $taskConfigurationCollection,
                'expectedParameters' => $parameters,
            ],
        ];
    }
}
