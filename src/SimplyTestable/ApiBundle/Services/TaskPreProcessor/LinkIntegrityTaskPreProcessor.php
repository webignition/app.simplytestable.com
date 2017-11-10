<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\TooManyRedirectsException;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\WebResource\Exception\Exception as WebResourceException;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebResource;
use webignition\WebResource\WebPage\WebPage;

class LinkIntegrityTaskPreProcessor implements TaskPreprocessorInterface
{
    const HTTP_USER_AGENT = 'ST Link integrity task pre-processor';

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var WebResourceService
     */
    private $webResourceService;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param TaskService $taskService
     * @param WebResourceService $webResourceService
     * @param HttpClientService $httpClientService
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        TaskService $taskService,
        WebResourceService $webResourceService,
        HttpClientService $httpClientService,
        LoggerInterface $logger,
        StateService $stateService,
        EntityManagerInterface $entityManager
    ) {
        $this->taskService = $taskService;
        $this->webResourceService = $webResourceService;
        $this->httpClientService = $httpClientService;
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * @param Type $taskType
     *
     * @return bool
     */
    public function handles(Type $taskType)
    {
        return $taskType->getName() === TaskTypeService::LINK_INTEGRITY_TYPE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function process(Task $task)
    {
        $rawTaskOutputs = $this->taskRepository->findOutputByJobAndType($task);

        if (empty($rawTaskOutputs)) {
            return false;
        }

        $webResource = $this->getWebResource($task);
        if (!$webResource instanceof WebPage) {
            return false;
        }

        $links = $this->findWebResourceLinks($task, $webResource);

        if (empty($links)) {
            $output = $this->createOutput(null);
            $this->completeTask($task, $output);

            return true;
        }

        $existingLinkIntegrityResults = $this->getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs);

        $linkIntegrityResults = $this->getLinkIntegrityResultsFromExistingResults(
            $links,
            $existingLinkIntegrityResults
        );

        $linkCount = count($links);
        $linkIntegrityResultCount = count($linkIntegrityResults);

        if (!empty($linkIntegrityResults)) {
            $output = $this->createOutput(json_encode($linkIntegrityResults));

            if ($linkIntegrityResultCount === $linkCount) {
                $this->completeTask($task, $output);

                return true;
            }

            $taskParameters = $task->getParameters();

            $parameters = empty($taskParameters)
                ? []
                : json_decode($task->getParameters(), true);

            $parameters['excluded-urls'] = $this->getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults);
            $task->setParameters(json_encode($parameters));

            $task->setOutput($output);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }

        return false;
    }

    /**
     * @param Task $task
     * @param WebResource $webResource
     *
     * @return array
     */
    private function findWebResourceLinks(Task $task, WebResource $webResource)
    {
        $linkFinder = new HtmlDocumentLinkUrlFinder();
        $linkFinder->setSourceUrl($task->getUrl());
        $linkFinder->setSourceContent($webResource->getContent());

        return $linkFinder->getAll();
    }

    /**
     * @param string $outputContent
     *
     * @return Output
     */
    private function createOutput($outputContent)
    {
        $mediaType = new InternetMediaType();
        $mediaType->setType('application');
        $mediaType->setSubtype('json');

        $output = new Output();
        $output->setOutput($outputContent);
        $output->setContentType($mediaType);
        $output->setErrorCount(0);
        $output->setWarningCount(0);

        return $output;
    }

    /**
     * @param Task $task
     * @param Output $output
     */
    private function completeTask(Task $task, Output $output)
    {
        $taskCompletedState = $this->stateService->get(TaskService::COMPLETED_STATE);

        $this->taskService->complete(
            $task,
            new \DateTime(),
            $output,
            $taskCompletedState
        );
    }

    /**
     * @param array $linkIntegrityResults
     *
     * @return string[]
     */
    private function getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults)
    {
        $urls = [];

        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if (!$this->isLinkIntegrityError($linkIntegrityResult) && !in_array($linkIntegrityResult->url, $urls)) {
                $urls[] = $linkIntegrityResult->url;
            }
        }

        return $urls;
    }

    /**
     * @param array $links
     * @param array $existingLinkIntegrityResults
     *
     * @return array
     */
    private function getLinkIntegrityResultsFromExistingResults($links, $existingLinkIntegrityResults)
    {
        $linkIntegrityResults = [];

        foreach ($links as $link) {
            $linkIntegrityResult = $this->getExistingLinkIntegrityResult($link['url'], $existingLinkIntegrityResults);

            if (!is_null($linkIntegrityResult)) {
                $linkIntegrityResult->context = $link['element'];
                $linkIntegrityResults[] = $linkIntegrityResult;
            }
        }

        return $linkIntegrityResults;
    }

    /**
     * @param string $url
     * @param \stdClass[] $existingLinkIntegrityResults
     *
     * @return \stdClass
     */
    private function getExistingLinkIntegrityResult($url, $existingLinkIntegrityResults)
    {
        foreach ($existingLinkIntegrityResults as $linkIntegrityResult) {
            if ($linkIntegrityResult->url == $url) {
                return $linkIntegrityResult;
            }
        }

        return null;
    }

    /**
     * @param array $rawTaskOutputs
     *
     * @return array
     */
    private function getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs)
    {
        $linkIntegrityResults = [];

        foreach ($rawTaskOutputs as $rawTaskOutput) {
            $decodedTaskOutput = json_decode($rawTaskOutput);

            if (is_array($decodedTaskOutput)) {
                foreach ($decodedTaskOutput as $linkIntegrityResult) {
                    if (!$this->isLinkIntegrityError($linkIntegrityResult) && is_object($linkIntegrityResult)) {
                        $linkIntegrityResults[] = $linkIntegrityResult;
                    }
                }
            }
        }

        return $linkIntegrityResults;
    }

    /**
     * @param \stdClass $linkIntegrityResult
     *
     * @return boolean
     */
    private function isLinkIntegrityError($linkIntegrityResult)
    {
        if (!is_object($linkIntegrityResult)) {
            $this->logger->error('LinkIntegrityTaskPreProcessor::isLinkIntegrityError: non-object found');

            return false;
        }

        if ($linkIntegrityResult->type == 'curl') {
            return true;
        }

        return $linkIntegrityResult->state >= 300 && $linkIntegrityResult->state < 600;
    }

    /**
     * @param Task $task
     *
     * @return WebResource|null
     */
    private function getWebResource(Task $task)
    {
        try {
            $httpClient = $this->httpClientService->get();

            $httpClient->setUserAgent(self::HTTP_USER_AGENT);

            $request = $this->httpClientService->getRequest($task->getUrl());
            $this->httpClientService->prepareRequest($request, $task->getParametersArray());

            $httpClient->setUserAgent(null);

            return $this->webResourceService->get($request);
        } catch (WebResourceException $webResourceException) {
            $this->logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::getWebResource [%s][http exception][%s]',
                $task->getUrl(),
                $webResourceException->getResponse()->getStatusCode()
            ));
        } catch (CurlException $curlException) {
            $this->logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::getWebResource [%s][curl exception][%s]',
                $task->getUrl(),
                $curlException->getErrorNo()
            ));
        } catch (TooManyRedirectsException $tooManyRedirectsException) {
            $this->logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::getWebResource [%s][http exception][too many redirects]',
                $task->getUrl()
            ));
        }

        return null;
    }
}
