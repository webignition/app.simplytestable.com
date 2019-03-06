<?php

namespace App\Tests\Functional\Services\QueryBuilderFactory;

use Doctrine\ORM\Query\Parameter;
use App\Entity\Job\Type;
use App\Entity\State;
use App\Entity\User;
use App\Model\JobList\Configuration;
use App\Services\QueryBuilderFactory\JobListQueryBuilderFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class JobListQueryBuilderFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var JobListQueryBuilderFactory
     */
    private $jobListQueryBuilderFactory;

    /**
     * @var User[]
     */
    private $users;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobListQueryBuilderFactory = self::$container->get(JobListQueryBuilderFactory::class);

        $userFactory = self::$container->get(UserFactory::class);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array $configurationValues
     * @param string[] $expectedDQLParts
     * @param array $expectedQueryParameters
     */
    public function testCreate($configurationValues, $expectedDQLParts, $expectedQueryParameters)
    {
        if (isset($configurationValues[Configuration::KEY_USER])) {
            $configurationValues[Configuration::KEY_USER] = $this->users[$configurationValues[Configuration::KEY_USER]];
        }

        $configuration = new Configuration($configurationValues);

        $queryBuilder = $this->jobListQueryBuilderFactory->create($configuration);

        $this->assertEquals(implode(' ', $expectedDQLParts), $queryBuilder->getDQL());

        foreach ($queryBuilder->getParameters() as $parameterIndex => $parameter) {
            /* @var Parameter $parameter */

            $expectedParameter = $expectedQueryParameters[$parameterIndex];
            if (preg_match('/user:/', $expectedParameter['value'])) {
                $userId = str_replace('user:', '', $expectedParameter['value']);
                $expectedParameter['value'] = $this->users[$userId];
            }

            $this->assertEquals($expectedParameter['name'], $parameter->getName());
            $this->assertEquals($expectedParameter['value'], $parameter->getValue());
        }
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $typeFoo = new Type();
        $typeFoo->setName('type-foo');

        $typeBar = new Type();
        $typeBar->setName('type-bar');

        $stateFoo = new State();
        $stateFoo->setName('state-foo');

        $stateBar = new State();
        $stateBar->setName('state-bar');

        return [
            'default configuration' => [
                'configurationValues' => [],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [],
            ],
            'user: public user' => [
                'configurationValues' => [
                    Configuration::KEY_USER => 'public',
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND Job.user = :User0',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'User0',
                        'value' => 'user:public',
                    ],
                ],
            ],
            'user: team leader' => [
                'configurationValues' => [
                    Configuration::KEY_USER => 'leader',
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND (Job.user = :User0 OR Job.user = :User1 OR Job.user = :User2)',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'User0',
                        'value' => 'user:leader',
                    ],
                    [
                        'name' => 'User1',
                        'value' => 'user:member1',
                    ],
                    [
                        'name' => 'User2',
                        'value' => 'user:member2',
                    ],
                ],
            ],
            'type: single' => [
                'configurationValues' => [
                    Configuration::KEY_TYPES_TO_EXCLUDE => [$typeFoo],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND (Job.type != :Type0)',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Type0',
                        'value' => $typeFoo,
                    ],
                ],
            ],
            'type: multiple' => [
                'configurationValues' => [
                    Configuration::KEY_TYPES_TO_EXCLUDE => [$typeFoo, $typeBar],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND ((Job.type != :Type0 AND Job.type != :Type1))',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Type0',
                        'value' => $typeFoo,
                    ],
                    [
                        'name' => 'Type1',
                        'value' => $typeBar,
                    ],
                ],
            ],
            'state: single' => [
                'configurationValues' => [
                    Configuration::KEY_STATES_TO_EXCLUDE => [$stateFoo],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND (Job.state != :State0)',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'State0',
                        'value' => $stateFoo,
                    ],
                ],
            ],
            'state: multiple' => [
                'configurationValues' => [
                    Configuration::KEY_STATES_TO_EXCLUDE => [$stateFoo, $stateBar],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND ((Job.state != :State0 AND Job.state != :State1))',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'State0',
                        'value' => $stateFoo,
                    ],
                    [
                        'name' => 'State1',
                        'value' => $stateBar,
                    ],
                ],
            ],
            'ids to include: single' => [
                'configurationValues' => [
                    Configuration::KEY_JOB_IDS_TO_INCLUDE => [1],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'OR Job.id = :Id0',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Id0',
                        'value' => 1,
                    ],
                ],
            ],
            'ids to include: multiple' => [
                'configurationValues' => [
                    Configuration::KEY_JOB_IDS_TO_INCLUDE => [2, 3],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'OR (Job.id = :Id0 OR Job.id = :Id1)',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Id0',
                        'value' => 2,
                    ],
                    [
                        'name' => 'Id1',
                        'value' => 3,
                    ],
                ],
            ],
            'ids to exclude: single' => [
                'configurationValues' => [
                    Configuration::KEY_JOB_IDS_TO_EXCLUDE => [1],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND Job.id != :Id0',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Id0',
                        'value' => 1,
                    ],
                ],
            ],
            'ids to exclude: multiple' => [
                'configurationValues' => [
                    Configuration::KEY_JOB_IDS_TO_EXCLUDE => [2, 3],
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'WHERE 1 = 1',
                    'AND (Job.id != :Id0 AND Job.id != :Id1)',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Id0',
                        'value' => 2,
                    ],
                    [
                        'name' => 'Id1',
                        'value' => 3,
                    ],
                ],
            ],
            'url filter: as-is' => [
                'configurationValues' => [
                    Configuration::KEY_URL_FILTER => 'foo',
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'INNER JOIN Job.website Website',
                    'WHERE 1 = 1',
                    'AND Website.canonicalUrl = :Website',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Website',
                        'value' => 'foo',
                    ],
                ],
            ],
            'url filter: wildcard' => [
                'configurationValues' => [
                    Configuration::KEY_URL_FILTER => 'foo*',
                ],
                'expectedDQLParts' => [
                    'SELECT Job',
                    'FROM App\Entity\Job\Job Job',
                    'INNER JOIN Job.website Website',
                    'WHERE 1 = 1',
                    'AND Website.canonicalUrl LIKE :Website',
                    'ORDER BY Job.id DESC',
                ],
                'expectedQueryParameters' => [
                    [
                        'name' => 'Website',
                        'value' => 'foo%',
                    ],
                ],
            ],
        ];
    }
}
