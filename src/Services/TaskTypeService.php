<?php
namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Task\Type\Type as TaskType;

class TaskTypeService
{
    const HTML_VALIDATION_TYPE = 'HTML validation';
    const CSS_VALIDATION_TYPE = 'CSS validation';
    const JS_STATIC_ANALYSIS_TYPE = 'JS static analysis';
    const URL_DISCOVERY_TYPE = 'URL discovery';
    const LINK_INTEGRITY_TYPE = 'Link integrity';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $taskTypeRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->taskTypeRepository = $entityManager->getRepository(TaskType::class);
    }

    /**
     * @return TaskType
     */
    public function getHtmlValidationTaskType()
    {
        return $this->get(self::HTML_VALIDATION_TYPE);
    }

    /**
     * @return TaskType
     */
    public function getCssValidationTaskType()
    {
        return $this->get(self::CSS_VALIDATION_TYPE);
    }

    /**
     * @return TaskType
     */
    public function getJsStaticAnalysisTaskType()
    {
        return $this->get(self::JS_STATIC_ANALYSIS_TYPE);
    }

    /**
     * @return TaskType
     */
    public function getUrlDiscoveryTaskType()
    {
        return $this->get(self::URL_DISCOVERY_TYPE);
    }

    /**
     * @param string $name
     *
     * @return TaskType
     */
    public function get($name)
    {
        /* @var TaskType $taskType */
        $taskType = $this->taskTypeRepository->findOneBy([
            'name' => $name
        ]);

        return $taskType;
    }
}
