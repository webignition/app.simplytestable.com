<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start\RequestAdapter;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Controller\ApiController;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpFoundation\Response;

class StartController extends ApiController
{
    /**
     * @param Request $request
     * @param string $site_root_url
     *
     * @return RedirectResponse|Response
     * @throws JobStartServiceException
     */
    public function startAction(Request $request, $site_root_url)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $jobStartService = $this->container->get('simplytestable.services.job.startservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if (!$request->attributes->has('site_root_url')) {
            $request->attributes->set('site_root_url', $site_root_url);
        }

        $requestAdapter = new RequestAdapter(
            $request,
            $this->get('simplytestable.services.websiteservice'),
            $this->get('simplytestable.services.jobtypeservice'),
            $this->get('simplytestable.services.tasktypeservice')
        );

        $jobConfiguration = $requestAdapter->getJobConfiguration();
        $jobConfiguration->setUser($this->getUser());

        try {
            $job = $jobStartService->start($jobConfiguration);

            return $this->redirect($this->generateUrl('job_job_status', array(
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
                'test_id' => $job->getId()
            )));
        } catch (JobStartServiceException $jobStartServiceException) {
            if ($jobStartServiceException->isUnroutableWebsiteException()) {
                return $this->rejectAsUnroutableAndRedirect($jobConfiguration);
            }
        } catch (UserAccountPlanEnforcementException $userAccountPlanEnforcementException) {
            return $this->rejectAsPlanLimitReachedAndRedirect(
                $jobConfiguration,
                $userAccountPlanEnforcementException->getAccountPlanConstraint()
            );
        }
    }

    /**
     * @param Request $request
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return RedirectResponse|Response
     */
    public function retestAction(Request $request, $site_root_url, $test_id)
    {
        $job = $this->getJobService()->getById($test_id);
        if (is_null($job)) {
            return $this->sendFailureResponse();
        }

        if (!$this->getJobService()->isFinished($job)) {
            return $this->sendFailureResponse();
        }

        $taskTypeNames = array();
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            $taskTypeNames[] = $taskType->getName();
        }

        $taskTypeOptionsArray = array();
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            $taskTypeNameKey = strtolower($taskTypeOptions->getTaskType()->getName());

            $taskTypeOptionsArray[$taskTypeNameKey] = $taskTypeOptions->getOptions();
        }

        $request->request->set('type', $job->getType()->getName());
        $request->request->set('test-types', $taskTypeNames);
        $request->request->set('test-type-options', $taskTypeOptionsArray);

        return $this->startAction($request, $job->getWebsite()->getCanonicalUrl());
    }

    /**
     * @param JobConfiguration $jobConfiguration
     *
     * @return RedirectResponse
     */
    private function rejectAsUnroutableAndRedirect(JobConfiguration $jobConfiguration)
    {
        return $this->rejectAndRedirect($jobConfiguration, 'unroutable');
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param AccountPlanConstraint $constraint
     *
     * @return RedirectResponse
     */
    private function rejectAsPlanLimitReachedAndRedirect(
        JobConfiguration $jobConfiguration,
        AccountPlanConstraint $constraint
    ) {
        return $this->rejectAndRedirect($jobConfiguration, 'plan-constraint-limit-reached', $constraint);
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param $reason
     * @param AccountPlanConstraint|null $constraint
     *
     * @return RedirectResponse
     */
    private function rejectAndRedirect(
        JobConfiguration $jobConfiguration,
        $reason,
        AccountPlanConstraint $constraint = null
    ) {
        $job = $this->getJobService()->create(
            $jobConfiguration
        );

        $jobService = $this->container->get('simplytestable.services.jobservice');

        $jobService->reject($job, $reason, $constraint);

        return $this->redirect($this->generateUrl('job_job_status', array(
            'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            'test_id' => $job->getId()
        )));
    }

    /**
     * @return boolean
     */
    private function isTestEnvironment()
    {
        return $this->get('kernel')->getEnvironment() == 'test';
    }

    /**
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    public function getUser()
    {
        if (!$this->isTestEnvironment()) {
            return parent::getUser();
        }

        if (is_null($this->getRequestValue('user'))) {
            return $this->getUserService()->getPublicUser();
        }

        return $this->getUserService()->findUserByEmail($this->getRequestValue('user'));
    }

    /**
     * @return JobService
     */
    private function getJobService()
    {
        return $this->get('simplytestable.services.jobservice');
    }
}
