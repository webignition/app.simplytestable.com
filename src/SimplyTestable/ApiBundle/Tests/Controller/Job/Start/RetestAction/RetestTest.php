<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\RetestAction;

use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Controller\Job\Start\ActionTest;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use Symfony\Component\HttpFoundation\Request;

class RetestTest extends ActionTest
{
    public function testWithInvalidId()
    {
        $request = new Request();
        $response = $this->createJobStartController($request)->retestAction(
            $request,
            'foo',
            1
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithIncompleteJob()
    {
        $job = $this->createJobFactory()->create();
        $request = new Request();
        $response = $this->createJobStartController($request)->retestAction(
            $request,
            'foo',
            $job->getId()
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testRetestJobIsNotOriginalJob()
    {
        $job = $this->createJobFactory()->create();
        $this->completeJob($job);

        $request = new Request();
        $response = $this->createJobStartController($request)->retestAction(
            $request,
            'foo',
            $job->getId()
        );

        $retestJob = $this->getJobFromResponse($response);
        $this->assertNotEquals($job->getId(), $retestJob->getId());
    }

    /**
     * @dataProvider retestDataProvider
     *
     * @param array $jobValues
     */
    public function testRetestFoo($jobValues)
    {
        $job = $this->createJobFactory()->create($jobValues);
        $this->completeJob($job);

        $request = new Request();
        $response = $this->createJobStartController($request)->retestAction(
            $request,
            'foo',
            $job->getId()
        );

        $retestJob = $this->getJobFromResponse($response);

        $this->assertEquals($job->getWebsite()->getId(), $retestJob->getWebsite()->getId());
        $this->assertEquals($job->getType()->getName(), $retestJob->getType()->getName());

        $jobTaskTypeNames = array();
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            $jobTaskTypeNames[] = $taskType->getName();
        }

        $retestJobTaskTypeNames = array();
        foreach ($retestJob->getRequestedTaskTypes() as $taskType) {
            $retestJobTaskTypeNames[] = $taskType->getName();
        }

        $this->assertEquals($jobTaskTypeNames, $retestJobTaskTypeNames);

        $jobTaskTypeOptionsArray = array();
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            /* @var TaskTypeOptions $taskTypeOptions */
            $options = $taskTypeOptions->getOptions();
            $jobTaskTypeOptionsArray[strtolower($taskTypeOptions->getTaskType())] = $options;
        }

        $retestJobTaskTypeOptionsArray = array();
        foreach ($retestJob->getTaskTypeOptions() as $taskTypeOptions) {
            /* @var TaskTypeOptions $taskTypeOptions */
            $options = $taskTypeOptions->getOptions();
            $retestJobTaskTypeOptionsArray[strtolower($taskTypeOptions->getTaskType())] = $options;
        }

        $this->assertEquals($jobTaskTypeOptionsArray, $retestJobTaskTypeOptionsArray);
    }

    /**
     * @return array
     */
    public function retestDataProvider()
    {
        return [
            'full site' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    JobFactory::KEY_TEST_TYPES => [
                        'html validation',
                        'css validation'
                    ],
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        'css validation' => [
                            'ignore-common-cdns' => 1,
                        ],
                    ],
                ],
            ],
            'single url' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                ],
            ],
        ];
    }
}
