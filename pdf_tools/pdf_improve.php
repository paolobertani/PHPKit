<?php

//
// PdfImprove
//
// Add links to a PDF document
//
// Params:
//          -pdf        path to PDF file
//          -res        path to web resources file
//          -out        path to PDF file with links to produce (opt.)
//          -cleanup    discard temp files (opt.)
//          -noimg      do not produce pdf with icons/images (opt.)
//          -offset     see "Offset" below
//
// Requirements:
//
// the resource file must be a tab separated  text
// file  witn  `\n`  line  separators.  The  first
// column holds the string that will  be  searched
// on the PDF. The first  line  contains  columns'
// header.                                      \x
//
// Must be defined:
//
// function PdfAddLinksProcess( (array)$resource, (array)$location ) --> (array|string)$link | false | []
//
// The function receives a resource record (as from the resource file)
// and the location `p`, `l`, `t`, `w`, `h` of the string found on the PDF.
// May return:
// false if no links have to be created.
// (string) the url to the link to create where the search string was found
// (array) associative with the keys `l`, `t`, `w`, `h`, `url`, optionally `p`
// (array) of associative arrays if two or more links have to be created
//
// May be defined:
//
// function PdfImproveLinksProcess( (string) $code ) --> (string) | false
//
// Receives a product code, returns  the  code  to
// search  for  (generally  the  same  code   with
// prepended a  modifier  search  character);  may
// return false to instruct to skip the code
//                                              \x
// May be defined:
//
// function PdfImproveResourcesManager( $resources ) --> (array)
//
// receives the products from the resource file as
// array  of  associative  arrays,   returns   the
// products array edited
//                                              \x
//
// Offset: the argument expect a value in the form
// `hh` where `hh` express a character  height  at
// 720 dpi; if `offset` is specified then a report
// is produced with the X offsets where the  codes
// (with specified height) are found on the  pages
// of the document. If `offset` is specified  then
// no output file is generated
//                                              \x



require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/arrays.php';
require_once ROOT . '/include/arguments.php';
require_once ROOT . '/include/filesystem.php';
require_once ROOT . '/include/milliseconds.php';

require_once ROOT . '/include/pdf_tools/pdf_tools.php';
require_once ROOT . '/include/pdf_tools/pdf_inspect.php';



//
// PdfImprove
//

