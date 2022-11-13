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
require_once ROOT . '/include/signals.php';
require_once ROOT . '/include/strings.php';
require_once ROOT . '/include/arguments.php';
require_once ROOT . '/include/fs.php';

require_once ROOT . '/include/3rd-parts/phpuri/phpuri.php';


class Scraper
{

    private $cache_path,
            $cache_zip,
            $visited,
            $domain,
            $root,
            $quit;

    protected $attempts,
              $pause,
              $parent_url,
              $parent_html,
              $level,
              $silent;



    public function __construct( $cmd = '', $silent = false )
    {
        $this->cache_path = false;
        $this->cache_zip = true;
        $this->visited = [];
        $this->attempts = 3;
        $this->pause = 10;
        $this->level = 0;
        $this->silent = $silent ? 1 : false;
        $this->quit = false;

        // manage cache dir and archive

        $cache_arg = ArgumentGet( 'cache', ARGUMENT_OPTIONAL );
        if( $cache_arg === false )
        {
            $cache_arg = "";
        }

        if( ArgumentGet( 'nozip', ARGUMENT_BOOLEAN ) )
        {
            $this->cache_zip = false;
        }

        $path = ROOT . "/cache.noindex";

        if( StringCompare( $cache_arg, 'no', STRING_CI ) || $cmd === 'no cache' )
        {
            EchoNL( 'cache disabled', $this->silent );
            $this->cache_path = false;
        }
        elseif( StringCompare( $cache_arg, 'clear', STRING_CI ) || $cmd === 'clear cache' )
        {
            EchoNL( 'cache clear', $this->silent );
            FSRemoveFile( "$path.tar.gz" );
            FSRemoveDirectory( $path );
            FSMakeDir( $path );
            $this->cache_path = $path;
        }
        else
        {
            $zip = FSFileExists( "$path.tar.gz" );
            $dir = FSDirectoryExists( $path );

            if( $zip && $dir )
            {
                FSRemoveFile( "$path.tag.gz" );
            }
            elseif( $zip && ! $dir )
            {
                EchoCR( "Decompressing and unarchiving cache..." );
                FSUnTarGz( "$path.tar.gz", FS_ZIP_DELETE );
            }
            elseif( ! $zip && $dir )
            {
                EchoNL( "Found existing cache" );
            }
            else//( ! zip && ! dir )
            {
                FSMakeDir( $path );
            }

            $this->cache_path = $path;
        }
    }


    // has the scraper been interrupted

    public function has_quit()
    {
        return $this->quit;
    }



    // --- Override to provide a custom info string

    //
    // returns info to be displayed on the terminal
    // during scraping
    //

    protected function get_info( $url, $count, $level, $memory )
    {
        return "(Visited: $count; Level: $level; Memory: $memory MB) URL: $url";
    }



    // --- Override to provide a handler for failed curls

    protected function failed( $url, $status, $error )
    {
        EchoNL( "Failed loading $url - Status: $status - Error: $error", $this->silent );
    }



    // --- Override to implement a URL filter

    //
    // filter the URLs retrieved;
    // the function may return:
    // `true`  let  load  and  parse  the  URL;
    // `false`  URL  should  not   be   loaded;
    // <string> let parse this URL instead;
    //
    // default filter removes the fragment part
    // of the url, converts spaces to `%20`
    //
    // may inspect `$this->parent_url` to  know
    // the parent url
    //                                       \p

    protected function filter( $url )
    {
        $url = StringReplace( $url, " ", "%20" );

        if( StringHas( $url, "#" ) )
        {
            $url = StringBetween( $url, '', '#');
        }

        return $url;
    }



    // --- Override to implement a contents processor

    //
    // process the response;
    // the function may return a string  or  an
    // array  of  strings   representing   urls
    // (aboslute o relative) to be scraped;
    // in case `false` is returned links in the
    // page are not scraped                  \p

    protected function process( $url, $response, $headers, $dom, $is_html )
    {
        //
    }



    //
    // gets called when a url is encountered again
    //

    protected function reprocess( $url )
    {
        //
    }



    //
    // set "retry" values
    //

    public function set_retry( $attempts, $pause )
    {
        if( $attempts < 1 ) { Error( "`attemps` must be at least 1" ); }
        if( $pause < 0 )    { Error( "`pause` must be at least 0" );   }

        $this->attempts = $attempts;
        $this->pause    = $pause;
    }


    //
    // make the Scraper silent (writes only inline)
    //

    public function silent()
    {
        $this->silent = 1;
    }



    //
    // scraping done
    //

