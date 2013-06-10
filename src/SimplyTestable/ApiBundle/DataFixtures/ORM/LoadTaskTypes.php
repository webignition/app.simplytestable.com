<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class LoadTaskTypes extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
    
    private $taskTypes = array(
        'HTML validation' => array(
            'description' => 'Validates the HTML markup for a given URL',
            'class' => 'verification',
            'selectable' => true
        )
    );
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $taskTypeClassRepository = $manager->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass');
        $taskTypeRepository = $manager->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type');
        
        foreach ($this->taskTypes as $name => $properties) {
            $taskType = $taskTypeRepository->findOneByName($name);
            
            if (is_null($taskType)) {
                $taskType = new TaskType();
            }
            
            $taskTypeClass = $taskTypeClassRepository->findOneByName($properties['class']);
            
            $taskType->setClass($taskTypeClass);
            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            $taskType->setSelectable($properties['selectable']);
            
            $manager->persist($taskType);
            $manager->flush();            
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 4; // the order in which fixtures will be loaded
    }
}
