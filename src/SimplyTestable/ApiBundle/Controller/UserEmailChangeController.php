<?php

namespace SimplyTestable\ApiBundle\Controller;

use Egulias\EmailValidator\EmailValidator;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserEmailChangeController extends ApiController
{
    /**
     * @param string $email_canonical
     * @param string $new_email
     *
     * @return Response
     */
    public function createAction($email_canonical, $new_email)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $emailCanonicalizer = $this->container->get('fos_user.util.email_canonicalizer');
        $userEmailChangeRequestService = $this->container->get('simplytestable.services.useremailchangerequestservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $new_email = $emailCanonicalizer->canonicalize($new_email);

        $user = $this->getUser();

        $userEmailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        /* @var UserEmailChangeRequest|null $existingRequest */
        $existingUserRequest = $userEmailChangeRequestRepository->findOneBy([
            'user' => $user,
        ]);

        if (!empty($existingUserRequest)) {
            if ($existingUserRequest->getNewEmail() === $new_email) {
                return new Response();
            }

            throw new ConflictHttpException();
        }

        $validator = new EmailValidator();
        $isEmailValid = $validator->isValid($new_email);

        if (!$isEmailValid) {
            throw new BadRequestHttpException();
        }

        if ($userService->exists($new_email)) {
            throw new ConflictHttpException();
        }

        $existingNewEmailRequest = $userEmailChangeRequestRepository->findOneBy([
            'newEmail' => $new_email,
        ]);

        if (!empty($existingNewEmailRequest)) {
            throw new ConflictHttpException();
        }

        $userEmailChangeRequestService->create($user, $new_email);

        return new Response();
    }

    /**
     * @param string $email_canonical
     *
     * @return JsonResponse|Response
     */
    public function getAction($email_canonical)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $user = $userService->findUserByEmail($email_canonical);
        if (empty($user)) {
            throw new NotFoundHttpException();
        }

        $userEmailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        $emailChangeRequest = $userEmailChangeRequestRepository->findOneBy([
            'user' => $user,
        ]);

        if (empty($emailChangeRequest)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($emailChangeRequest);
    }

    /**
     * @param $email_canonical
     *
     * @return Response
     */
    public function cancelAction($email_canonical)
    {
        $userEmailChangeRequestService = $this->container->get('simplytestable.services.useremailchangerequestservice');

        $user = $this->getUser();

        $userEmailChangeRequestService->removeForUser($user);

        return new Response();
    }

    /**
     * @param string $email_canonical
     * @param string $token
     *
     * @return Response
     */
    public function confirmAction($email_canonical, $token)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userEmailChangeRequestService = $this->container->get('simplytestable.services.useremailchangerequestservice');
        $user = $this->getUser();

        $emailChangeRequest = $userEmailChangeRequestService->findByUser($user);
        if (empty($emailChangeRequest)) {
            throw new NotFoundHttpException();
        }

        if ($token !== $emailChangeRequest->getToken()) {
            throw new BadRequestHttpException();
        }

        if ($userService->exists($emailChangeRequest->getNewEmail())) {
            $userEmailChangeRequestService->removeForUser($user);
            throw new ConflictHttpException();
        }

        $newEmail = $emailChangeRequest->getNewEmail();

        $user->setEmail($newEmail);
        $user->setEmailCanonical($newEmail);
        $user->setUsername($newEmail);
        $user->setUsernameCanonical($newEmail);

        $userService->updateUser($user);

        $userEmailChangeRequestService->removeForUser($user);

        return new Response();
    }
}
