<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SetPublicUserAccountPlan extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $accountPlanService = $this->container->get('simplytestable.services.accountplan');

        $user = $userService->getPublicUser();

        $userAccountPlan = $userAccountPlanService->getForUser($user);

        if (!empty($userAccountPlan)) {
            $plan = $accountPlanService->get('public');
            $userAccountPlanService->subscribe($user, $plan);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 7; // the order in which fixtures will be loaded
    }
}
