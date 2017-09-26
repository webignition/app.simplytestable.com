<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DifferentUsersTest extends ActionTest {


    /**
     * @var RedirectResponse[]
     */
    private $responses;

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user1 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $user2 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $methodName = $this->getActionNameFromRouter([
            'site_root_url' => self::DEFAULT_CANONICAL_URL
        ]);

        $this->responses[] = $this->getCurrentController([
            'user' => $user1
        ])->$methodName(
            $this->container->get('request'),
            self::DEFAULT_CANONICAL_URL
        );

        $this->responses[] = $this->getCurrentController([
            'user' => $user2
        ])->$methodName(
            $this->container->get('request'),
            self::DEFAULT_CANONICAL_URL
        );

        $this->responses[] = $this->getCurrentController([
            'user' => $user1
        ])->$methodName(
            $this->container->get('request'),
            self::DEFAULT_CANONICAL_URL
        );
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