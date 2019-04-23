<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Task\TaskType;

class LoadTaskTypes extends Fixture
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $taskTypeRepository = $manager->getRepository(TaskType::class);

        foreach ($this->taskTypes as $name => $properties) {
            $taskType = $taskTypeRepository->findOneBy([
                'name' => $name,
            ]);

            if (empty($taskType)) {
                $taskType = new TaskType();
            }

            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            $taskType->setSelectable($properties['selectable']);

            $manager->persist($taskType);
            $manager->flush();
        }
    }
}
