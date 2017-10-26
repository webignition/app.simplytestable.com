<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task\Type;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;

class ClassTest extends BaseSimplyTestableTestCase
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

        $taskTypeClassRepository = $entityManager->getRepository(TaskTypeClass::class);

        $retrievedTaskType = $taskTypeClassRepository->find($taskTypeClassId);

        $this->assertEquals($name, $retrievedTaskType->getName());
        $this->assertEquals($description, $retrievedTaskType->getDescription());
    }
}
