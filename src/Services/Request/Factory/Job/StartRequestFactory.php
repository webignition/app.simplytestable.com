<?php

namespace App\Services\Request\Factory\Job;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\TaskConfiguration;
use App\Entity\Job\Type as JobType;
use App\Entity\Task\Type\Type as TaskType;
use App\Entity\WebSite;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use App\Request\Job\StartRequest;
use App\Services\JobTypeService;
use App\Services\WebSiteService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class StartRequestFactory
{
    const PARAMETER_JOB_TYPE = 'type';
    const PARAMETER_SITE_ROOT_URL = 'site_root_url';
    const PARAMETER_TEST_TYPES = 'test-types';
    const PARAMETER_TEST_TYPE_OPTIONS = 'test-type-options';
    const PARAMETER_JOB_PARAMETERS = 'parameters';
    const PARAMETER_URL = 'url';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $requestPayload;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebSiteService
     */
    private $websiteService;

    /**
     * @var EntityRepository
     */
    private $taskTypeRepository;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $entityManager
     * @param WebSiteService $websiteService
     * @param JobTypeService $jobTypeService
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        WebSiteService $websiteService,
        JobTypeService $jobTypeService
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->websiteService = $websiteService;
        $this->jobTypeService = $jobTypeService;

        $this->taskTypeRepository = $entityManager->getRepository(TaskType::class);
    }

    /**
     * @param Request $request
     *
     * @return StartRequest
     */
    public function create(Request $request)
    {
        $this->request = $request;
        $this->requestPayload = $request->request;

        return new StartRequest(
            $this->tokenStorage->getToken()->getUser(),
            $this->getWebsiteFromRequest(),
            $this->getJobTypeFromRequest(),
            $this->getTaskConfigurationCollectionFromRequest(),
            $this->getJobParametersFromRequest()
        );
    }

    /**
     * @return WebSite
     */
    private function getWebsiteFromRequest()
    {
        return $this->websiteService->get($this->requestPayload->get(self::PARAMETER_URL));
    }

    /**
     * @return JobType
     */
    private function getJobTypeFromRequest()
    {
        $requestJobType = $this->requestPayload->get(self::PARAMETER_JOB_TYPE);

        $jobType = $this->jobTypeService->get($requestJobType);
        if (empty($jobType)) {
            $jobType = $this->jobTypeService->getFullSiteType();
        }

        return $jobType;
    }

    /**
     * @return TaskConfigurationCollection
     */
    private function getTaskConfigurationCollectionFromRequest()
    {
        $collection = $this->getRequestTaskConfigurationCollection();

        if ($collection->isEmpty()) {
            $selectableTaskTypes = $this->taskTypeRepository->findBy([
                'selectable' => true,
            ]);

            foreach ($selectableTaskTypes as $taskType) {
                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($taskType);
                $collection->add($taskConfiguration);
            }
        }

        return $collection;
    }

    /**
     * @return TaskConfigurationCollection
     */
    private function getRequestTaskConfigurationCollection()
    {
        $collection = new TaskConfigurationCollection();

        if (!$this->requestPayload->has(self::PARAMETER_TEST_TYPES)) {
            return $collection;
        }

        if (!is_array($this->requestPayload->get(self::PARAMETER_TEST_TYPES))) {
            return $collection;
        }

        $selectableTaskTypes = $this->taskTypeRepository->findBy([
            'selectable' => true,
        ]);

        $requestTestTypes = $this->requestPayload->get(self::PARAMETER_TEST_TYPES);
        array_walk($requestTestTypes, function (&$item) {
            $item = strtolower($item);
        });

        foreach ($selectableTaskTypes as $taskType) {
            $isEnabled = in_array(strtolower($taskType), $requestTestTypes);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);
            $taskConfiguration->setOptions($this->getTaskTypeOptionsFromRequest($taskType));
            $taskConfiguration->setIsEnabled($isEnabled);

            $collection->add($taskConfiguration);
        }

        return $collection;
    }

    /**
     * @param TaskType $taskType
     * @return array
     */
    private function getTaskTypeOptionsFromRequest(TaskType $taskType)
    {
        if (!$this->requestPayload->has(self::PARAMETER_TEST_TYPE_OPTIONS)) {
            return [];
        }

        if (!is_array($this->requestPayload->get(self::PARAMETER_TEST_TYPE_OPTIONS))) {
            return [];
        }

        $requestTaskTypeOptions = $this->requestPayload->get(self::PARAMETER_TEST_TYPE_OPTIONS);

        foreach ($requestTaskTypeOptions as $requestTaskTypeName => $options) {
            $taskTypeName = strtolower(urldecode(strtolower($requestTaskTypeName)));

            if ($taskTypeName == strtolower($taskType->getName())) {
                return $options;
            }
        }

        return [];
    }

    /**
     * @return array
     */
    private function getJobParametersFromRequest()
    {
        if (!$this->requestPayload->has(self::PARAMETER_JOB_PARAMETERS)) {
            return [];
        }

        if (!is_array($this->requestPayload->get(self::PARAMETER_JOB_PARAMETERS))) {
            return [];
        }

        $parameters = [];
        $rawParameters = $this->requestPayload->get(self::PARAMETER_JOB_PARAMETERS);

        foreach ($rawParameters as $key => $value) {
            $parameters[urldecode(strtolower($key))] = $value;
        }

        return $parameters;
    }
}
