<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;

class LoadTaskTypeClasses extends AbstractFixture implements OrderedFixtureInterface
{
    private $taskTypeClasses = array(
        'verification' => 'For the verification of quality aspects such as the presence of a robots.txt file',
        'discovery' => 'For the discovery of information, such as collecting all unique URLs within a given page',
        'validation' => 'For the validation of syntactial correctness, such as HTML or CSS validation'
    );

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(TaskTypeClass::class);

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
}
