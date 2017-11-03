<?php

namespace Tests\ApiBundle\Unit\Model\JobList;

use SimplyTestable\ApiBundle\Entity\Job\Type;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\JobList\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configuration = new Configuration();
    }

    public function testConstruct()
    {
        $user = new User();
        $type = new Type();
        $state = new State();

        $configurationValues = [
            Configuration::KEY_LIMIT => 5,
            Configuration::KEY_OFFSET => 2,
            Configuration::KEY_USER => $user,
            Configuration::KEY_TYPES_TO_EXCLUDE => [$type],
            Configuration::KEY_STATES_TO_EXCLUDE => [$state],
            Configuration::KEY_JOB_IDS_TO_INCLUDE => [1, 2, 3],
            Configuration::KEY_JOB_IDS_TO_EXCLUDE => [4, 5, 6],
            Configuration::KEY_URL_FILTER => 'foo',
        ];

        $this->configuration = new Configuration($configurationValues);

        $this->assertEquals(
            $configurationValues[Configuration::KEY_LIMIT],
            $this->configuration->getLimit()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_OFFSET],
            $this->configuration->getOffset()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_USER],
            $this->configuration->getUser()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_TYPES_TO_EXCLUDE],
            $this->configuration->getTypesToExclude()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_STATES_TO_EXCLUDE],
            $this->configuration->getStatesToExclude()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_JOB_IDS_TO_INCLUDE],
            $this->configuration->getJobIdsToInclude()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_JOB_IDS_TO_EXCLUDE],
            $this->configuration->getJobIdsToExclude()
        );

        $this->assertEquals(
            $configurationValues[Configuration::KEY_URL_FILTER],
            $this->configuration->getUrlFilter()
        );
    }

    /**
     * @dataProvider limitDataProvider
     *
     * @param int $limit
     * @param int $expectedLimit
     */
    public function testLimit($limit, $expectedLimit)
    {
        if (!is_null($limit)) {
            $this->configuration->setLimit($limit);
        }

        $this->assertEquals($expectedLimit, $this->configuration->getLimit());
    }

    /**
     * @return array
     */
    public function limitDataProvider()
    {
        return [
            'no limit specified' => [
                'limit' => null,
                'expectedLimit' => Configuration::DEFAULT_LIMIT,
            ],
            'less than min limit' => [
                'limit' => Configuration::MIN_LIMIT - 1,
                'expectedLimit' => Configuration::MIN_LIMIT,
            ],
            'greater than max limit' => [
                'limit' => Configuration::MAX_LIMIT + 1,
                'expectedLimit' => Configuration::MAX_LIMIT,
            ],
            'min limit' => [
                'limit' => Configuration::MIN_LIMIT,
                'expectedLimit' => Configuration::MIN_LIMIT,
            ],
            'max limit' => [
                'limit' => Configuration::MAX_LIMIT,
                'expectedLimit' => Configuration::MAX_LIMIT,
            ],
            'greater than min limit, less than max limit' => [
                'limit' => Configuration::MIN_LIMIT + 1,
                'expectedLimit' => Configuration::MIN_LIMIT + 1,
            ],
        ];
    }

    /**
     * @dataProvider offsetDataProvider
     *
     * @param int $offset
     * @param int $expectedOffset
     */
    public function testOffset($offset, $expectedOffset)
    {
        if (!is_null($offset)) {
            $this->configuration->setOffset($offset);
        }

        $this->assertEquals($expectedOffset, $this->configuration->getOffset());
    }

    /**
     * @return array
     */
    public function offsetDataProvider()
    {
        return [
            'no offset specified' => [
                'offset' => null,
                'expectedOffset' => Configuration::DEFAULT_OFFSET,
            ],
            'less than min offset' => [
                'offset' => Configuration::MIN_OFFSET - 1,
                'expectedOffset' => Configuration::MIN_OFFSET,
            ],
            'greater than min offset' => [
                'offset' => Configuration::MIN_OFFSET + 1,
                'expectedOffset' => Configuration::MIN_OFFSET + 1,
            ],
        ];
    }

    /**
     * @dataProvider userDataProvider
     *
     * @param User $user
     * @param User $expectedUser
     */
    public function testUser($user, $expectedUser)
    {
        if (!is_null($user)) {
            $this->configuration->setUser($user);
        }

        $this->assertEquals($expectedUser, $this->configuration->getUser());
    }

    /**
     * @return array
     */
    public function userDataProvider()
    {
        $user = new User();

        return [
            'no user' => [
                'user' => null,
                'expectedUser' => null,
            ],
            'has user' => [
                'user' => $user,
                'expectedUser' => $user,
            ],
        ];
    }

    /**
     * @dataProvider typesToExcludeDataProvider
     *
     * @param Type[] $typesToExclude
     * @param Type[] $expectedTypesToExclude
     */
    public function testTypesToExclude($typesToExclude, $expectedTypesToExclude)
    {
        $this->configuration->setTypesToExclude($typesToExclude);

        $this->assertEquals($expectedTypesToExclude, $this->configuration->getTypesToExclude());
    }

    /**
     * @return array
     */
    public function typesToExcludeDataProvider()
    {
        $type = new Type();

        return [
            'none' => [
                'typesToExclude' => [],
                'expectedTypesToExclude' => [],
            ],
            'not right type' => [
                'typesToExclude' => [
                    new \StdClass(),
                    1,
                    'foo',
                ],
                'expectedTypesToExclude' => [],
            ],
            'has types' => [
                'typesToExclude' => [
                    $type,
                ],
                'expectedTypesToExclude' => [
                    $type,
                ],
            ],
        ];
    }

    /**
     * @dataProvider statesToExcludeDataProvider
     *
     * @param State[] $statesToExclude
     * @param State[] $expectedStatesToExclude
     */
    public function testStatesToExclude($statesToExclude, $expectedStatesToExclude)
    {
        $this->configuration->setStatesToExclude($statesToExclude);

        $this->assertEquals($expectedStatesToExclude, $this->configuration->getStatesToExclude());
    }

    /**
     * @return array
     */
    public function statesToExcludeDataProvider()
    {
        $state = new State();

        return [
            'none' => [
                'statesToExclude' => [],
                'expectedStatesToExclude' => [],
            ],
            'not right type' => [
                'statesToExclude' => [
                    new \StdClass(),
                    1,
                    'foo',
                ],
                'expectedStatesToExclude' => [],
            ],
            'has states' => [
                'statesToExclude' => [
                    $state,
                ],
                'expectedStatesToExclude' => [
                    $state,
                ],
            ],
        ];
    }

    /**
     * @dataProvider jobIdsToIncludeDataProvider
     *
     * @param int[] $jobIds
     * @param int[] $expectedJobIds
     */
    public function testJobIdsToInclude($jobIds, $expectedJobIds)
    {
        $this->configuration->setJobIdsToInclude($jobIds);

        $this->assertEquals($expectedJobIds, $this->configuration->getJobIdsToInclude());
    }

    /**
     * @return array
     */
    public function jobIdsToIncludeDataProvider()
    {
        return [
            'none' => [
                'jobIds' => [],
                'expectedJobIds' => [],
            ],
            'has' => [
                'jobIds' => [1, 2, 3],
                'expectedJobIds' => [1, 2, 3],
            ],
        ];
    }

    /**
     * @dataProvider jobIdsToExcludeDataProvider
     *
     * @param int[] $jobIds
     * @param int[] $expectedJobIds
     */
    public function testJobIdsToExclude($jobIds, $expectedJobIds)
    {
        $this->configuration->setJobIdsToExclude($jobIds);

        $this->assertEquals($expectedJobIds, $this->configuration->getJobIdsToExclude());
    }

    /**
     * @return array
     */
    public function jobIdsToExcludeDataProvider()
    {
        return [
            'none' => [
                'jobIds' => [],
                'expectedJobIds' => [],
            ],
            'has' => [
                'jobIds' => [1, 2, 3],
                'expectedJobIds' => [1, 2, 3],
            ],
        ];
    }

    /**
     * @dataProvider urlFilterDataProvider
     *
     * @param string $urlFilter
     * @param string $expectedUrlFilter
     */
    public function testUrlFilter($urlFilter, $expectedUrlFilter)
    {
        $this->configuration->setUrlFilter($urlFilter);

        $this->assertEquals($expectedUrlFilter, $this->configuration->getUrlFilter());
    }

    /**
     * @return array
     */
    public function urlFilterDataProvider()
    {
        return [
            'none' => [
                'urlFilter' => null,
                'expectedUrlFilter' => null,
            ],
            'has' => [
                'urlFilter' => 'foo',
                'expectedUrlFilter' => 'foo',
            ],
        ];
    }
}
