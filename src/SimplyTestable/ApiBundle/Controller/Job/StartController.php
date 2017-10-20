<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Controller\ApiController;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class StartController extends ApiController
{
    /**
     * @return RedirectResponse|Response
     * @throws JobStartServiceException
     */
    public function startAction()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $jobStartService = $this->container->get('simplytestable.services.job.startservice');
        $jobStartRequestFactory = $this->container->get('simplytestable.services.request.factory.job.start');
        $jobConfigurationFactory = $this->container->get('simplytestable.services.jobconfiguration.factory');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobStartRequest = $jobStartRequestFactory->create();
        $jobConfiguration = $jobConfigurationFactory->createFromJobStartRequest($jobStartRequest);

        try {
            $job = $jobStartService->start($jobConfiguration);

            return $this->redirect($this->generateUrl('job_job_status', array(
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
                'test_id' => $job->getId()
            )));
        } catch (JobStartServiceException $jobStartServiceException) {
            return $this->rejectAsUnroutableAndRedirect($jobConfiguration);
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
        $request->attributes->set('site_root_url', $site_root_url);

        return $this->startAction();
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
     * @return JobService
     */
    private function getJobService()
    {
        return $this->get('simplytestable.services.jobservice');
    }
}
