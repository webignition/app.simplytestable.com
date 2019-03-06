<?php

namespace App\Tests\Functional\Services\Job\Configuration;

use App\Entity\Job\Configuration;
use App\Entity\User;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Tests\Services\UserFactory;

class ConfigurationServiceGetListTest extends AbstractConfigurationServiceTest
{
    /**
     * @var User[]
     */
    private $users;

    /**
     * @var array
     */
    private $jobConfigurationValuesCollection = [
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
        [
            'userName' => 'public',
            'label' => 'foobar',
            'website' => 'http://foobar.example.com/',
            'type' => JobTypeService::FULL_SITE_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ]
            ],
        ],
    ];

    /**
     * @var Configuration[]
     */
    private $jobConfigurationCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $userFactory = self::$container->get(UserFactory::class);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->jobConfigurationCollection = $this->createJobConfigurationCollection(
            $this->jobConfigurationValuesCollection,
            $this->users
        );
    }

    /**
     * @dataProvider getListDataProvider
     *
     * @param $userName
     * @param $expectedJobConfigurationIndices
     */
    public function testGetList(
        $userName,
        $expectedJobConfigurationIndices
    ) {
        $user = $this->users[$userName];
        $this->setUser($user);

        $expectedJobConfigurationIds = [];
        foreach ($this->jobConfigurationCollection as $jobConfigurationIndex => $jobConfiguration) {
            if (in_array($jobConfigurationIndex, $expectedJobConfigurationIndices)) {
                $expectedJobConfigurationIds[] = $jobConfiguration->getId();
            }
        }

        $jobConfigurationList = $this->jobConfigurationService->getList();

        $jobConfigurationListIds = [];
        foreach ($jobConfigurationList as $jobConfiguration) {
            $jobConfigurationListIds[] = $jobConfiguration->getId();
        }

        sort($expectedJobConfigurationIds);
        sort($jobConfigurationListIds);

        $this->assertEquals($expectedJobConfigurationIds, $jobConfigurationListIds);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'public user' => [
                'userName' => 'public',
                'expectedJobConfigurationIndices' => [0, 2, 5],
            ],
            'team leader' => [
                'userName' => 'leader',
                'expectedJobConfigurationIndices' => [1, 3, 4],
            ],
            'team member' => [
                'userName' => 'member1',
                'expectedJobConfigurationIndices' => [1, 3, 4],
            ],
        ];
    }
}
