<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        if (!$this->getUserService()->hasPublicUser()) {
            $user = new User();
            $user->setEmail('public@simplytestable.com');
            $user->setPlainPassword('public');
            $user->setUsername('public');        

            $userManager = $this->container->get('fos_user.user_manager');        
            $userManager->updateUser($user);

            $manipulator = $this->container->get('fos_user.util.user_manipulator');
            $manipulator->activate($user->getUsername());             
        }
        
        if (!$this->getUserService()->hasAdminUser()) {
            $user = new User();
            $user->setEmail($this->container->getParameter('admin_user_email'));
            $user->setPlainPassword($this->container->getParameter('admin_user_password'));
            $user->setUsername('admin'); 
            $user->addRole('role_admin');

            $userManager = $this->container->get('fos_user.user_manager');        
            $userManager->updateUser($user);

            $manipulator = $this->container->get('fos_user.util.user_manipulator');
            $manipulator->activate($user->getUsername());            
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */
    public function getUserService() {
        return $this->container->get('simplytestable.services.userservice');
    }
}
