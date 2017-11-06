<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadTaskTypes extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $taskTypes = array(
        'HTML validation' => array(
            'description' => 'Validates the HTML markup for a given URL',
            'class' => 'verification',
            'selectable' => true
        ),
        'CSS validation' => array(
            'description' => 'Validates the CSS related to a given web document URL',
            'class' => 'verification',
            'selectable' => true
        ),
        'JS static analysis' => array(
            'description' => 'JavaScript static code analysis (via jslint)',
            'class' => 'verification',
            'selectable' => true
        ),
        'URL discovery' => array(
            'description' => 'Discover in-scope URLs from the anchors within a given URL',
            'class' => 'discovery',
            'selectable' => false
        ),
        'Link integrity' => array(
            'description' => 'Check links in a HTML document and determine those that don\'t work',
            'class' => 'verification',
            'selectable' => true
        ),
    );

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
        $taskTypeClassRepository = $this->container->get('simplytestable.repository.tasktypeclass');
        $taskTypeRepository = $manager->getRepository(TaskType::class);

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
