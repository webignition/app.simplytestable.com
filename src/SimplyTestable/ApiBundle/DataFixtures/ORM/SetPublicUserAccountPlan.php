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
        $user = $this->getUserService()->getPublicUser();
        
        if (!$this->getUserAccountPlanService()->hasForUser($user)) {
            $plan = $this->getAccountPlanService()->find('public');
            $this->getUserAccountPlanService()->create($user, $plan);        
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 7; // the order in which fixtures will be loaded
    }
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->container->get('simplytestable.services.useraccountplanservice');
    }     
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService
     */
    private function getAccountPlanService() {
        return $this->container->get('simplytestable.services.accountplanservice');
    }    
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */
    private function getUserService() {
        return $this->container->get('simplytestable.services.userservice');
    }    
}
