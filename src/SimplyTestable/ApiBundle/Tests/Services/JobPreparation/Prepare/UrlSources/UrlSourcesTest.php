<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class UrlSourcesTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job; 
    
    private $expectedUrls = array(
        'NoUrls' => array(),
        'SitemapXmlUrls' => array(
            'http://www.example.com/from-sitemap-xml/'
        ),        
        'SitemapTxtUrls' => array(
            'http://www.example.com/from-sitemap-txt/'
        ),
        'SitemapXmlUrlsAndSitemapTxtUrls' => array(
            'http://www.example.com/from-sitemap-xml/',
            'http://www.example.com/from-sitemap-txt/'
        ),
        'AtomUrls' => array(
            'http://example.com/from-atom-feed'
        ),
        'RssUrls' => array(
            'http://example.com/from-rss-feed'
        )        
    );
    
    
    public function setUp() {
        parent::setUp();

        $this->job = $this->getJobService()->getById($this->createAndResolveDefaultJob());        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureItems())); 
        
        $this->getJobPreparationService()->prepare($this->job);

        $expectedUrls = $this->expectedUrls[$this->getResultSetKey()];
        
        $this->assertEquals($this->getExpectedJobState(), $this->job->getState());
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * count($expectedUrls), $this->job->getTasks()->count());           
        $this->assertHasExpectedTaskUrlSet($expectedUrls);        
    }
    
    abstract protected function getExpectedJobState();
    
    
    private function assertHasExpectedTaskUrlSet($expectedUrls) {        
        foreach ($this->getJobTaskUrls() as $jobUrl) {
            $this->assertTrue(in_array($jobUrl, $expectedUrls));
        }
    }    
    
    /**
     * 
     * @return string[]
     */
    private function getJobTaskUrls() {
        $urls = array();
        foreach ($this->getTaskService()->getUrlsByJob($this->job) as $urlItem) {
            $urls[] = $urlItem['url'];
        }
        
        return $urls;
    }     
    
    private function getHttpFixtureItems() {
        $testCaseValues = $this->getTestCaseValues();

        $this->getSitemapResources($testCaseValues);
        
        $httpFixtureSet = array_merge(array(
            ($testCaseValues['RobotsTxt']) ? $this->getRobotsTxtResource($testCaseValues) : 'HTTP/1.0 404',
        ), $this->getSitemapResources($testCaseValues), array(
            $this->getRootWebPageResource($testCaseValues)
        ), $this->getNewsFeedResources($testCaseValues));      
        return $httpFixtureSet;
    }
    
    private function getTestCaseValues() {
        $rawValues = explode('_', str_replace('test', '', $this->getName()));
        $testCaseValues = array();
        $currentKey = null;
        
        foreach ($rawValues as $value) {
            if (substr($value, 0, strlen('Gets')) == 'Gets') {
                continue;
            }
            
            if (in_array($value, array('True', 'False'))) {
                $testCaseValues[$currentKey] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } else {
                $currentKey = $value;
            }
        }
        
        return $testCaseValues;        
    }
    
    
    private function getResultSetKey() {
        $rawValues = explode('_', str_replace('test', '', $this->getName()));        
        foreach ($rawValues as $value) {
            if (substr($value, 0, strlen('Gets')) == 'Gets') {
                return str_replace('Gets', '', $value);
            }
        }
    }
    
    
    private function getRobotsTxtResource($testCaseValues) {                
        if ($testCaseValues['SitemapXml'] === false &&  $testCaseValues['SitemapTxt'] === false ) {
return <<<'EOD'
HTTP/1.1 200 Ok
Content-Type: text/plain

User-Agent: *
EOD;
        } elseif ($testCaseValues['SitemapXml'] === false &&  $testCaseValues['SitemapTxt'] === true ) {
return <<<'EOD'
HTTP/1.1 200 Ok
Content-Type: text/plain

User-Agent: *
Sitemap: http://example.com/sitemap.txt
EOD;
        } elseif ($testCaseValues['SitemapXml'] === true &&  $testCaseValues['SitemapTxt'] === false ) {
return <<<'EOD'
HTTP/1.1 200 Ok
Content-Type: text/plain

User-Agent: *
Sitemap: http://example.com/sitemap.xml
EOD;
        } elseif ($testCaseValues['SitemapXml'] === true &&  $testCaseValues['SitemapTxt'] === true ) {
return <<<'EOD'
HTTP/1.1 200 Ok
Content-Type: text/plain

User-Agent: *
Sitemap: http://example.com/sitemap.xml
Sitemap: http://example.com/sitemap.txt
EOD;
        }
    }
    
    
    private function getSitemapResources($testCaseValues) {
        if ($testCaseValues['SitemapXml'] === false &&  $testCaseValues['SitemapTxt'] === false ) {
            return array(
                'HTTP/1.0 404',
                'HTTP/1.0 404'
            );
        } elseif ($testCaseValues['SitemapXml'] === false &&  $testCaseValues['SitemapTxt'] === true ) {
            return array(
                $this->getSitemapTxtResource(),
            );
        } elseif ($testCaseValues['SitemapXml'] === true &&  $testCaseValues['SitemapTxt'] === false ) {
            return array(
                $this->getSitemapXmlResource(),
            );
        } elseif ($testCaseValues['SitemapXml'] === true &&  $testCaseValues['SitemapTxt'] === true ) {
            return array(
                $this->getSitemapXmlResource(),
                $this->getSitemapTxtResource(),
            );
        }        
    }
    
    
    private function getRootWebPageResource($testCaseValues) {
        if ($testCaseValues['Rss'] === false && $testCaseValues['Atom'] === false) {
return <<<'EOD'
HTTP/1.1 200 Ok
Content-Type: text/html

<!DOCTYPE html><html></html>
EOD;
        } elseif ($testCaseValues['Rss'] === false && $testCaseValues['Atom'] === true) {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html lang="en">
  <head>
    <link href="http://example.com/atom.xml" type="application/atom+xml" rel="alternate" title="Sitewide ATOM Feed">
  </head>
  <body></body>
</html>
EOD;
        } elseif ($testCaseValues['Rss'] === true && $testCaseValues['Atom'] === false) {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="alternate" type="application/rss+xml" title="Title Example" href="http://example.com/rss.xml" />
  </head>
  <body></body>
</html>
EOD;
        } elseif ($testCaseValues['Rss'] === true && $testCaseValues['Atom'] === true) {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="alternate" type="application/rss+xml" title="Title Example" href="http://example.com/rss.xml" />
    <link href="http://example.com/atom.xml" type="application/atom+xml" rel="alternate" title="Sitewide ATOM Feed">
  </head>
  <body></body>
</html>
EOD;
        }
    }

    
    private function getSitemapXmlResource() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: application/xml

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://www.example.com/from-sitemap-xml/</loc>
    <lastmod>2009-09-22</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>
