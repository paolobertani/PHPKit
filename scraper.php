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

    private $cache_path,
            $visited,
            $domain,
            $root,

            $attempts,
            $pause;



    public function __construct()
    {
        $this->cache_path = false;
        $this->visited = [];
        $this->attempts = 3;
        $this->pause = 10;

        if( ArgumentGet( '-nocache', ARGUMENT_BOOLEAN ) )
        {
            $this->cache_path = false;
        }
        else
        {
            $this->cache_path = ROOT . "/cache.noindex";
            MakeDir( $this->cache_path );
        }
    }



    // --- Override to provide a custom info string

    //
    // returns info to be displayed on the terminal during scraping
    //

    protected function get_info( $url, $count, $level, $memory )
    {
        return "(Visited: $count; Level: $level; Memory: $memory MB) URL: $url";
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
    // set "retry" values
    //

    protected function set_retry( $attempts, $pause )
    {
        $this->attempts = $attempts;
        $this->pause    = $pause;
    }



    //
    // scrape
    //
    // parse the  site  from  `$root`  then  go
    // (only) deeper with recursion
    //

    public function scrape( $root, $test = false )
    {

        // check root url is good

        if( ! $this->url_is_good( $root ) )
        {
            Error( "Scraper: bad root URL: $root" );
            /*--- QUIT POINT ---*/
        }


        // check url is absolute

        if( ! $this->url_is_absolute( $root ) )
        {
            Error( "Scraper: relative root URL: $root" );
            /*--- QUIT POINT ---*/
        }


        // make root lowercase

        $root = StringLowercase( $root );


        // root url is stored lowercase
        // and subsequently compared ci

        $this->root = $root;


        // store domain (scheme+domain)

        $this->domain = $this->domain_from_url( $root );


        // testing the class? exit here

        if( $test )
        {
            return;
        }


        // start recursive scraping

        $this->scrape_url( $root, 1 );


        // Done

        EchoNL( '' );
    }



    // parse web pages recursively; the  passed
    // url must have not been visited  yet  and
    // must have the root part lowercase

    private function scrape_url( $url, $level )
    {
        // add URL to visited pages

        $this->visited[] = $url;
        $n = count( $this->visited );


        // provide info during parsing

        $memory = round( memory_get_usage() / ( 1024 * 1024 ), 0 );
        $info = $this->get_info( $url, $count, $level, $memory );
        EchoCR( $info );


        // retrieve page contents

        $result = $this->curl_or_fetch_cache( $url );
        if( $result['status'] >= 300 || $result['error'] != '' )
        {
            EchoNL( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] );
            return;
        }
        $response = $result['response'];
        $headers  = $result['headers'];


        // redirected? save destination url in the visited list

        if( $result['url'] !== $url )
        {
            $this->save_in_visited( $result['url'] );
        }


        // is html?

        $is_html = false;

        if( isset( $headers['content-type'] ) )
        {
            $is_html = StringBegins( $headers['content-type'], 'text/html', STRING_CI );
        }
        else
        {
            $is_html = StringBegins( $response, [ '<!DOCTYPE html>', '<html', '<head>', '<body>' ], STRING_CI );
        }


        // parse html with DOMDocument

        if( $is_html )
        {
            $dom = new DOMDocument();
            @$success = $dom->loadHtml( mb_convert_encoding( $response, 'HTML-ENTITIES', "UTF-8" ) );
            if( $success === false )
            {
                EchoNL( "Failed parsing $url" );
                $dom = false;
            }
        }
        else
        {
            $dom = false;
        }


        // process/parse contents

        $this->process( $url, $response, $headers, $dom );


        // if not html there are no links
        // to parse: exit here

        if( ! $is_html )
        {
            return;
            /*--- EXIT POINT ---*/
        }


        // retrieve links and go thru the linked pages

        $hrefs = [];
        foreach( $dom->getElementsByTagName( 'a' ) as $node )
        {
            $hrefs[] = [ 'url' => trim( $node->getAttribute('href') ), 'pre_filter_url' => '' ];
        }


        // free some memory

        unset( $node );
        unset( $dom );


        // iterates over hyperlinks

        $n = count( $hrefs );
        for( $i = 0; $i < $n; $i++ )
        {
            $href = $hrefs[ $i ];

            $url = $href['url'];

            if( ! $this->url_is_good( $url ) )
            {
                $this->warn_if_url_comes_from_filter( 'not good', $href['url'], $href['pre_filter_url'] );
                continue;
            }

            if( ! $this->url_is_absolute( $url ) )
            {
                $url = $this->make_url_absolute( $url );
            }

            if( ! $this->url_is_below_root( $url ) )
            {
                $this->warn_if_url_comes_from_filter( 'below root', $href['url'], $href['pre_filter_url'] );
                continue;
            }

            $url = $this->lowercase_root( $url );

            if( in_array( $url, $this->visited ) )
            {
                continue;
            }

            if( $href['pre_filter_url'] === '' )
            {
                $filter = $this->filter( $url );

                if( $filter === false )
                {
                    continue;
                }

                if( is_string( $filter ) ) // replace  the  current entry  with  the  new  url  and   let   the   loop
                {                          // iterate on it again making every check. On the new iteration the new url
                    $hrefs[ $i ] = [ 'url' => $filter, 'pre_filter_url' => $url ];  //  will  not  be  filtered  again
                    $i--;
                    continue;
                }

                if( $filter !== true )
                {
                    continue;
                }
            }

            $this->scrape_url( $url, $level + 1 );
        }
    }



    private function curl_or_fetch_cache( $url )
    {
        if( $this->cache_path === false )
        {
            return Curl( $url );
        }

        $cache_file_path = $this->cache_file_path_for_url( $url );

        if( is_file( $cache_file_path ) )
        {
            $result = [];
            $result['response'] = file_get_contents( "$cache_file_path.response.txt" );
            $result['headers']  = file_get_contents( "$cache_file_path.headers.txt" );
            $result['error'] = '';
            $result['status'] = 200;
            $result['errnum'] = 0;
            $result['url'] = $url;

            return $result;
            /*--- EXIT POINT ---*/
        }

        for( $attempts = 0; $attempts < $this->attempts; $attempts++ )
        {
            $result = Curl( $url );
            if( $result['status'] < 300 && $result['error'] == '' )
            {
                file_put_contents( "$cache_file_path.response.txt", $result['response'] );
                file_put_contents( "$cache_file_path.headers.txt",  $result['headers'] );
            }

            for( $i = $this->pause; $i > 0; $i-- )
            {
                EchoCR( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] . " - pause... $i" );
                sleep(1);
            }
        }

        return $result;
    }


    // return path to cache file  for  a  given
    // URL
    // as URL may be case sensitive  while  the
    // OS is not a hash is always appended

    private function cache_file_path_for_url( $url )
    {
        $url = $this->lowercase_root( $url );
        $filename = str_replace( "://", "-", $url );
        $filename = str_replace( ":", "_", $filename );
        $filename = str_replace( "/", "|", $filename );
        if( strlen( $filename ) > 100 )
        {
            $filename = substr( $filename, 97 ) . '---';
        }
        $filename = $filename . " (" . md5( $url ) .")";
        $cache_file_path = $this->cache_path . "/" . $filename;

        return $cache_file_path;
    }



    // save the url in the visited list

    private function save_in_visited( $url )
    {
        $url = $this->lowercase_root( $url );
        if( ! in_array ( $url, $this->visited ) )
        {
            $this->visited[] = $url;
        }
    }



    // is the url absolute

    private function url_is_absolute( $url )
    {
        return StringBegins( $url, [ 'https://', 'http://' ], STRING_CI );
    }



    // is the url good

    private function url_is_good( $url )
    {
        $scheme = StringLowercase( StringBetween( $url, '', '://' ) );

        if( $scheme === false )
        {
            return true; // relative url is ok
        }

        // check scheme is supported

        return in_array( $scheme, [ 'http', 'https' ] );
    }



    // make relative path absolute

    private function make_url_absolute( $relative )
    {
        return phpUri::parse( $this->domain )->join( $relative );
    }



    // returns the scheme+domain

    private function domain_from_url( $url )
    {
        $url = StringLowercase( $url ) . "/";
        $pos = strpos( $url, "/", 8 );
        return substr( $url, 0, $pos );
    }



    // takes the absolute path  passed  and  if
    // begins with  root  turn  the  root  part
    // lowercase  otherwise  returns  the   url
    // unmodified
    // the absolute path passed may be  shorter
    // than root: in this case if  root  begins
    // with the url it is  returned  lowercase,
    // if not is returned unmodified

    private function lowercase_root( $url )
    {
        if( strlen( $url ) >= strlen( $this->root ) )
        {
            if( StringBegins( $url, $this->root, STRING_CI ) )
            {
                return StringReplaceAtBeginning( $url, $this->root, $this->root );
            }
            return $url;
        }

        // the url is shorter than root

        if( StringBegins( $this->root, $url, STRING_CI ) )
        {
            return LowerCase( $url );
        }
        return $url;
    }


    // is the absolute url below root

    private function url_is_below_root( $url )
    {
        return StringBegins( $url, $root, STRING_CI );
    }



    // warn if a url with issues comes from the filter function

    private function warn_if_url_comes_from_filter( $warn, $new, $original )
    {
        if( $original === '' )
        {
            return;
        }
        EchoNL( "WARNING: filter function produced $warn url\n         original: $original\n         filtered: $new" );
    }



    //
    // test
    //

    public function test( $root )
    {
        EchoNL( "Testing Scraper" );
        EchoNL( "---------------" );
        EchoNL( "root url: $root" );
        $this->scrape( $root, true );
        EchoNL( "Instance variablies:" );

        EchoNL( "cache_path = $this->cache_path" );
        EchoNL( "domain     = $this->domain    " );
        EchoNL( "root       = $this->root      " );
        EchoNL( "attempts   = $this->attempts  " );
        EchoNL( "pause      = $this->pause     " );
        if( is_array( $this->visited ) )
        {
            EchoNL( "visited    = <array> " . count( $this->visited ) . " items" );
        }
        else
        {
            EchoNL( "FAIL: visited is not array" );
        }
        EchoNL( "---------------" );

        $this->set_retry( 1, 8 );
        if( $this->attempts === 1 && $this->pause === 8 );


    }
}