function PdfImprove()
{
    //
    // Check PdfImproveLinksProcess is defined
    //

    if( ! function_exists( 'PdfImproveLinksProcess' ) )
    {
        Error( "PdfImproveLinksProcess function is not defined." );
        /*--- QUIT POINT ---*/
    }


    //
    // Check PdfImproveLinksFilter is defined
    //

    if( function_exists( 'PdfImproveLinksFilter' ) )
    {
        $filter = true;
    }
    else
    {
        $filter = false;
    }


    //
    // Check PdfImproveResourcesManager is defined
    //

    if( function_exists( 'PdfImproveResourcesManager' ) )
    {
        $resmanager = true;
    }
    else
    {
        $resmanager = false;
    }


    //
    // Get params
    //

    $pdfPath = ArgumentGet( 'pdf' );
    $resPath = ArgumentGet( 'res' );
    $dstPath = ArgumentGet( 'out',     ARGUMENT_OPTIONAL );
    $noimg   = ArgumentGet( 'noimg',   ARGUMENT_BOOLEAN );
    $offset  = ArgumentGet( 'offset',  ARGUMENT_OPTIONAL );


    //
    // Temp files paths
    //

    $linksPath      = ROOT . '/temp/temp.lks-0.txt';
    $pdfImagesPath  = ROOT . '/temp/temp.pdf-1-images.pdf';
    $pdfLinksPath   = ROOT . '/temp/temp.pdf-2-links.pdf';
    $pdfOutlinesPath= ROOT . '/temp/temp.pdf-3-outlines.pdf';


    //
    // File check
    //

    if( ! FileExists( $pdfPath ) || PathGetExtension( $pdfPath ) !== 'pdf' )
    {
        Error( "input pdf missing or not a pdf file: $pdfPath" );
        /*--- QUIT POINT ---*/
    }

    if( $dstPath !== false && $offset !== false )
    {
        EcnoNL( "`offset` option specified. no output file will be produced" );
    }

    if( $dstPath === false )
    {
        $dstPath = substr( $pdfPath, 0, -4 ) . '-improved.pdf';
    }

    if( $offset === false )
    {
        if( PathGetExtension( $dstPath ) !== 'pdf' )
        {
            Error( "output pdf has not pdf extension: $outPath" );
            /*--- QUIT POINT ---*/
        }

        if( $pdfPath === $dstPath ) // don't overwrite source
        {
            Error( "input and output pdf file must be different" );
            /*--- QUIT POINT ---*/
        }

        if( FileExists( $dstPath ) ) // overwrite
        {
            EchoNL( "output file exists, will be overwritten: $dstPath" );
            RemoveFile( $dstPath );
        }
    }


    //
    // Temp dir, pdfff and pdfidx
    //

    $pdfidxPath = PdfToolsPdfidx( $pdfPath );


    //
    // Parse Resources file
    //

    RemoveFile( $linksPath );

    $products = ArrayFromFile( $resPath );

    // Manage resource

    if( $resmanager )
    {
        $products = PdfImproveResourcesManager( $products );
    }

    EchoNL( ( count( $products ) ) . " links/products parsed" );


    //
    // OFFSET mode
    //

    if( $offset !== false )
    {
        PdfOffset( $offset, $products, $pdfidxPath );
        exit(0);
        /*--- QUIT POINT ---*/
    }

    //
    // Search for text to be linked, build links+images list
    //

    $linksList = [];
    $lnkHashes = [];
    $imgHashes = [];

    $i = 1;
    $n = count( $products );
    $milliseconds = 0;
    foreach( $products as $p )
    {
        EchoCR( "Searching for text to turn into links... $i:$n" );
        $i++;

        $code = $p['code'];

        // skip empty line (no code)

        if( $code === '' )
        {
            continue;
        }

        $text = array();

        if( $filter )
        {
            $code = PdfImproveLinksFilter( $code );
        }

        if( $code === false )
        {
            continue;
        }

        $ms = Milliseconds();
        $output = Execute( [ "pdfidxfind -limit 2500 -pdfidx", $pdfidxPath, "-search", $code ], $status );
        if( $status != 0 )
        {
            Error( "pdfidxfind exited with status $status: searching $code: $output" );
            /*--- QUIT POINT ---*/
        }
        $milliseconds += Milliseconds( $ms );

        $results = json_decode( $output, true );

        foreach( $results as $r )
        {
            $links = PdfImproveLinksProcess( $p, $r );

            // false: no links/images

            if( $links === false )
            {
                continue;
            }

            // empty array: no links/images

            if( is_array( $links ) && count( $links ) === 0 )
            {
                continue;
            }

            // a string: the string is the url, location is taken from the resource, no image

            if( is_string( $links ) )
            {
                $links = [ 'p' => $r['p'], 'l' => $r['l'], 't' => $r['t'], 'w' => $r['w'], 'h' => $r['h'], 'url' => $links, 'img' => '' ];
            }

            // a key-value pair array: this is a single link/image

            if( ! isset( $links[ 0 ] ) )
            {
                $links = [ $links ];
            }

            // for each link autocomplete the page, url, img if not present with their default values

            foreach( $links as $l )
            {
                if( ! isset( $l['l'] ) )
                {
                    $l['l'] = $r['l'];
                }

                if( ! isset( $l['t'] ) )
                {
                    $l['t'] = $r['t'];
                }

                if( ! isset( $l['w'] ) )
                {
                    $l['w'] = $r['w'];
                }

                if( ! isset( $l['h'] ) )
                {
                    $l['h'] = $r['h'];
                }



                if( ! isset( $l['p'] ) )
                {
                    $l['p'] = $r['p'];
                }


                if( ! isset( $l['img'] ) )
                {
                    $l['img'] = '';
                }


                if( ! isset( $l['url'] ) )
                {
                    $l['url'] = '';
                }


                if( ! isset( $l['z'] ) )
                {
                    $l['z'] = 0; // z-index
                }


                // raise a warning if both `url` and `img` are missing, skip the item

                if( $l['img'] === '' && $l['url'] === '' )
                {
                    EchoNL( "WARNING: no `img` and no `url` specified for code-search $code, in page " . ( $r['p'] + 1 ) );
                    continue;
                }


                // raise a warning for duplicate locations

                if( $l['url'] !== '' )
                {
                    $lnkHash = md5(  $l['p'] . "," . $l['l'] . "," . $l['t'] . "," . $l['w'] . "," . $l['h'] );

                    if( in_array( $lnkHash, $lnkHashes ) )
                    {
                        EchoNL( "WARNING: duplicate link location for code-search $code, in page " . ( $r['p'] + 1 ) );
                    }
                    else
                    {
                        $lnkHashes[] = $lnkHash;
                    }
                }

                if( $l['img'] !== '' )
                {
                    $imgHash = md5(  $l['p'] . "," . $l['l'] . "," . $l['t'] . "," . $l['w'] . "," . $l['h'] );

                    if( in_array( $imgHash, $imgHashes ) )
                    {
                        EchoNL( "WARNING: duplicate image location for code-search $code, in page " . ( $r['p'] + 1 ) );
                    }
                    else
                    {
                        $imgHashes[] = $imgHash;
                    }
                }


                // each link is finally added to the global list

                $linksList[] = $l;
            }
        }
    }


    //
    // Links/images list MUST be sorted by page
    //

    ArraySortByKey( $linksList, [ 'p', 'z', 't', 'l' ] );


    //
    // Build links/images output, check for images and urls
    //

    $linksText = "";

    $hasimg = false; // the links/images list specifies at least one image
    $hasurl = false; // the links/images list specifies at least one link

    foreach( $linksList as $l )
    {
        $linksText .= "{$l['p']}\t{$l['l']}\t{$l['t']}\t{$l['w']}\t{$l['h']}\t{$l['url']}\t{$l['img']}\n";

        if( $l['img'] !== '' )
        {
            $hasimg = true;
        }

        if( $l['url'] !== '' )
        {
            $hasurl = true;
        }

    }


    //
    // Write links file
    //

    $milliseconds = (int) ( $milliseconds / $n );
    EchoNL( "Search average time: $milliseconds ms" );
    EchoNL( "Writing links list file" );
    file_put_contents( $linksPath, $linksText );
    EchoNL( "Links count: " . count( $linksList ) );


    //
    // First input file
    //

    $inPath  = $pdfPath;
    $outPath = $pdfPath;


    //
    // Add images to PDF
    //

    if( $hasimg && ! $noimg )
    {
        $outPath = $pdfImagesPath;
        RemoveFile( $outPath );

        EchoCR( "Adding images to PDF..." );
        $output = Execute( [ "pdfAddImgs -pdf", $inPath, "-imgs", $linksPath, "-out", $outPath ], $status );
        if( $status != 0 )
        {
            Error( "pdfAddImgs exited with status $status: $output" );
            /*--- QUIT POINT ---*/
        }
        $relPath = PathRelative( $outPath );
        EchoNL( "Produced PDF with images: $relPath" );
    }

    if( ! $hasimg && ! $noimg )
    {
        EchoNL( "Resource file does not specify any image" );
    }


    //
    // Add PDF links to PDF
    //

    if( $hasurl )
    {
        $inPath = $outPath;
        $outPath = $pdfLinksPath;
        RemoveFile( $outPath );

        EchoCR( "Adding links to PDF..." );
        $output = Execute( [ "pdfAddLinks -pdf", $inPath, "-links", $linksPath, "-out", $outPath ], $status );
        if( $status != 0 )
        {
            Error( "pdfAddLinks exited with status $status: $output" );
            /*--- QUIT POINT ---*/
        }
        $relPath = PathRelative( $outPath );
        EchoNL( "Produced PDF with links: $relPath" );
    }
    else
    {
        EchoNL( "Resource file does not specify any link" );
    }


    //
    // Add Outlines to PDF
    //

    $outlinesPath = 'outlines.' . substr( $pdfPath, 0, -4 ) . '.txt';
    if( FileExists( $outlinesPath ) )
    {
        $inPath = $outPath;
        $outPath = $pdfOutlinesPath;
        RemoveFile( $outPath );

        EchoCR( "Adding outlines to PDF..." );
        $output = Execute( [ "pdfAddOutlines -pdf", $inPath, "-otl", $outlinesPath, "-out", $outPath ], $status );
        if( $status != 0 )
        {
            Error( "pdfAddOutlines exited with status $status: $output" );
            /*--- QUIT POINT ---*/
        }
        $relPath = PathRelative( $outPath );
        EchoNL( "Produced PDF with outlines: $relPath" );
    }
    else
    {
        EchoNL( "Outlines file not present, expected: " . PathRelative( $outlinesPath ) );
    }


    //
    // Take last produced file and copy to destination output file
    //

    CopyFile( $outPath, $dstPath );


    //
    // Discarding temporary files (only if tool was called with `cleanup` argument)
    //


    PdfToolsDeleteTempDir();


    //
    // Done
    //

    EchoNL( "Done" );
}