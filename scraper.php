<?php

//
//
// Scraper
//
//



//
// Includes
//

require_once ROOT . '/include/curl.php';
require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/phpuri.php';
require_once ROOT . '/include/strings.php';
require_once ROOT . '/include/arguments.php';



//
// Globals
//

$g_ScrapeCache = false;
$g_ScrapeVisited = [];
$g_ScrapeInfo = false;



//
// Initialize
//

ScrapeCacheInitMaybe(); // shell args are inspected for cache enable/disable



//
// ScrapeInfo
//
// Provide info on the terminal during scraping
// @url @count @memory parameters can be
// used in the info string
//

function ScrapeInfo( $info )
{
    global $g_ScrapeInfo;
    $g_ScrapeInfo = $info;
}




//
// Scrape
//
// Parse the site from `$root` then go (only) deeper with recursion.
//
// `$processFn` is called after a URL is retrieved and defined as
// function processFn( $url, $html, $headers, $dom )
// `$dom` is false if the content is not html
//
// `$filterFn` (opt.) is called before retrieving a URL and defined as
// function filterFn( $url ) -> $url | false
// will return `false` if the URL must not be parsed, `true` otherwise,
// a string representing a URL if a different URL should be retrieved
//

function Scrape( $root, $processFn, $filterFn = null )
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

    ScrapeUrl( $root, $root, $site, $processFn, $filterFn, 1 );


    // Done

    EchoNL( '' );
}



//
// PRIVATE
//

function ScrapeUrl( $url, $root, $site, $processFn, $filterFn, $level )
{
    global $g_ScrapeVisited;
    global $g_ScrapeInfo;


    // Add URL to visited pages

    $g_ScrapeVisited[] = strtolower( $url );
    $n = count( $g_ScrapeVisited );


    // Provide info during parsing

    $memory = round( memory_get_usage() / ( 1024 * 1024 ), 0 );

    $info = $g_ScrapeInfo === false ? '( N=@count L=@level M=@memoryMB ) URL: @url' : $g_ScrapeInfo;
    $info = str_replace( '@count',  $n,     $info );
    $info = str_replace( '@level',  $level, $info );
    $info = str_replace( '@memory', $memory,$info );
    $info = str_replace( '@url',    $url,   $info );

    EchoCR( $info );


    // Retrieve page contents

    $result = ScrapeCurlCache( $url );
    if( $result['status'] >= 300 || $result['error'] != '' )
    {
        EchoNL( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] );
        return;
    }
    $response = $result['response'];
    $headers  = $result['headers'];


    // Got a redirect?
    // Save the URL in the visited list

    if( $result[ 'url' ] != '' && $result[ 'url' ] != $url )
    {
        $g_ScrapeVisited[] = strtolower( $result[ 'url' ] );
    }


    // Parse Html

    $isHtml = false;

    if( isset( $headers['content-type'] )
    {
        $isHtml = StringBegins( $headers['content-type'], 'text/html' );
    }
    else
    {
        $isHtml = StringBeginsCI( $response, '<!DOCTYPE html>' ) || StringBeginsCI( $response, '<html' ) || StringBeginsCI( $response, '<head>' ) || StringBeginsCI( $response, '<body>' );
    }

    if( $isHtml )
    {
        $dom = new DOMDocument();
        @$success = $dom->loadHtml( mb_convert_encoding( $response, 'HTML-ENTITIES', "UTF-8" ) );
        if( $success === false )
        {
            EchoNL( "Failed parsing $url" );
            return;
        }
    }
    else
    {
        $dom = false;
    }


    // Call provided contents processing function

    $processFn( $url, $response, $headers, $dom );


    // If not html there are no links to parse

    if( ! $isHtml )
    {
        return;
        /*--- EXIT POINT ---*/
    }


    // Retrieve links and parse the linked pages

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

        if( $filterFn !== null )
        {
            $flt = $filterFn( $href );
            if( $flt === false )
            {
                continue;
            }
            if( is_string( $flt ) )
            {
                $href = $flt;
            }
        }

        ScrapeUrl( $href, $root, $site, $processFn, $filterFn, $level + 1 );
    }
}



function ScrapeCacheInitMaybe()
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
        $result['headers'] = file_get_contents( "$cacheFile.headers" );
        $result['error'] = '';
        $result['status'] = 200;
        $result['errnum'] = 0;
        $result['url'] = $url;

        return $result;
        /*--- EXIT POINT ---*/
    }

    for( $attempts = 0; $attempts < 3; $attempts++ )
    {
        $result = Curl( $url );
        if( $result['status'] < 300 && $result['error'] == '' )
        {
            file_put_contents( $cacheFile, $result['response'] );
            file_put_contents( "$cacheFile.headers", $result['headers'] );
            if( $result['url'] != $url )
            {
                $url = $result['url'];
                $cacheFile = ScrapeCurlCacheFile( $url );
                file_put_contents( $cacheFile, $result['response'] );
                file_put_contents( "$cacheFile.headers", $result['headers'] );
            }
            break;
        }

        for( $i = 30; $i > 0; $i-- )
        {
            EchoCR( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] . " - pause... $i" );
            sleep(1);
        }
    }

    return $result;
}



//
// Return path to cache file for a given URL
//

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