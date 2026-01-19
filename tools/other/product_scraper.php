<?php

//
//
// Product Scraper
//
//



//
// Includes
//

require_once ROOT . '/include/scraper.php';



class ProductScraper extends Scraper
{

    protected $products = [];

    private $file_mapping = [];



    // --- Override to provide a custom info string

    //
    // returns info  to  be  displayed  on  the
    // terminal during scraping              \p
    //

    protected function get_info( $url, $count, $level, $memory )
    {
        $n = count( $this->products );
        return "(Products: {$n}; Pages: $count; Level: $level; Memory: $memory MB) URL: $url";
    }



    // --- Override to implement custom duplicates deletion

    //
    // default function:  keep  the  code  with
    // more resources and shorter URL        \p
    //

    protected function duplicates()
    {
        ArrayRemoveDuplicates( $this->products, 'code', function( &$block )
        {
            for( $i = 0; $i < count( $block ); $i++ )
            {
                $score = 0;
                foreach( $block[$i] as $b )
                {
                    if( $b !== '' )
                    {
                        $score += 1000;
                    }
                }
                $score -= strlen( $block[$i]['url'] );
                $block[$i]['score'] = $score;
            }
        }, 'score' );
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
    //                                       \p

    /*
    protected function filter( $url ) { }
    */



    // --- Override to implement a contents processor

    //
    // process the response;
    // the function may return a string  or  an
    // array  of  strings   representing   urls
    // (aboslute o relative) to be scraped; the
    // function is  expected  to  populate  the
    // array     of     associative      arrays
    // $this->products                       \p

    /*
    protected function process( $url, $response, $headers, $dom, $is_html ) { }
    */



    //
    // map each resource key to a  filename  if
    // the filename begins with  `!`  then  the
    // file contents will be the resource value
    //                                       \p

    public function filemap( $map )
    {
        $this->file_mapping = $map;
    }



    //
    // scrape recursively the website  starting
    // from the url  provided  and  going  only
    // deeper and in the same domain         \p
    //

    /*
    public function scrape( $url ) { .... }
    */



    //
    // save resources file
    // download resources if requested
    //

    public function save()
    {
        if( ArrayHasDuplicates( $this->products, 'code' ) )
        {
            EchoNL( 'DUPLICATE codes found' );
            $this->duplicates();
        }
        else
        {
            EchoNL( 'no duplicate codes found' );
        }

        ArrayToFile( ROOT . "/resources.txt", $this->products );

        if( ! ArgumentGet( 'download', ARGUMENT_BOOLEAN ) )
        {
            return;
            /*--- EXIT POINT ---*/
        }

        // make resources directory if missing

        MakeDir( ROOT . "/resources" );

        // download resources

        foreach( $this->products as $product )
        {
            $codedir = StringReplace( $product['code'], "/", "|" );
            MakeDir( ROOT . "/resources/$codedir" );
            foreach( $product as $key => $value )
            {
                if( SignalIsInstalled() && SignalQuitReceived() )
                {
                    return;
                    /*--- EXIT POINT ---*/
                }

                if( isset( $this->file_mapping[$key] ) )
                {
                    $dest_file = $this->file_mapping[$key];
                    if( StringBegins( $dest_file, "!" ) )
                    {
                        $dest_file = substr( $dest_file, 1 );
                        file_put_contents( ROOT . "/resources/$codedir/$dest_file", $value );
                    }
                    else
                    {
                        $url = StringTrim( $value );
                        if( $url !== '' && ! FileExists( ROOT . "/resources/$codedir/$dest_file" ) )
                        {
                            EchoCR( "Code: {$product['code']} - Downloading: $url" );
                            for( $attempts = 0; $attempts < $this->attempts; $attempts++ )
                            {
                                $result = Curl( $url );
                                if( $result['status'] < 300 && $result['error'] == '' )
                                {
                                    file_put_contents( ROOT . "/resources/$codedir/$dest_file", $result['response'] );
                                    break;
                                }

                                if( $attempts === $this->attempts - 1 )
                                {
                                    EchoNL( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] );
                                }
                                else
                                {
                                    for( $i = $this->pause; $i > 0; $i-- )
                                    {
                                        EchoCR( "Failed loading $url - Status: " . $result['status'] . " - Error: " . $result['error'] . " - pause... $i" );
                                        sleep(1);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }



    //
    // cleanup
    //

    /*
    public function done() { .... }
    */


}
