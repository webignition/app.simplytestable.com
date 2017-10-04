<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\TooManyRedirectsException;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\HttpClientService;
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TaskService $taskService,
        WebResourceService $webResourceService,
        HttpClientService $httpClientService,
        LoggerInterface $logger
    ) {
        $this->taskService = $taskService;
        $this->webResourceService = $webResourceService;
        $this->httpClientService = $httpClientService;
        $this->logger = $logger;
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
        $rawTaskOutputs = $this->taskService->getEntityRepository()->findOutputByJobAndType($task);

        if (empty($rawTaskOutputs)) {
            return false;
        }

        $webResource = $this->getWebResource($task);

        if (!$webResource instanceof WebPage) {
            return false;
        }

        $linkFinder = new HtmlDocumentLinkUrlFinder();
        $linkFinder->setSourceUrl($task->getUrl());
        $linkFinder->setSourceContent($webResource->getContent());

        $links = $linkFinder->getAll();
        $existingLinkIntegrityResults = $this->getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs);

        $linkIntegrityResults = array();

        foreach ($links as $link) {
            $linkIntegrityResult = $this->getExistingLinkIntegrityResult($link['url'], $existingLinkIntegrityResults);
            if (!is_null($linkIntegrityResult)) {
                $linkIntegrityResult->context = $link['element'];
                $linkIntegrityResults[] = $linkIntegrityResult;
            }
        }

        if (count($linkIntegrityResults)) {
            $mediaType = new InternetMediaType();
            $mediaType->setType('application');
            $mediaType->setSubtype('json');

            $output = new Output();
            $output->setOutput(json_encode($linkIntegrityResults));
            $output->setContentType($mediaType);
            $output->setErrorCount($this->getErrorCount($linkIntegrityResults));
            $output->setWarningCount(0);

            if (count($linkIntegrityResults) == count($links)) {
                $this->taskService->complete(
                    $task,
                    new \DateTime(),
                    $output,
                    $this->taskService->getCompletedState()
                );

                return true;
            }

            $parameters = ($task->hasParameters()) ? json_decode($task->getParameters(), true) : array();
            $parameters['excluded-urls'] = $this->getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults);
            $task->setParameters(json_encode($parameters));

            $task->setOutput($output);
            $this->taskService->getManager()->persist($task);
            $this->taskService->getManager()->flush();
        }

        return false;
    }

    /**
     * @param array $linkIntegrityResults
     *
     * @return string[]
     */
    private function getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults)
    {
        $urls = array();

        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if (!$this->isLinkIntegrityError($linkIntegrityResult) && !in_array($linkIntegrityResult->url, $urls)) {
                $urls[] = $linkIntegrityResult->url;
            }
        }

        return $urls;
    }

    /**
     * @param array $linkIntegrityResults
     * @return int
     */
    private function getErrorCount($linkIntegrityResults)
    {
        $errorCount = 0;

        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if ($linkIntegrityResult->type == 'curl') {
                $errorCount++;
            }

            if ($linkIntegrityResult->type == 'http' && $linkIntegrityResult->state != 200) {
                $errorCount++;
            }
        }

        return $errorCount;
    }


    /**
     * @param string $url
     * @param array $existingLinkIntegrityResults
     *
     * @return \stdClass
     */
    private function getExistingLinkIntegrityResult($url, $existingLinkIntegrityResults)
    {
        foreach ($existingLinkIntegrityResults as $linkIntegrityResult) {
            if (is_object($linkIntegrityResult)) {
                if ($linkIntegrityResult->url == $url) {
                    return $linkIntegrityResult;
                }
            } else {
                $this->logger->error('LinkIntegrityTaskPreProcessor::getExistingLinkIntegrityResult: non-object found');
            }
        }
    }

    /**
     * @param array $rawTaskOutputs
     *
     * @return array
     */
    private function getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs)
    {
        $linkIntegrityResults = array();

        foreach ($rawTaskOutputs as $rawTaskOutput) {
            $decodedTaskOutput = json_decode($rawTaskOutput);

            if (!is_array($decodedTaskOutput)) {
                continue;
            }

            foreach ($decodedTaskOutput as $linkIntegrityResult) {
                if (!$this->isLinkIntegrityError($linkIntegrityResult)) {
                    $linkIntegrityResults[] = $linkIntegrityResult;
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

        $isHttpState = in_array(substr($linkIntegrityResult->state, 0, 1), array('3', '4', '5'));

        if ($linkIntegrityResult->type == 'http' && $isHttpState) {
            return true;
        }

        return false;
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

            $httpClient->setUserAgent('ST Link integrity task pre-processor');

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
