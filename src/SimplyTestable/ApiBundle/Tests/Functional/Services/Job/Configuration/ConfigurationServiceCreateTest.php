<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ConfigurationServiceCreateTest extends AbstractConfigurationServiceTest
{
    /**
     * @dataProvider createWithMissingRequiredValueDataProvider
     *
     * @param array $configurationValues
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testCreateWithMissingRequiredValue(
        $configurationValues,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $this->setExpectedException(
            JobConfigurationServiceException::class,
            $expectedExceptionMessage,
            $expectedExceptionCode
        );

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $configurationValuesModel = $this->createJobConfigurationValuesModel($configurationValues);

        $this->jobConfigurationService->create($configurationValuesModel);
    }

    /**
     * @return array
     */
    public function createWithMissingRequiredValueDataProvider()
    {
        return [
            'missing label' => [
                'configurationValues' => [],
                'expectedExceptionMessage' => 'Label cannot be empty',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY,
            ],
            'empty label' => [
                'configurationValues' => [
                    'label' => '',
                ],
                'expectedExceptionMessage' => 'Label cannot be empty',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY,
            ],
            'null label' => [
                'configurationValues' => [
                    'label' => null,
                ],
                'expectedExceptionMessage' => 'Label cannot be empty',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY,
            ],
            'missing website' => [
                'configurationValues' => [
                    'label' => 'foo',
                ],
                'expectedExceptionMessage' => 'Website cannot be empty',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_WEBSITE_CANNOT_BE_EMPTY,
            ],
            'missing type' => [
                'configurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                ],
                'expectedExceptionMessage' => 'Type cannot be empty',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_TYPE_CANNOT_BE_EMPTY,
            ],
            'missing task configuration collection' => [
                'configurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                ],
                'expectedExceptionMessage' => 'TaskConfigurationCollection is empty',
                'expectedExceptionCode' =>
                    JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY,
            ],
        ];
    }

    /**
     * @dataProvider createWithNonUniqueLabelDataProvider
     *
     * @param string $creatorUserName
     * @param string $requestorUserName
     */
    public function testCreateWithNonUniqueLabel($creatorUserName, $requestorUserName)
    {
        $this->setExpectedException(
            JobConfigurationServiceException::class,
            'Label "foo" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $creator = $users[$creatorUserName];
        $requestor = $users[$requestorUserName];

        $jobConfigurationValues = $this->createJobConfigurationValuesModel([
            'label' => 'foo',
            'website' => 'http://example.com/',
            'type' => JobTypeService::FULL_SITE_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ],
            ],
        ]);

        $this->setUser($creator);
        $this->jobConfigurationService->create($jobConfigurationValues);

        $this->setUser($requestor);
        $this->jobConfigurationService->create($jobConfigurationValues);
    }

    /**
     * @return array
     */
    public function createWithNonUniqueLabelDataProvider()
    {
        return [
            'user creator, user requestor' => [
                'creatorUserName' => 'public',
                'requestorUserName' => 'public',
            ],
            'leader creator, member1 requestor' => [
                'creatorUserName' => 'leader',
                'requestorUserName' => 'member1',
            ],
            'member1 creator, leader requestor' => [
                'creatorUserName' => 'member1',
                'requestorUserName' => 'leader',
            ],
            'member1 creator, member2 requestor' => [
                'creatorUserName' => 'member1',
                'requestorUserName' => 'member2',
            ],
        ];
    }

    /**
     * @dataProvider creteHasExistingDataProvider
     *
     * @param string $creatorUserName
     * @param string $requestorUserName
     */
    public function testCreateHasExisting($creatorUserName, $requestorUserName)
    {
        $this->setExpectedException(
            JobConfigurationServiceException::class,
            'Matching configuration already exists',
            JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
        );

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $creator = $users[$creatorUserName];
        $requestor = $users[$requestorUserName];

        $jobConfigurationValues = $this->createJobConfigurationValuesModel([
            'label' => 'foo',
            'website' => 'http://example.com/',
            'type' => JobTypeService::FULL_SITE_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ],
            ],
        ]);

        $this->setUser($creator);
        $this->jobConfigurationService->create($jobConfigurationValues);

        $jobConfigurationValues->setLabel('bar');

        $this->setUser($requestor);
        $this->jobConfigurationService->create($jobConfigurationValues);
    }

    /**
     * @return array
     */
    public function creteHasExistingDataProvider()
    {
        return [
            'user creator, user requestor' => [
                'creatorUserName' => 'public',
                'requestorUserName' => 'public',
            ],
            'leader creator, member1 requestor' => [
                'creatorUserName' => 'leader',
                'requestorUserName' => 'member1',
            ],
            'member1 creator, leader requestor' => [
                'creatorUserName' => 'member1',
                'requestorUserName' => 'leader',
            ],
            'member1 creator, member2 requestor' => [
                'creatorUserName' => 'member1',
                'requestorUserName' => 'member2',
            ],
        ];
    }

    /**
     * @dataProvider createSuccessDataProvider
     *
     * @param string $userName
     * @param array $existingJobConfigurationValuesCollection
     * @param array $jobConfigurationValues
     */
    public function testCreateSuccess(
        $userName,
        $existingJobConfigurationValuesCollection,
        $jobConfigurationValues
    ) {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->createJobConfigurationCollection($existingJobConfigurationValuesCollection, $users);

        $jobConfigurationValuesModel = $this->createJobConfigurationValuesModel($jobConfigurationValues);

        $user = $users[$userName];
        $this->setUser($user);

        $jobConfiguration = $this->jobConfigurationService->create($jobConfigurationValuesModel);

        $this->assertInstanceOf(Configuration::class, $jobConfiguration);
        $this->assertNotNull($jobConfiguration->getId());

        $this->assertEquals($jobConfigurationValuesModel->getLabel(), $jobConfiguration->getLabel());
        $this->assertEquals($user, $jobConfiguration->getUser());
        $this->assertEquals($jobConfigurationValuesModel->getWebsite(), $jobConfiguration->getWebsite());
        $this->assertEquals($jobConfigurationValuesModel->getType(), $jobConfiguration->getType());
        $this->assertEquals($jobConfigurationValuesModel->getParameters(), $jobConfiguration->getParameters());

        $taskConfigurationCollection = $jobConfigurationValuesModel->getTaskConfigurationCollection();

        foreach ($taskConfigurationCollection->get() as $taskConfiguration) {
            $this->assertEquals($jobConfiguration, $taskConfiguration->getJobConfiguration());
        }
    }

    /**
     * @return array
     */
    public function createSuccessDataProvider()
    {
        return [
            'no existing job configurations' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [],
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameter string',
                ],
            ],
            'no matching job configurations for website' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [
                    [
                        'userName' => 'public',
                        'label' => 'foo',
                        'website' => 'http://foo.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                        'parameters' => 'parameter string',
                    ],
                ],
                'jobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://bar.example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameter string',
                ],
            ],
            'no matching job configurations for type' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [
                    [
                        'userName' => 'public',
                        'label' => 'foo',
                        'website' => 'http://example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                        'parameters' => 'parameter string',
                    ],
                ],
                'jobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameter string',
                ],
            ],
            'no matching job configurations for parameters' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [
                    [
                        'userName' => 'public',
                        'label' => 'foo',
                        'website' => 'http://example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                        'parameters' => 'parameter string',
                    ],
                ],
                'jobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'bar parameter string',
                ],
            ],
            'no matching job configurations for individual user' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [
                    [
                        'userName' => 'private',
                        'label' => 'foo',
                        'website' => 'http://example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                        'parameters' => 'parameter string',
                    ],
                ],
                'jobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameter string',
                ],
            ],
            'no matching job configurations for team user' => [
                'userName' => 'leader',
                'existingJobConfigurationValuesCollection' => [
                    [
                        'userName' => 'member1',
                        'label' => 'foo',
                        'website' => 'http://foo.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                        'parameters' => 'parameter string',
                    ],
                    [
                        'userName' => 'member2',
                        'label' => 'bar',
                        'website' => 'http://bar.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                        'parameters' => 'parameter string',
                    ],
                ],
                'jobConfigurationValues' => [
                    'label' => 'foobar',
                    'website' => 'http://foobar.example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameter string',
                ],
            ],
        ];
    }
}
