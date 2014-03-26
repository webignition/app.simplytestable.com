<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

class FromRssFeedTest extends FromNewsFeedTest {    
    
    protected function getRootWebPageFixture() {
return <<<'EOD'
HTTP/1.0 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
    <head>
        <link rel="alternate" type="application/rss+xml" href="http://example.com/feed.xml" />
    </head>
    <body>
    </body>
</html>    
EOD;
    }
    
    protected function getFeedFixture() {
return <<<'EOD'
HTTP/1.0 200 OK
Content-Type: application/rss+xml

<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
    <channel>
        <item>
            <link>http://example.com/0/</link>
        </item>
        
        <item>
            <link>http://example.com/1/</link>
        </item>
        
        <item>
            <link>http://example.com/2/</link>
        </item>
    </channel>
</rss>   
EOD;
    }    

}
