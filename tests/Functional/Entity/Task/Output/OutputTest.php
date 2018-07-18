<?php

namespace App\Tests\Functional\Entity\Task\Output;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TaskOutputRepository;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;

class TaskTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->taskOutputRepository = $this->entityManager->getRepository(Output::class);
    }

    public function testUtf8Output()
    {
        $outputValue = 'ɸ';

        $output = new Output();
        $output->setOutput($outputValue);

        $this->entityManager->persist($output);
        $this->entityManager->flush();

        $outputId = $output->getId();

        $this->entityManager->clear();

        $this->assertEquals($outputValue, $this->taskOutputRepository->find($outputId)->getOutput());
    }

    public function testUtf8ContentType()
    {
        $typeValue = 'ɸ';

        $contentType = new InternetMediaType();
        $contentType->setType($typeValue);
        $contentType->setSubtype($typeValue);

        $output = new Output();
        $output->setOutput('');
        $output->setContentType($contentType);


        $this->entityManager->persist($output);
        $this->entityManager->flush();

        $outputId = $output->getId();

        $this->entityManager->clear();
        $this->assertEquals(
            $typeValue . '/' . $typeValue,
            $this->taskOutputRepository->find($outputId)->getContentType()
        );
    }

    public function testUtf8Hash()
    {
        $hash = 'ɸ';

        $output = new Output();
        $output->setOutput('');
        $output->setHash($hash);

        $this->entityManager->persist($output);
        $this->entityManager->flush();

        $outputId = $output->getId();

        $this->entityManager->clear();

        $this->assertEquals($hash, $this->taskOutputRepository->find($outputId)->getHash());
    }
}
