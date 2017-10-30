<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ConfigurationServiceGetTest extends AbstractConfigurationServiceTest
{
    /**
     * @var User[]
     */
    private $users;

    protected function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param $userName
     * @param $existingJobConfigurationValuesCollection
     * @param $label
     * @param $expectedJobConfigurationIndex
     */
    public function testGet(
        $userName,
        $existingJobConfigurationValuesCollection,
        $label,
        $expectedJobConfigurationIndex
    ) {
        $jobConfigurationIds = [];

        foreach ($existingJobConfigurationValuesCollection as $existingJobConfigurationValues) {
            $existingJobConfigurationValuesModel = $this->createJobConfigurationValuesModel(
                $existingJobConfigurationValues
            );

            $currentUser = $this->users[$existingJobConfigurationValues['userName']];
            $this->setUser($currentUser);

            $existingJobConfiguration = $this->jobConfigurationService->create($existingJobConfigurationValuesModel);
            $jobConfigurationIds[] = $existingJobConfiguration;
        }

        $user = $this->users[$userName];
        $this->setUser($user);

        $jobConfiguration = $this->jobConfigurationService->get($label);

        if (is_null($expectedJobConfigurationIndex)) {
            $this->assertNull($jobConfiguration);
        } else {
            $expectedJobConfigurationId = $jobConfigurationIds[$expectedJobConfigurationIndex]->getId();

            $this->assertEquals($expectedJobConfigurationId, $jobConfiguration->getId());
        }
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        $teamJobConfigurationValuesCollection = [
            [
                'userName' => 'member1',
                'label' => 'member1',
                'website' => 'http://foo.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],
            [
                'userName' => 'member2',
                'label' => 'member2',
                'website' => 'http://member2.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],
            [
                'userName' => 'leader',
                'label' => 'leader',
                'website' => 'http://leader.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],

        ];

        return [
            'no existing job configurations' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [],
                'label' => 'foo',
                'expectedJobConfigurationIndex' => null,
            ],
            'match for user' => [
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
                    ],
                    [
                        'userName' => 'public',
                        'label' => 'bar',
                        'website' => 'http://bar.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                    ],
                ],
                'label' => 'bar',
                'expectedJobConfigurationIndex' => 1,
            ],
            'match for leader' => [
                'userName' => 'leader',
                'existingJobConfigurationValuesCollection' => $teamJobConfigurationValuesCollection,
                'label' => 'leader',
                'expectedJobConfigurationIndex' => 2,
            ],
            'match for member1' => [
                'userName' => 'member1',
                'existingJobConfigurationValuesCollection' => $teamJobConfigurationValuesCollection,
                'label' => 'leader',
                'expectedJobConfigurationIndex' => 2,
            ],
            'match for member2' => [
                'userName' => 'member2',
                'existingJobConfigurationValuesCollection' => $teamJobConfigurationValuesCollection,
                'label' => 'member1',
                'expectedJobConfigurationIndex' => 0,
            ],
        ];
    }
}
