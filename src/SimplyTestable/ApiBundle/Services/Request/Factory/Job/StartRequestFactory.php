<?php

namespace SimplyTestable\ApiBundle\Services\Request\Factory\Job;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Request\Job\StartRequest;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
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

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $requestAttributes;

    /**
     * @var ParameterBag
     */
    private $requestPayload;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var WebSiteService
     */
    private $websiteService;

    /**
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManager $entityManager
     * @param WebSiteService $websiteService
     */
    public function __construct(
        Request $request,
        TokenStorageInterface $tokenStorage,
        EntityManager $entityManager,
        WebSiteService $websiteService
    ) {
        $this->request = $request;
        $this->requestAttributes = $request->attributes;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->websiteService = $websiteService;

        if (0 === $request->request->count() && 0 === $request->query->count()) {
            $this->requestPayload = new ParameterBag();
        } elseif ($request->request->count()) {
            $this->requestPayload = $request->request;
        } elseif ($request->query->count()) {
            $this->requestPayload = $request->query;
        }
    }

    /**
     * @return StartRequest
     */
    public function create()
    {
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
        return $this->websiteService->fetch($this->requestAttributes->get(self::PARAMETER_SITE_ROOT_URL));
    }

    /**
     * @return JobType
     */
    private function getJobTypeFromRequest()
    {
        $requestJobType = $this->requestPayload->get(self::PARAMETER_JOB_TYPE);

        $jobTypeRepository = $this->entityManager->getRepository(JobType::class);

        $jobType = $jobTypeRepository->findOneBy([
            'name' => $requestJobType,
        ]);

        if (empty($jobType)) {
            $jobType = $jobTypeRepository->findOneBy([
                'name' => JobTypeService::FULL_SITE_NAME,
            ]);
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
            $taskTypeRepository = $this->entityManager->getRepository(TaskType::class);
            $selectableTaskTypes = $taskTypeRepository->findBy([
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

        $requestTestTypes = $this->requestPayload->get(self::PARAMETER_TEST_TYPES);

        $taskTypeRepository = $this->entityManager->getRepository(TaskType::class);

        foreach ($requestTestTypes as $taskTypeName) {
            $taskType = $taskTypeRepository->findOneBy([
                'name' => $taskTypeName,
            ]);

            if (!empty($taskType)) {
                if ($taskType->isSelectable()) {
                    $taskConfiguration = new TaskConfiguration();
                    $taskConfiguration->setType($taskType);
                    $taskConfiguration->setOptions($this->getTaskTypeOptionsFromRequest($taskType));
                    $collection->add($taskConfiguration);
                }
            }
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
