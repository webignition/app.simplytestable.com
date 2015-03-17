<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

use Symfony\Component\HttpFoundation\RedirectResponse;

class DifferentUsersTest extends ActionTest {


    /**
     * @var RedirectResponse[]
     */
    private $responses;

    public function setUp() {
        parent::setUp();

        $user1 = $this->createAndActivateUser('user1@example.com');
        $user2 = $this->createAndActivateUser('user2@example.com');

        $methodName = $this->getActionNameFromRouter([
            'site_root_url' => self::DEFAULT_CANONICAL_URL
        ]);

        $this->responses[] = $this->getCurrentController([
            'user' => $user1
        ])->$methodName(self::DEFAULT_CANONICAL_URL);

        $this->responses[] = $this->getCurrentController([
            'user' => $user2
        ])->$methodName(self::DEFAULT_CANONICAL_URL);

        $this->responses[] = $this->getCurrentController([
            'user' => $user1
        ])->$methodName(self::DEFAULT_CANONICAL_URL);
    }


    public function testHasCorrectNumberOfResponses() {
        $this->assertEquals(3, count($this->responses));
    }


    public function testResponsesForDifferentUsersAreNotTheSame() {
        $this->assertNotEquals($this->responses[0]->getTargetUrl(), $this->responses[1]->getTargetUrl());
    }


    public function testResponsesForSameUsersTheSame() {
        $this->assertEquals($this->responses[0]->getTargetUrl(), $this->responses[2]->getTargetUrl());
    }
    
}