    public function done()
    {
        // clear cookies

        Curl();

        // manage cache archive

        if( $this->cache_path !== false )
        {
            if( $this->cache_zip )
            {
                EchoCR( "Archiving and compressing cache..." );
                FSTarGzDirectory( $this->cache_path, FS_ZIP_DELETE );
                EchoNL( "Cache archived and compressed", $this->silent );
            }
            else
            {
                EchoNL( "Cache archived", $this->silent );
            }
        }
    }



    //
    // is the url absolute
    //

    protected function url_is_absolute( $url )
    {
        return StringBegins( $url, [ 'https://', 'http://' ], STRING_CI );
    }



    //
    // is the url good
    //

    protected function url_is_good( $url )
    {
        if( StringBegins( $url, [ 'mailto:', 'tel:', 'javascript:' ] ) )
        {
            return false;
        }

        $scheme = StringBetween( $url, '', '://' );

        if( $scheme === false )
        {
            return true; // relative url is ok
        }

        $scheme = StringLowercase( $scheme );

        // check scheme is supported

        return in_array( $scheme, [ 'http', 'https' ] );
    }



    //
    // make relative path absolute
    //

    protected function url_make_absolute( $relative, $from )
    {
        return phpUri::parse( $from )->join( $relative );
    }



    //
    // scrape
    //
    // parse the  site  from  `$root`  then  go
    // (only) deeper with recursion;
    // optionally a url to start  from  may  be
    // specified
    //

    public function scrape( $root, $start = false )
    {
        // manage optional `start`

        if( $start === false )
        {
            $start = $root;
        }


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


        // manage start URL

        if( $start !== $root )
        {
            if( ! $this->url_is_good( $start ) ) { Error( "Scraper: bad start URL: $start" ); } /*--- QUIT POINT ---*/
            if( ! $this->url_is_absolute( $start ) ) { $start = $this->url_make_absolute( $start, $root ); }
            if( ! $this->url_is_below_root( $start ) ) { Error( "Scraper: start URL is below root: $start" ); } /*--- QUIT POINT ---*/
            $start = $this->lowercase_root( $start );
        }


        // start recursive scraping

        $this->scrape_url( $start, 1 );


        // Done

        EchoNL( "Done scraping {$this->root}", $this->silent );
    }



    // parse web pages recursively; the  passed
    // url must have not been visited  yet  and
    // must have the root part lowercase

    protected function scrape_url( $url, $level, $parent_url = '', $parent_html = '' )
    {
        // add URL to visited pages

        $this->visited[] = $url;
        $n = count( $this->visited );


        // provide info during parsing

        $memory = round( memory_get_usage() / ( 1024 * 1024 ), 0 );
        $count = count( $this->visited );
        $info = $this->get_info( $url, $count, $level, $memory );
        EchoCR( $info );


        // retrieve page contents

        $result = $this->curl_or_fetch_cache( $url );
        if( $result['status'] >= 300 || $result['error'] != '' )
        {
            $this->failed( $url, $result['status'], $result['error'] );
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
            if( trim( $response ) === '' )
            {
                $this->failed( $url, 0, 'Empty response' );
                $dom = false;
                $is_html = false;
            }
        }

        if( $is_html )
        {
            $dom = new DOMDocument();
            @$success = $dom->loadHtml( mb_convert_encoding( $response, 'HTML-ENTITIES', "UTF-8" ) );
            if( $success === false )
            {
                $this->failed( $url, 0, 'Failed DOM parsing' );
                $dom = false;
            }
        }
        else
        {
            $dom = false;
        }


        // process/parse contents

        $this->level = $level;
        $this->parent_url = $parent_url;
        $this->parent_html = $parent_html;
        $more = $this->process( $url, $response, $headers, $dom, $is_html );


        // if not html there are no links
        // to parse: exit here

        if( ! $is_html || $dom === false )
        {
            return;
            /*--- EXIT POINT ---*/
        }



        // retrieve links and go thru the linked pages

        $hrefs = [];
        foreach( $dom->getElementsByTagName( 'a' ) as $node )
        {
            $href = trim( $node->getAttribute('href') );

            $hrefs[] = [ 'url' => $href, 'pre_filter_url' => '' ];
        }


        // add links returned by process function

        if( is_string( $more ) )
        {
            $more = [ $more ];
        }

        if( is_array( $more ) )
        {
            foreach( $more as $m )
            {
                if( is_string( $m ) )
                {
                    $hrefs[] = [ 'url' => $m, 'pre_filter_url' => '' ];
                }
                else
                {
                    EchoNL( "WARNING: process function must return a string or array of strings" );
                }
            }
        }

        if( $more === false )
        {
            $hrefs = [];
        }


        // free some memory

        unset( $node );
        unset( $dom );


        // iterates over hyperlinks

        $n = count( $hrefs );
        for( $i = 0; $i < $n; $i++ )
        {
            $href = $hrefs[ $i ];

            $linkurl = $href['url'];

            if( ! $this->url_is_good( $linkurl ) )
            {
                $this->warn_if_url_comes_from_filter( 'not good', $href['url'], $href['pre_filter_url'] );
                continue;
            }

            if( ! $this->url_is_absolute( $linkurl ) )
            {
                $linkurl = $this->url_make_absolute( $linkurl, $url );
            }

            if( ! $this->url_is_below_root( $linkurl ) )
            {
                $this->warn_if_url_comes_from_filter( 'below root', $href['url'], $href['pre_filter_url'] );
                continue;
            }

            $linkurl = $this->lowercase_root( $linkurl );

            if( in_array( $linkurl, $this->visited ) )
            {
                $this->level = $level;
                $this->reprocess( $linkurl );
                continue;
            }

            if( $href['pre_filter_url'] === '' )
            {
                $this->parent_url = $url;
                $this->parent_html = $response;
                $filter = $this->filter( $linkurl );

                if( $filter === false )
                {
                    continue;
                }

                if( is_string( $filter ) ) // replace  the  current entry  with  the  new  url  and   let   the   loop
                {                          // iterate on it again making every check. On the new iteration the new url
                    $hrefs[ $i ] = [ 'url' => $filter, 'pre_filter_url' => $linkurl ];  //  will  not  be  filtered  again
                    $i--;
                    continue;
                }

                if( $filter !== true )
                {
                    continue;
                }

                $linkurl = $filter;
            }

            $this->scrape_url( $linkurl, $level + 1 );

            // check for CTRL-C

            if( SignalIsInstalled() && SignalQuitReceived() )
            {
                $this->quit = true;
                break;
            }
        }
    }



