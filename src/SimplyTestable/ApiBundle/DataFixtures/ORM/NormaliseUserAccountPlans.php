<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;

class NormaliseUserAccountPlans extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanRepository = $manager->getRepository(UserAccountPlan::class);

        $userAccountPlans = $userAccountPlanRepository->findAll();

        foreach ($userAccountPlans as $userAccountPlan) {
            if ($userService->isSpecialUser($userAccountPlan->getUser())) {
                continue;
            }

            /* @var $userAccountPlan UserAccountPlan */
            $isModified = false;

            if (is_null($userAccountPlan->getIsActive())) {
                if ($userAccountPlanService->countForUser($userAccountPlan->getUser()) === 1) {
                    $userAccountPlan->setIsActive(true);
                    $isModified = true;
                }
            }

            if (is_null($userAccountPlan->getStartTrialPeriod())) {
                $userAccountPlan->setStartTrialPeriod($this->container->getParameter('default_trial_period'));
                $isModified = true;
            }

            if ($isModified === true) {
                $manager->persist($userAccountPlan);
                $manager->flush();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 8; // the order in which fixtures will be loaded
    }
}