EOD;
    }  
    
    
    private function getSitemapTxtResource() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/plain

http://www.example.com/from-sitemap-txt/
EOD;
    }    
    
    
    private function getNewsFeedResources($testCaseValues) {
        if ($testCaseValues['Rss'] === false && $testCaseValues['Atom'] === false) {
            return array(
                'HTTP/1.0 404',
                'HTTP/1.0 404'
            );
        } elseif ($testCaseValues['Rss'] === false && $testCaseValues['Atom'] === true) {
            return array(
                $this->getAtomResource()
            );
        } elseif ($testCaseValues['Rss'] === true && $testCaseValues['Atom'] === false) {
            return array(
                $this->getRssResource()
            );            
        } elseif ($testCaseValues['Rss'] === true && $testCaseValues['Atom'] === true) {
            return array(
                $this->getRssResource()
            );             
        }        
    }
    
    
    private function getAtomResource() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: application/atom+xml

<?xml version="1.0" encoding="utf-8"?>
 
<feed xmlns="http://www.w3.org/2005/Atom">
 
        <title>Example Feed</title>
        <subtitle>A subtitle.</subtitle>
        <link href="http://example.org/feed/" rel="self" />
        <link href="http://example.org/" />
        <id>urn:uuid:60a76c80-d399-11d9-b91C-0003939e0af6</id>
        <updated>2003-12-13T18:30:02Z</updated>
 
 
        <entry>
                <title>Atom-Powered Robots Run Amok</title>
                <link href="http://example.com/from-atom-feed" />
                <link rel="alternate" type="text/html" href="http://example.org/2003/12/13/from-atom-feed.html"/>
                <link rel="edit" href="http://example.org/2003/12/13/from-atom-feed/edit"/>
                <id>urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</id>
                <updated>2003-12-13T18:30:02Z</updated>
                <summary>Some text.</summary>
                <author>
                      <name>John Doe</name>
                      <email>johndoe@example.com</email>
                </author>
        </entry>
 
</feed>
EOD;
    }
    
    
    private function getRssResource() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: application/rss+xml

<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
<channel>
 <title>RSS Title</title>
 <description>This is an example of an RSS feed</description>
 <link>http://www.someexamplerssdomain.com/main.html</link>
 <lastBuildDate>Mon, 06 Sep 2010 00:01:00 +0000 </lastBuildDate>
 <pubDate>Mon, 06 Sep 2009 16:45:00 +0000 </pubDate>
 <ttl>1800</ttl>
 
 <item>
  <title>Example entry</title>
  <description>Here is some text containing an interesting description.</description>
  <link>http://example.com/from-rss-feed</link>
  <guid>unique string per item</guid>
  <pubDate>Mon, 06 Sep 2009 16:45:00 +0000 </pubDate>
 </item>
 
</channel>
</rss>
EOD;
    }    
    
}