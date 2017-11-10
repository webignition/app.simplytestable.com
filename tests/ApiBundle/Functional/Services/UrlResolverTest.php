<?php

namespace Tests\ApiBundle\Command;

use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Services\UrlResolver;
use Tests\ApiBundle\Factory\CurlExceptionFactory;
use Tests\ApiBundle\Factory\HtmlDocumentFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class UrlResolverTest extends AbstractBaseTestCase
{
    private $longUrlParts = [
        'http://example.com/',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
    ];

    /**
     * @dataProvider resolveWithCurlExceptionDataProvider
     *
     * @param $curlCode
     */
    public function testResolveWithCurlException($curlCode)
    {
        $this->queueHttpFixtures([
            CurlExceptionFactory::create('', $curlCode)
        ]);

        $resolver = $this->container->get(UrlResolver::class);

        try {
            $resolver->resolve('http://example.com/');
        } catch (CurlException $curlException) {
            $this->assertEquals($curlCode, $curlException->getErrorNo());
        }
    }

    /**
     * @return array
     */
    public function resolveWithCurlExceptionDataProvider()
    {
        return [
            'curl 6' => [
                'curlCode' => 6,
            ],
            'curl 28' => [
                'curlCode' => 28,
            ],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedResolvedUrl
     */
    public function testResolve($httpFixtures, $expectedResolvedUrl)
    {
        $this->queueHttpFixtures($httpFixtures);

        $resolver = $this->container->get(UrlResolver::class);
        $resolvedUrl = $resolver->resolve('http://example.com/');

        $this->assertEquals($expectedResolvedUrl, $resolvedUrl);
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        return [
            'no change to url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'change to url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
            'resolves very long url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse(implode('', $this->longUrlParts)),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => implode('', $this->longUrlParts),
            ],
            'too many redirects' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
            'meta redirect absolute url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('http://meta-redirect.example.com/')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://meta-redirect.example.com/',
            ],
            'meta redirect relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('/foo')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'meta redirect schemeless url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('//foo.example.com')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com',
            ],
            'meta redirect same url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('//example.com')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect no url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument(null)
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect invalid content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/plain',
                        HtmlDocumentFactory::createMetaRedirectDocument('http//foo.example.com/')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
        ];
    }
}
