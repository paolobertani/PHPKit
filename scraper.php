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
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/phpuri.php';
require_once ROOT . '/include/strings.php';
require_once ROOT . '/include/arguments.php';
require_once ROOT . '/include/filesystem.php';



class Scraper
{

    private $cachePath,
            $visited;



    public function __construct()
    {
        $this->cachePath = false;
        $this->visited = [];

        if( ArgumentGet( '-nocache', ARGUMENT_BOOLEAN ) )
        {
            $this->cachePath = false;
        }
        else
        {
            $this->cachePath = ROOT . "/cache.noindex";
            MakeDir( $this->cachePath );
        }
    }



    // --- Override to provide a custom info string

    //
    // returns info to be displayed on the terminal during scraping
    //

    protected function getInfo( $url, $count, $level, $memory )
    {
        return "(Visited: $count; Level: $level; Memory: $memory MB;) URL: $url";
    }



    // --- Override to implement a URL filter

    //
    // filter the URLs retrieved;
    // the function may return:
    // `true` let load and parse the URL
    // `false` URL should not be loaded
    // <string> let parse this URL instead
    //

    protected function filter( $url )
    {
        return true;
    }



    // --- Override to implement a contents processor

    //
    // process the response
    //

    protected function process( $url, $response, $headers, $dom )
    {
        //
    }



    //
    // Scrape
    //
    // parse the site from `$root` then go (only) deeper with recursion
    //

    public function Scrape( $root, $href )
    {
        $x = $this->lowercase( $root );
        $url = phpUri::parse( $x )->join( $href );
        echo "root: $root\ndomain: $x\nhref: $href\nurl: $url\n";
        exit(0);

        // Check root url is good

        if( ! $this->lowercase( $root ) );
        {
            Error( "bad root URL" );
            /*--- QUIT POINT ---*/
        }


        // Start recursive scraping

        $this->ScrapeUrl( $root, $root, 1 );


        // Done

        EchoNL( '' );
    }



    private function ScrapeUrl( $url, $root, $level )
    {
        // Add URL to visited pages

        $this->visited[] = $url;
        $n = count( $this->visited );


        // Provide info during parsing

        $memory = round( memory_get_usage() / ( 1024 * 1024 ), 0 );
        EchoCR( $this->getInfo( $url, $count, $level, $memory ) );


        // Retrieve page contents

        $result = $this->ScrapeCurlCache( $url );
        if( $result['status'] >= 300 || $result['error'] != '' )
        {
            EchoNL( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] );
            return;
        }
        $response = $result['response'];
        $headers  = $result['headers'];


        // Got a redirect?
        // Save the URL in the visited list

        $this->lowercase( $result['url'] );
        if( $result['url'] !== $url )
        {
            $this->visited[] = $result['url'];
        }


        // Parse Html

        $isHtml = false;

        if( isset( $headers['content-type'] ) )
        {
            $isHtml = StringBegins( $headers['content-type'], 'text/html', STRING_CI );
        }
        else
        {
            $isHtml = StringBegins( $response, [ '<!DOCTYPE html>', '<html', '<head>', '<body>' ], STRING_CI );
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


        // Process/parse contents

        $this->process( $url, $response, $headers, $dom );


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

            $domain = $this->lowercase( $href, true );

            if( $href === false )
            {
                continue;
            }

            $href = phpUri::parse( $domain )->join( $href );

            if( ! StringBegins( $href, $root ) )
            {
                continue;
            }

            if( in_array( $href, $this->visited ) )
            {
                continue;
            }

            $filter = $this->filter();

            if( $filter === false )
            {
                continue;
            }

            if( is_string( $filter ) )
            {
                $href = $filter;
                $this->lowercase( $href );
                if( $href === false || in_array( $href, $this->visited ) )
                {
                    continue;
                }
            }

            ScrapeUrl( $href, $root, $level + 1 );
        }
    }



    private function ScrapeCurlCache( $url )
    {
        if( $this->cachePath === false )
        {
            return Curl( $url );
        }

        $cacheFile = $this->cacheFileForUrl( $url );

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
                file_put_contents( "$cacheFile.response.txt", $result['response'] );
                file_put_contents( "$cacheFile.headers.txt", $result['headers'] );
                $this->lowercase( $result['url'] );
                if( $result['url'] !== false && $result['url'] !== $url )
                {
                    $url = $result['url'];
                    $cacheFile = $this->cacheFileForUrl( $url );
                    file_put_contents( "$cacheFile.response.txt", $result['response'] );
                    file_put_contents( "$cacheFile.headers.txt", $result['headers'] );
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
    // return path to cache file for a given URL
    // as URL may be case sensitive while the OS is not
    // a hash is always appended
    //

    private function cacheFileForUrl( $url )
    {
        $this->lowercase( $url );
        $filename = str_replace( "://", "-", $url );
        $filename = str_replace( ":", "_", $filename );
        $filename = str_replace( "/", "|", $filename );
        if( strlen( $filename ) > 100 )
        {
            $filename = substr( $filename, 97 ) . '---';
        }
        $filename = $filename . " (" . md5( $url ) .")";
        $cacheFile = $this->cachePath . "/" . $filename;

        return $cacheFile;
    }



    //
    // takes the url passed by reference and make
    // scheme and domain lowercase
    // if the url is not valid turn it into `false`
    // returns the domain name with a trailing
    // slash
    //

    private function lowercase( &$url )
    {
        if( $url === '' || $url === false || ! StringBegins( $url, [ 'https://', 'http://' ], STRING_CI ) )
        {
            $url = false;
            return false;
        }

        $domain = StringBetween( $url, '://', '' );
        if( StringHas( $domain, '/' ) )
        {
            $domain = StringBetween( $domain, '', '/' );
        }

        $sd = StringLowercase( StringBetween( $url, '', $domain, STRING_MARKERS ) );

        $url = $sd . substr( $url, strlen( $sd ) );

        return "$sd/";
    }
}