    protected function curl_or_fetch_cache( $url )
    {
        if( $this->cache_path === false )
        {
            return Curl( $url );
        }

        $resp = $this->cache_response_path_for_url( $url );
        $hdrs = $this->cache_headers_path_for_url ( $url );

        if( FSFileExists( $resp ) )
        {
            $result = [];
            $result['response'] = file_get_contents( $resp );
            $result['headers']  = json_decode( file_get_contents( $hdrs ), true );
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
                file_put_contents( $resp, $result['response'] );
                file_put_contents( $hdrs, json_encode( $result['headers'], JSON_PRETTY_PRINT ) );
                break;
            }

            for( $i = $this->pause; $i > 0; $i-- )
            {
                EchoCR( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] . " - pause... $i" );

                // check for CTRL-C

                if( SignalIsInstalled() && SignalQuitReceived() )
                {
                    $this->quit = true;
                    return $result;
                    /*--- EXIT POINT ---*/
                }

                sleep(1);
            }
        }

        return $result;
    }



    // return path to  base  file  path  for  a
    // given URL;                            \p

    protected function cache_base_path_for_url( $url )
    {
        $url = $this->lowercase_root( $url );
        $filename = md5( $url );
        $cache_file_path = $this->cache_path . "/" . $filename;

        return $cache_file_path;
    }



    // path to response cache file

    protected function cache_response_path_for_url( $url )
    {
        $path = $this->cache_base_path_for_url( $url );
        return "$path.response.txt";
    }



    // path to response-headers cache file

    protected function cache_headers_path_for_url( $url )
    {
        $path = $this->cache_base_path_for_url( $url );
        return "$path.headers.txt";
    }



    // save the url in the visited list

    protected function save_in_visited( $url )
    {
        $url = $this->lowercase_root( $url );
        if( ! in_array ( $url, $this->visited ) )
        {
            $this->visited[] = $url;
        }
    }



    // returns the scheme+domain

    protected function domain_from_url( $url )
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

    protected function lowercase_root( $url )
    {
        if( strlen( $url ) >= strlen( $this->root ) )
        {
            if( StringBegins( $url, $this->root, STRING_CI ) )
            {
                return StringReplaceAtBeginning( $url, $this->root, $this->root, STRING_CI );
            }
            return $url;
        }

        // the url is shorter than root

        if( StringBegins( $this->root, $url, STRING_CI ) )
        {
            return StringLowercase( $url );
        }
        return $url;
    }


    // is the absolute url below root

    protected function url_is_below_root( $url )
    {
        return StringBegins( $url, $this->root, STRING_CI );
    }



    // warn if a url with issues comes from the filter function

    protected function warn_if_url_comes_from_filter( $warn, $new, $original )
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

        TestBegin();

        Test( 'set_retry', $this->set_retry( 1, 8 ), $this->attempts === 1 && $this->pause === 8 );

        TestSummary();

    }
}
