<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties\GetForUser;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use Tests\ApiBundle\Factory\UserFactory;

class UserHasTest extends ServiceTest {

    protected function setUp() {
        parent::setUp();

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $accountPlanRepository = $entityManager->getRepository(Plan::class);

        /* @var Plan $plan */
        $plan = $accountPlanRepository->findOneBy([
            'name' => 'personal',
        ]);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();

        $this->getUserPostActivationPropertiesService()->create($user, $plan);

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsOfCorrectType() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\UserPostActivationProperties', $this->userPostActivationProperties);
    }

}
