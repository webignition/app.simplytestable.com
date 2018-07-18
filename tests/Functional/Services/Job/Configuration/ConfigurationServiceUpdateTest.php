<?php

namespace App\Tests\Functional\Services\Job\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use App\Services\UserService;

class ConfigurationServiceUpdateTest extends AbstractConfigurationServiceTest
{
    /**
     * @dataProvider updateExceptionDataProvider
     *
     * @param array $updatedJobConfigurationValues
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testUpdateException(
        $updatedJobConfigurationValues,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $userService = self::$container->get(UserService::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $existingJobConfigurationValues = [
            'label' => 'foo',
            'website' => 'http://example.com/',
            'type' => JobTypeService::FULL_SITE_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ]
            ],
        ];

        $this->createJobConfigurationCollection([$existingJobConfigurationValues]);

        $jobConfigurationValues = [
            'label' => 'bar',
            'website' => 'http://example.com/',
            'type' => JobTypeService::SINGLE_URL_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ]
            ],
        ];

        $jobConfigurationValuesModel = $this->createJobConfigurationValuesModel($jobConfigurationValues);

        $jobConfiguration = $this->jobConfigurationService->create($jobConfigurationValuesModel);

        $updateJobConfigurationValuesModel = $this->createJobConfigurationValuesModel(array_merge(
            $jobConfigurationValues,
            $updatedJobConfigurationValues
        ));

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->jobConfigurationService->update($jobConfiguration, $updateJobConfigurationValuesModel);
    }

    /**
     * @return array
     */
    public function updateExceptionDataProvider()
    {
        return [
            'non-unique label' => [
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                ],
                'expectedExceptionMessage' => 'Label "foo" is not unique',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
            ],
            'matching configuration already exists' => [
                'updatedJobConfigurationValues' => [
                    'type' => JobTypeService::FULL_SITE_NAME,
                ],
                'expectedExceptionMessage' => 'Matching configuration already exists',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
            ],
        ];
    }

    /**
     * @dataProvider updateMatchesCurrentJobConfigurationDataProvider
     *
     * @param array $jobConfigurationValues
     * @param array $updatedJobConfigurationValues
     */
    public function testUpdateMatchesCurrentJobConfiguration($jobConfigurationValues, $updatedJobConfigurationValues)
    {
        $userService = self::$container->get(UserService::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $jobConfigurationValuesModel = $this->createJobConfigurationValuesModel($jobConfigurationValues);

        $jobConfiguration = $this->jobConfigurationService->create($jobConfigurationValuesModel);

        $serialisedJobConfiguration = $jobConfiguration->jsonSerialize();

        $updatedJobConfigurationValuesModel = $this->createJobConfigurationValuesModel($updatedJobConfigurationValues);

        $this->jobConfigurationService->update($jobConfiguration, $updatedJobConfigurationValuesModel);

        $this->assertEquals($serialisedJobConfiguration, $jobConfiguration->jsonSerialize());
    }

    /**
     * @return array
     */
    public function updateMatchesCurrentJobConfigurationDataProvider()
    {
        return [
            'same label' => [
                'jobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                ],
            ],
            'empty label' => [
                'jobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                ],
                'updatedJobConfigurationValues' => [
                    'label' => null,
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateSuccessDataProvider
     *
     * @param array $jobConfigurationValues
     * @param array $updatedJobConfigurationValues
     * @param array $expectedSerializedUpdatedJobConfiguration
     */
    public function testUpdateSuccess(
        $jobConfigurationValues,
        $updatedJobConfigurationValues,
        $expectedSerializedUpdatedJobConfiguration
    ) {
        $userService = self::$container->get(UserService::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $jobConfiguration = $this->jobConfigurationService->create(
            $this->createJobConfigurationValuesModel($jobConfigurationValues)
        );

        $this->jobConfigurationService->update(
            $jobConfiguration,
            $this->createJobConfigurationValuesModel($updatedJobConfigurationValues)
        );

        $this->assertEquals($expectedSerializedUpdatedJobConfiguration, $jobConfiguration->jsonSerialize());
    }

    /**
     * @return array
     */
    public function updateSuccessDataProvider()
    {
        return [
            'label change only' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'bar',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update parameters only' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'parameters' => 'updated parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'updated parameters string',
                ],
            ],
            'update type only' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'type' => JobTypeService::SINGLE_URL_NAME,
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update task configuration only' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                        ]
                    ],
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update website only' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'website' => 'http://foo.example.com/',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://foo.example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update all values except label' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'website' => 'http://foo.example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'updated parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://foo.example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'updated parameters string',
                ],
            ],
        ];
    }
}
