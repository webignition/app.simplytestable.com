<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Cron\Validator\CrontabValidator;
use Cron\Exception\InvalidPatternException;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ScheduledJobController extends Controller
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $cronModifierValidationService = $this->container->get(
            'simplytestable.services.scheduledjob.cronmodifier.validationservice'
        );

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $requestData = $request->request;

        $requestJobConfigurationLabel = rawurldecode(trim($requestData->get('job-configuration')));

        if (empty($requestJobConfigurationLabel)) {
            throw new BadRequestHttpException('"job-configuration" missing');
        }

        $requestSchedule = rawurldecode(trim($requestData->get('schedule')));

        if (empty($requestSchedule)) {
            throw new BadRequestHttpException('"schedule" missing');
        }

        $user = $this->getUser();

        if ($userService->isSpecialUser($user)) {
            return Response::create('', 400, [
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 99,
                    'message' => 'Special users cannot create scheduled jobs'
                ])
            ]);
        }

        $jobConfiguration = $jobConfigurationService->get($requestJobConfigurationLabel);

        if (empty($jobConfiguration)) {
            return Response::create('', 400, [
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 98,
                    'message' => 'Unknown job configuration "' . $requestJobConfigurationLabel . '"'
                ])
            ]);
        }

        try {
            $scheduleValidator = new CrontabValidator();
            $scheduleValidator->validate($requestSchedule);
        } catch (InvalidPatternException $invalidPatternException) {
            return Response::create('', 400, [
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 97,
                    'message' => 'Malformed schedule "' . $requestSchedule . '"'
                ])
            ]);
        }

        $requestScheduleModifier = trim($requestData->get('schedule-modifier'));
        if (empty($requestScheduleModifier)) {
            $requestScheduleModifier = null;
        }

        if (!$cronModifierValidationService->isValid($requestScheduleModifier)) {
            return Response::create('', 400, [
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 96,
                    'message' => 'Malformed schedule modifier "' . $requestScheduleModifier . '"'
                ])
            ]);
        }

        try {
            $scheduledJob = $scheduledJobService->create(
                $jobConfiguration,
                $request->request->get('schedule'),
                $request->request->get('schedule-modifier'),
                $requestData->getBoolean('is-recurring')
            );

            return $this->redirect($this->generateUrl(
                'scheduledjob_get',
                ['id' => $scheduledJob->getId()]
            ));
        } catch (ScheduledJobException $scheduledJobException) {
            return Response::create('', 400, [
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => $scheduledJobException->getCode(),
                    'message' => $scheduledJobException->getMessage()
                ])
            ]);
        }
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            throw new NotFoundHttpException();
        }

        $scheduledJobService->delete($scheduledJob);

        return new Response();
    }

    /**
     * @param int $id
     *
     * @return JsonResponse|Response
     */
    public function getAction($id)
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($scheduledJob);
    }

    /**
     * @return JsonResponse|Response
     */
    public function listAction()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        return new JsonResponse($scheduledJobService->getList());
    }
}
