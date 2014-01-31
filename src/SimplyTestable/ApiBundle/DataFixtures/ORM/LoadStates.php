<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\State;

class LoadStates extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
    
    private $stateDetails = array(
        'job-completed' => null,
        'job-in-progress' => 'job-completed',
        'job-queued' => 'job-in-progress',
        'job-preparing' => 'job-queued',
        'job-new' => 'job-preparing',
        'task-completed' => null,
        'task-in-progress' => 'task-completed',
        'task-queued' => 'task-in-progress',
        'job-cancelled' => null,
        'task-cancelled' => null,
        'worker-activation-request-verified' => null,
        'worker-activation-request-failed' => null,
        'worker-activation-request-awaiting-verification' => 'worker-activation-request-verified',
        'task-awaiting-cancellation' => null,
        'job-failed-no-sitemap' => null,
        'task-queued-for-assignment' => null,
        'task-failed-no-retry-available' => null,
        'task-failed-retry-available' => null,
        'task-failed-retry-limit-reached' => null,
        'task-skipped' => null,
        'worker-active' => null,
        'worker-deleted' => null,
        'worker-offline' => null,
        'worker-unactivated' => null,    
        'job-rejected' => null,
        'job-resolving' => null
    );  
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->stateDetails as $name => $nextStateName) {
            if (!$this->getStateService()->has($name)) {
                $state = new State();
                $state->setName($name);
                
                if (!is_null($nextStateName)) {
                    $state->setNextState($this->getStateService()->find($nextStateName));
                }
     
                $manager->persist($state);
                $manager->flush();                  
            }
        }
        
        
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */
    public function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }
}
