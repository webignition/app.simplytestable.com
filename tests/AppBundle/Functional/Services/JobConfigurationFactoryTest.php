<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Entity\Job\Type as JobType;
use AppBundle\Entity\User;
use AppBundle\Entity\WebSite;
use AppBundle\Request\Job\StartRequest;
use AppBundle\Services\JobConfigurationFactory;
use AppBundle\Services\JobTypeService;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\ModelFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class JobConfigurationFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var JobConfigurationFactory
     */
    private $jobConfigurationFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);
    }

    /**
     * @dataProvider createFromJobStartRequestDataProvider
     *
     * @param User $user
     * @param WebSite $website
     * @param JobType $jobType
     * @param array $jobParameters
     * @param TaskConfigurationCollection $taskConfigurationCollection
     * @param array $expectedJobParameters
     * @param array $expectedTaskConfigurationCollectionValues
     */
    public function testCreateFromJobStartRequest(
        User $user,
        WebSite $website,
        JobType $jobType,
        $jobParameters,
        TaskConfigurationCollection $taskConfigurationCollection,
        $expectedJobParameters,
        $expectedTaskConfigurationCollectionValues
    ) {
        $jobStartRequest = new StartRequest(
            $user,
            $website,
            $jobType,
            $taskConfigurationCollection,
            $jobParameters
        );

        $jobConfiguration = $this->jobConfigurationFactory->createFromJobStartRequest($jobStartRequest);

        $this->assertEquals($user, $jobConfiguration->getUser());
        $this->assertEquals($website, $jobConfiguration->getWebsite());
        $this->assertEquals($jobType, $jobConfiguration->getType());
        $this->assertEquals($expectedJobParameters, $jobConfiguration->getParametersArray());
        $this->assertEquals($taskConfigurationCollection, $jobConfiguration->getTaskConfigurationsAsCollection());


        $jobConfigurationTaskConfigurationCollection = $jobConfiguration->getTaskConfigurationsAsCollection()->get();

        foreach ($jobConfigurationTaskConfigurationCollection as $taskConfigurationIndex => $taskConfiguration) {
            $expectedTaskConfiguration = $expectedTaskConfigurationCollectionValues[$taskConfigurationIndex];

            $this->assertEquals($expectedTaskConfiguration['task-type-name'], $taskConfiguration->getType()->getName());
            $this->assertEquals($expectedTaskConfiguration['options'], $taskConfiguration->getOptions());
        }
    }

    /**
     * @return array
     */
    public function createFromJobStartRequestDataProvider()
    {
        return [
            'empty parameters' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'website' => ModelFactory::createWebsite([
                    ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                ]),
                'jobType' => ModelFactory::createJobType([
                    ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                ]),
                'jobParameters' => [],
                'taskConfigurationCollection' => ModelFactory::createTaskConfigurationCollection(),
                'expectedJobParameters' => null,
                'expectedTaskConfigurationCollectionValues' => [],
            ],
            'non-empty parameters' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'website' => ModelFactory::createWebsite([
                    ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                ]),
                'jobType' => ModelFactory::createJobType([
                    ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                ]),
                'jobParameters' => [
                    'foo' => 'bar',
                ],
                'taskConfigurationCollection' => ModelFactory::createTaskConfigurationCollection(),
                'expectedJobParameters' => [
                    'foo' => 'bar',
                ],
                'expectedTaskConfigurationCollectionValues' => [],
            ],
            'task configuration collection' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'website' => ModelFactory::createWebsite([
                    ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                ]),
                'jobType' => ModelFactory::createJobType([
                    ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                ]),
                'jobParameters' => [],
                'taskConfigurationCollection' => ModelFactory::createTaskConfigurationCollection([
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-validation-foo' => 'html-validation-bar',
                        ],
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::LINK_INTEGRITY_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'link-integrity-foo' => 'link-integrity-bar',
                        ],
                    ],
                ]),
                'expectedJobParameters' => null,
                'expectedTaskConfigurationCollectionValues' => [
                    [
                        'task-type-name' => TaskTypeService::HTML_VALIDATION_TYPE,
                        'options' => [
                            'html-validation-foo' => 'html-validation-bar',
                        ],
                    ],
                    [
                        'task-type-name' => TaskTypeService::LINK_INTEGRITY_TYPE,
                        'options' => [
                            'link-integrity-foo' => 'link-integrity-bar',
                        ],
                    ],
                ],
            ],
        ];
    }
}