<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\NormaliseLabels;

use SimplyTestable\ApiBundle\Model\Job\Configuration\Collection as JobConfigurationCollection;

class NewMemberHasNoJobConfigurationsTest extends TeamTest {

    /**
     * @var JobConfigurationCollection
     */
    private $memberJobConfigurationCollection;

    public function setUp() {
        parent::setUp();

        $this->getTeamMemberService()->add($this->team, $this->user1);

        $this->getJobConfigurationService()->setUser($this->leader);
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getTaskConfigurationCollection([
                'HTML validation' => [
                    'foo1' => 'bar'
                ]
            ]),
            'leader',
            'parameters'
        );


        $this->getJobConfigurationService()->setUser($this->user1);
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getTaskConfigurationCollection([
                'HTML validation' => [
                    'foo2' => 'bar'
                ]
            ]),
            'user1',
            'parameters'
        );

        $this->getTeamMemberService()->add($this->team, $this->user2);

        $this->getJobConfigurationService()->setUser($this->user2);
        $this->getJobConfigurationService()->normaliseLabels();
        $this->memberJobConfigurationCollection = $this->getJobConfigurationService()->getList();
    }


    public function testNewMemberHasTeamJobConfigurationCount() {
        $this->assertEquals(2, $this->memberJobConfigurationCollection->count());
    }


    public function testNewMemberHasTeamJobConfigurationsWithNoLabelModifications() {
        foreach ($this->memberJobConfigurationCollection as $jobConfiguration) {
            $this->assertTrue(in_array($jobConfiguration->getLabel(), ['leader', 'user1']));
        }
    }

}