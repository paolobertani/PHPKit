<?php

require_once ROOT . '/include/curl.php';
require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/phpuri.php';
require_once ROOT . '/include/arguments.php';

$g_ScrapeCache = false;
$g_ScrapeVisited = [];

ScrapeCache();

function Scrape( $root, $filterFn, $preFilterFn = null )
{
    // Array of visited pages

    global $g_ScrapeVisited;
    $g_ScrapeVisited = [];


    // Check root url is good

    if( substr( $root, 0, 8) != 'https://' && substr( $root, 0, 7) != 'http://' )
    {
        echo "Scrape(): bad root URL\n";
        exit(0);
    }


    // Extract website url w/trailing slash

    $x = strpos( $root, '/', 9 );
    if( $x === false )
    {
        $site = $root;
    }
    else
    {
        $site = substr( $root, 0, $x );
    }
    $site .= '/';


    // Start recursive scraping

    ScrapeUrl( $root, $root, $site, $filterFn, $preFilterFn, 1 );


    // Done

    EchoNL( '' );
}


function ScrapeUrl( $url, $root, $site, $filterFn, $preFilterFn, $level )
{
    global $g_ScrapeVisited;


    // Add URL to visited pages

    $g_ScrapeVisited[] = strtolower( $url );
    $n = count( $g_ScrapeVisited );


    // Provide some info

    $memory = round( memory_get_usage() / ( 1024 * 1024 ), 0 );

    EchoCR( "($n L$level {$memory}MB) $url" );


    // Retrieve page contents

    $result = ScrapeCurlCache( $url );
    if( $result['status'] >= 300 || $result['error'] != '' )
    {
        EchoNL( "Scrape(): $url - Status: " . $result['status'] . " - Error: " . $result['error'] );
        return;
    }
    $html = $result['response'];


    // Got a redirect?
    // Save the URL in the visited list

    if( $result[ 'url' ] != '' && $result[ 'url' ] != $url )
    {
        $g_ScrapeVisited[] = strtolower( $result[ 'url' ] );
    }


    // Parse Html

    $dom = new DOMDocument();
    @$success = $dom->loadHtml( mb_convert_encoding( $html, 'HTML-ENTITIES', "UTF-8" ) );
    if( $success === false )
    {
        EchoNL( "Scrape(): failed parsing " . $url );
        return;
    }


    // Call provided filter function

    $filterFn( $url, $html, $dom );


    // Retrieve and parse URLs

    $hrefs = array();
    foreach( $dom->getElementsByTagName( 'a' ) as $node )
    {
        $hrefs[] = $node->getAttribute('href');
    }
    unset( $node );
    unset( $dom );

    foreach( $hrefs as $href )
    {
        $href = trim( $href );

        $href = phpUri::parse( $site )->join( $href );

        if( substr( strtolower( $href ), 0, strlen( $root ) ) !== $root )
        {
            continue;
        }

        if( in_array( strtolower( $href ), $g_ScrapeVisited ) )
        {
            continue;
        }

        if( $preFilterFn !== null )
        {
            $href = $preFilterFn( $href );
            if( $href === false )
            {
                continue;
            }
        }

        ScrapeUrl( $href, $root, $site, $filterFn, $preFilterFn, $level + 1 );
    }
}



function ScrapeCache()
{
    global $g_ScrapeCache;

    if( ! ArgumentGet( '-nocache', ARGUMENT_BOOLEAN ) )
    {
        $g_ScrapeCache = ROOT . "/cache.noindex";
        if( ! is_dir( $g_ScrapeCache ) )
        {
            mkdir( $g_ScrapeCache );
        }
    }
}



function ScrapeCurlCache( $url )
{
    global $g_ScrapeCache;

    if( $g_ScrapeCache === false )
    {
        return Curl( $url );
    }

    $cacheFile = ScrapeCurlCacheFile( $url );

    if( is_file( $cacheFile ) )
    {
        $result = array();
        $result['response'] = file_get_contents( $cacheFile );
        $result['error'] = '';
        $result['status'] = 200;
        $result['errnum'] = 0;
        $result['url'] = $url;
    }
    else
    {
        $result = Curl( $url );
        if( $result['status'] < 300 && $result['error'] == '' )
        {
            file_put_contents( $cacheFile, $result['response'] );
            if( $result['url'] != $url )
            {
                $url = $result['url'];
                $cacheFile = ScrapeCurlCacheFile( $url );
                file_put_contents( $cacheFile, $result['response'] );
            }
        }
    }

    return $result;
}



function ScrapeCurlCacheFile( $url )
{
    global $g_ScrapeCache;

    $filename = str_replace( "/", "|", str_replace( ":", "_", $url) );
    if( strlen( $filename ) > 200 )
    {
        $filename = substr( $filename, 0, 156 ) . '||||' . sha1( $filename );
    }

    $cacheFile = $g_ScrapeCache . "/" . $filename;

    return $cacheFile;
}