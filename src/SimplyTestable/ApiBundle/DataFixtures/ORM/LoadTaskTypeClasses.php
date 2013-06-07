<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;

class LoadTaskTypeClasses extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
    
    private $taskTypeClasses = array(
        'verification' => 'For the verification of quality aspects such as HTML validity, CSS validity or the presence of a robot.txt file',
        'discovery' => 'For the discovery of information, such as collecting all unique URLs within a given page'
    ); 
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass');
        
        foreach ($this->taskTypeClasses as $name => $description) {
            if (is_null($repository->findOneByName($name))) {
                $taskTypeClass = new TaskTypeClass();
                $taskTypeClass->setName($name);
                $taskTypeClass->setDescription($description);

                $manager->persist($taskTypeClass);
                $manager->flush();                 
            }            
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */
    public function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }
}
