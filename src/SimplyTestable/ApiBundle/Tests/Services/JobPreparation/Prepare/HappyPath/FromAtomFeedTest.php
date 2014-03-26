<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

class FromAtomFeedTest extends FromNewsFeedTest {    
    
    protected function getRootWebPageFixture() {
return <<<'EOD'
HTTP/1.0 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
    <head>
        <link href="http://example.com/atom.xml" type="application/atom+xml" rel="alternate">
    </head>
    <body>
    </body>
</html>    
EOD;
    }
    
    protected function getFeedFixture() {
return <<<'EOD'
HTTP/1.0 200 OK
Content-Type: application/atom+xml

<?xml version="1.0" encoding="utf-8"?> 
<feed xmlns="http://www.w3.org/2005/Atom">
 
    <entry>
        <link href="http://example.com/0/" />
    </entry>
        
    <entry>
        <link href="http://example.com/1/" />
    </entry>
        
    <entry>
        <link href="http://example.com/2/" />
    </entry>
</feed> 
EOD;
    }    

}
