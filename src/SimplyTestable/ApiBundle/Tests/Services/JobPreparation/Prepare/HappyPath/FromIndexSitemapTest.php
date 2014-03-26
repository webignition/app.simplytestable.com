<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

class FromIndexSitemapTest extends HappyPathTest {

    protected function getFixtureMessages() {
        return array(
            $this->getDefaultRobotsTxtFixtureContent(), // robots.txt fixture
            $this->getIndexSitemapFixture(),
            $this->getSitemapFixture(),
        );
    } 
    
    protected function getIndexSitemapFixture() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/xml

<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>http://www.example.com/sitemap1.xml</loc>
    </sitemap>
</sitemapindex>
EOD;
    }
    
    
    protected function getSitemapFixture() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/xml

<?xml version="1.0" encoding="UTF-8"?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
   <url>
      <loc>http://example.com/0/</loc>
   </url>
   <url>
      <loc>http://example.com/1/</loc>
   </url>
   <url>
      <loc>http://example.com/2/</loc>
   </url>      
</urlset>
EOD;
    }

}
