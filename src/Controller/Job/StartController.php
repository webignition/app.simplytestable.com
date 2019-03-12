<?php

namespace App\Controller\Job;

use App\Repository\JobRepository;
use App\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use App\Entity\Job\Job;
use App\Exception\Services\Job\Start\Exception as JobStartServiceException;
use App\Services\ApplicationStateService;
use App\Services\Job\StartService;
use App\Services\JobConfigurationFactory;
use App\Services\JobService;
use App\Services\Request\Factory\Job\StartRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use App\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class StartController
{
    private $router;
    private $applicationStateService;
    private $jobStartService;
    private $jobStartRequestFactory;
    private $jobConfigurationFactory;
    private $jobService;
    private $jobRepository;

    public function __construct(
        RouterInterface $router,
        ApplicationStateService $applicationStateService,
        StartService $jobStartService,
        StartRequestFactory $jobStartRequestFactory,
        JobConfigurationFactory $jobConfigurationFactory,
        JobService $jobService,
        JobRepository $jobRepository
    ) {
        $this->router = $router;
        $this->applicationStateService = $applicationStateService;
        $this->jobStartService = $jobStartService;
        $this->jobStartRequestFactory = $jobStartRequestFactory;
        $this->jobConfigurationFactory = $jobConfigurationFactory;
        $this->jobService = $jobService;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function startAction(Request $request)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobStartRequest = $this->jobStartRequestFactory->create($request);
        $jobConfiguration = $this->jobConfigurationFactory->createFromJobStartRequest($jobStartRequest);

        try {
            $job = $this->jobStartService->start($jobConfiguration);

            return $this->redirect(
                'job_job_status',
                [
                    'test_id' => $job->getId()
                ]
            );
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
    public function retestAction(
        Request $request,
        $site_root_url = '',
        $test_id
    ) {
        /* @var Job $job */
        $job = $this->jobRepository->find($test_id);
        if (is_null($job)) {
            throw new BadRequestHttpException();
        }

        if (!$this->jobService->isFinished($job)) {
            throw new BadRequestHttpException();
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

        $request->request->set('url', (string) $job->getWebsite());
        $request->request->set('type', $job->getType()->getName());
        $request->request->set('test-types', $taskTypeNames);
        $request->request->set('test-type-options', $taskTypeOptionsArray);

        return $this->startAction($request);
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
     * @param string $reason
     * @param AccountPlanConstraint|null $constraint
     *
     * @return RedirectResponse
     */
    private function rejectAndRedirect(
        JobConfiguration $jobConfiguration,
        $reason,
        AccountPlanConstraint $constraint = null
    ) {
        $job = $this->jobService->create(
            $jobConfiguration
        );

        $this->jobService->reject($job, $reason, $constraint);

        return $this->redirect(
            'job_job_status',
            [
                'test_id' => $job->getId()
            ]
        );
    }

    /**
     * @param string  $routeName
     * @param array $routeParameters
     *
     * @return RedirectResponse
     */
    private function redirect($routeName, $routeParameters = [])
    {
        $url = $this->router->generate(
            $routeName,
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new RedirectResponse($url);
    }
}
