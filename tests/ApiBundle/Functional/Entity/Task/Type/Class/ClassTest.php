<?php

namespace Tests\ApiBundle\Functional\Entity\Task\Type;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;

class ClassTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $name = 'name-ɸ';
        $description = 'description-ɸ';

        $taskTypeClass = new TaskTypeClass();
        $taskTypeClass->setName($name);
        $taskTypeClass->setDescription($description);

        $entityManager->persist($taskTypeClass);
        $entityManager->flush();

        $taskTypeClassId = $taskTypeClass->getId();

        $entityManager->clear();

        $taskTypeClassRepository = $this->container->get('simplytestable.repository.tasktypeclass');

        $retrievedTaskType = $taskTypeClassRepository->find($taskTypeClassId);

        $this->assertEquals($name, $retrievedTaskType->getName());
        $this->assertEquals($description, $retrievedTaskType->getDescription());
    }
}
