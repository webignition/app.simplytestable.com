<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class ConfigurationService extends EntityService {

    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Configuration';

    /**
     * @var TeamService
     */
    private $teamService;


    /**
     * @var User
     */
    private $user;

    /**
     * @param EntityManager $entityManager
     * @param TeamService $teamService
     */
    public function __construct(EntityManager $entityManager,TeamService $teamService) {
        parent::__construct($entityManager);
        $this->teamService = $teamService;
    }

    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user) {
        $this->user = $user;
        return $this;
    }


    /**
     * @param WebSite $website
     * @param JobType $type
     * @param TaskConfigurationCollection $taskConfigurationCollection
     * @param string $label
     * @param string $parameters
     * @return JobConfiguration
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function create(WebSite $website, JobType $type, TaskConfigurationCollection $taskConfigurationCollection, $label = '', $parameters = '') {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        $label = trim($label);

        if ($label == '') {
            throw new JobConfigurationServiceException(
                'Label cannot be empty',
                JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY
            );
        }

        if ($this->has($label)) {
            throw new JobConfigurationServiceException(
                'Label "' . $label . '" is not unique',
                JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
            );
        }

        if ($taskConfigurationCollection->isEmpty()) {
            throw new JobConfigurationServiceException(
                'TaskConfigurationCollection is empty',
                JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY
            );
        }


        if ($this->hasExisting($website, $type, $taskConfigurationCollection, $parameters)) {
            throw new JobConfigurationServiceException(
                'Matching configuration already exists',
                JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
            );
        }

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel($label);
        $jobConfiguration->setUser($this->user);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($type);
        $jobConfiguration->setParameters($parameters);

        $this->getManager()->persist($jobConfiguration);

        foreach ($taskConfigurationCollection->get() as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($jobConfiguration);
            $jobConfiguration->addTaskConfiguration($taskConfiguration);
            $this->getManager()->persist($taskConfiguration);
        }

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();

        return $jobConfiguration;
    }

    /**
     * @param $label
     * @throws JobConfigurationServiceException
     * @return null|JobConfiguration
     */
    public function get($label) {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        return $this->getEntityRepository()->findOneBy([
            'label' => $label,
            'user' => ($this->teamService->hasForUser($this->user)) ? $this->teamService->getPeopleForUser($this->user) : [$this->user]
        ]);
    }

    public function update($label, WebSite $website, JobType $type, TaskConfigurationCollection $taskConfigurationCollection, $parameters = '') {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        if (!$this->has($label)) {
            throw new JobConfigurationServiceException(
                'Configuration with label "' . $label . '" does not exist',
                JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION
            );
        }

        if ($this->hasExisting($website, $type, $taskConfigurationCollection, $parameters)) {
            throw new JobConfigurationServiceException(
                'Matching configuration already exists',
                JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
            );
        }

        $configuration = $this->get($label);
        $configuration->setWebsite($website);
        $configuration->setType($type);
        $configuration->setParameters($parameters);
        $configuration->getTaskConfigurations()->clear();

        foreach ($taskConfigurationCollection->get() as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($configuration);
            $configuration->addTaskConfiguration($taskConfiguration);
            $this->getManager()->persist($taskConfiguration);
        }

        $this->getManager()->persist($configuration);
        $this->getManager()->flush($configuration);

        return true;
    }

    public function delete($label) {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        if (!$this->has($label)) {
            throw new JobConfigurationServiceException(
                'Configuration with label "' . $label . '" does not exist',
                JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION
            );
        }

        $configuration = $this->get($label);
        $this->getManager()->remove($configuration);

        foreach ($configuration->getTaskConfigurations() as $taskConfiguration) {
            $this->getManager()->remove($taskConfiguration);
        }

        $this->getManager()->flush();
    }

    /**
     * @return JobConfiguration[]
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function getList() {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        return $this->getEntityRepository()->findBy([
            'user' => ($this->teamService->hasForUser($this->user)) ? $this->teamService->getPeopleForUser($this->user) : [$this->user]
        ]);
    }


    /**
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function removeAll() {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        if ($this->teamService->hasForUser($this->user)) {
            throw new JobConfigurationServiceException(
                'Unable to remove all; user is in a team',
                JobConfigurationServiceException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM
            );
        }

        $userJobConfigurations = $this->getEntityRepository()->findBy([
            'user' => $this->user
        ]);

        foreach ($userJobConfigurations as $userJobConfiguration) {
            /* @var $userJobConfiguration JobConfiguration */
            foreach ($userJobConfiguration->getTaskConfigurations() as $jobTaskConfiguration) {
                $this->getManager()->remove($jobTaskConfiguration);
            }

            $this->getManager()->remove($userJobConfiguration);
            $this->getManager()->flush($userJobConfiguration);
        }
    }


    /**
     * @param $label
     * @return bool
     */
    private function has($label) {
        return !is_null($this->get($label));
    }


    /**
     * @param WebSite $website
     * @param JobType $type
     * @param TaskConfigurationCollection $taskConfigurationCollection
     * @param string $parameters
     * @return bool
     */
    private function hasExisting(WebSite $website, JobType $type, TaskConfigurationCollection $taskConfigurationCollection, $parameters = '') {
        $jobConfigurations = $this->getEntityRepository()->findBy([
            'website' => $website,
            'type' => $type,
            'parameters' => $parameters,
            'user' => ($this->teamService->hasForUser($this->user)) ? $this->teamService->getPeopleForUser($this->user) : [$this->user]
        ]);

        foreach ($jobConfigurations as $jobConfiguration) {
            /* @var $jobConfiguration JobConfiguration */
            if ($taskConfigurationCollection->equals($jobConfiguration->getTaskConfigurationsAsCollection())) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    private function hasUser() {
        return $this->user instanceof User;
    }



}