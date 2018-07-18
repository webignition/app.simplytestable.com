<?php

namespace Tests\AppBundle\Functional\Entity\Task\Type;

use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Task\Type\TaskTypeClass;

class ClassTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $name = 'name-ɸ';
        $description = 'description-ɸ';

        $taskTypeClass = new TaskTypeClass();
        $taskTypeClass->setName($name);
        $taskTypeClass->setDescription($description);

        $entityManager->persist($taskTypeClass);
        $entityManager->flush();

        $taskTypeClassId = $taskTypeClass->getId();

        $entityManager->clear();

        $taskTypeClassRepository = $entityManager->getRepository(TaskTypeClass::class);

        $retrievedTaskType = $taskTypeClassRepository->find($taskTypeClassId);

        $this->assertEquals($name, $retrievedTaskType->getName());
        $this->assertEquals($description, $retrievedTaskType->getDescription());
    }
}
