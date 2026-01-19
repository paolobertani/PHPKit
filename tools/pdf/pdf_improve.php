<?php if( isset( $argv[ 1 ] ) && $argv[ 1 ] === '-h' ) { echo <<<HEREDOC

   PdfImprove

   Add links to a PDF document and register the assets



   Params:
            -pdf        path to PDF file
            -prd        path to products file, with associated resources
            -out        path to PDF file with links to produce (opt.)
            -cleanup    discard temp files (opt.)
            -noimg      do not produce pdf with icons/images (opt.)
            -geto       see "Offset" below
            -geth       see "Height" below
            -tol        see "Tolerance"
            -code       see "Code" below
            -compress   compress the produced file using `cpdf`

                    or alone

            -register   to register the assets file on Pinaxo

                    or alone

            -h          display usage

   Requirements:

   the products file must be a tab separated  text
   file generated with `ArrayToFile`.

   Relevant columns are:
   `code` the product code;
   `code_id` the product code -registered- `id`;
   `brand_id` the brand id of the producer on Px;

   Resource columns with URLs must be named  using
   the resource type code:
   web - drw - ins - tec - pho - sht - spa - m2d -
   m3d - amb

   For each resource column the resource file type
   must be specificed in a column named type_{rtc}



   Must be defined:

   function PdfImproveLinksProcess( (array)\$product, (array)\$location ) --> array[array] | false | []

   receives the record for a given  product/item.
   Receives the  location  where  the  resource's
   code was found as associative array with  keys
   `p`, `l`, `t`, `w`, `h`;
   the function may return:`false` nothing to do;
   array of associative arrays each one with  the
   following keys:
   `p` (opt): page number
   `l`, `t`, `w`, `h` (opt): location on the page
   `z` (opt): z-index of the image
   `img` (opt): path to the image to be applied
   `res` (opt): create a link to the resource  of
   type specified;
   either `url` or `img` should be specified;
   values for `res`:
   "web": product web page
   "sht": product sheet
   "tec": technical sheet
   "ins": installation instruct
   "spa": spare parts
   "drw": 2d drawing
   "m3d": 3d model
   "m2d": 2d model
   "pho": photo
   "amb": photo of ambientation



   May be defined:

   function PdfImproveLinksFilter( (string) \$code ) --> (string) | false

   Receives a product code, returns  the  code  to
   search  for  (generally  the  same  code   with
   prepended a  modifier  search  character);  may
   return false to instruct to skip the code    \x



   May be defined:

   function PdfImproveResultsFilter( (string)\$search, (array)\$results ) --> (array) | false

   Receives  the  search  query  (as   passed   to
   pdfidxfind) an the search results  as  returned
   by pdfidxfind and is  expected  to  return  the
   same set or a subset; the returned results will
   be  passed  to  PdfAddLinksProces;  may  return
   `false` as an alias to a empty array         \x



   DOCUMENT INSPECTION

   Height: the argument `geth`  does  not  require
   any value; when specified a report is  produced
   with all the character heights at 720dpi of the
   codes found in the document; along  with  every
   "height" found, the  pages  containing  one  or
   more product codes with that height are listed.

   'geto`: the argument expect a value in the form
   `hh` where `hh` express a character  height  at
   720 dpi;  if  `geto` is specified then a report
   is produced with the X offsets where the  codes
   (with specified height) are found on the  pages
   of the document.   If `geto` is specified  then
   no output file is generated; several values may
   be specified separated by comma: hh1,hh2,...

   Tolerance: `tol` expect a value that  represent
   a 720dpi measure;  when  specified  along  with
   `offset` or `height` the reports produced group
   heights/offsets that differs equal or less  the
   value specified (they fit into the tolerance).

   Code: `code` let the  Offsets report  (argument
   `height`) produce also  code  for  setting  the
   icons   offsets   for   each   combination   of
   product-code x position and height

HEREDOC . "\n"; exit( 0 ); }



require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/arrays.php';
require_once ROOT . '/include/arguments.php';
require_once ROOT . '/include/fs.php';
require_once ROOT . '/include/milliseconds.php';
require_once ROOT . '/include/strings.php';

require_once ROOT . '/include/pdf_tools/pdf_tools.php';
require_once ROOT . '/include/pdf_tools/pdf_inspect.php';

require_once ROOT . '/include/pinaxo/assets.php';

require_once ROOT . '/include/pinaxo/public_api.php';
require_once ROOT . '/include/../pinaxo_private/apitoken.php';

require_once ROOT . '/include/3rd-parts/fpdf/fpdf.php';



//
// Exclude the whole script's dir from TM backups
//

FSTMExclude( ROOT );



//
// REGISTER ASSETS AND QUIT MAYBE
//

if( ArgumentGet( '-register', ARGUMENT_BOOLEAN ) )
{
    RegisterAssetsPrivate();
    exit( 0 );
}


//
// UPLOAD FILE AND QUIT MAYBE
//

if( ArgumentGet( '-upload', ARGUMENT_BOOLEAN ) )
{
    UploadFilePrivate();
    exit( 0 );
}


//
// Globals
//

$g_pdf_improve_document_inspection = false;



//
// PdfImprove
//

function PdfImprove()
{
    global $g_pdf_improve_document_inspection;

    $successful_searches = 0;
    $links_sets_produced = 0;


    //
    // Check PdfImproveLinksProcess is defined
    //

    if( ! function_exists( 'PdfImproveLinksProcess' ) )
    {
        Error( "`PdfImproveLinksProcess()` function is not defined." );
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
    // Check PdfImproveResultsFilter is defined
    //

    if( function_exists( 'PdfImproveResultsFilter' ) )
    {
        $results_filter = true;
    }
    else
    {
        $results_filter = false;
    }


    //
    // Pinaxo Assets Interface
    //

    $pinaxoAssets = new PinaxoAssets();


    //
    // Discard temporary files maybe (only if tool was called with `cleanup` argument)
    //


    PdfToolsDeleteTempDir();


    //
    // Get params
    //

    if( ArgumentGet( 'res', ARGUMENT_OPTIONAL ) !== false ) Error( "PDF Improve: `-res` argument is no longer in use; use `-prd` instead" );
    $pdfPath = ArgumentGet( 'pdf' );
    $prdPath = ArgumentGet( 'prd' );
    $dstPath = ArgumentGet( 'out',     ARGUMENT_OPTIONAL );
    $noimg   = ArgumentGet( 'noimg',   ARGUMENT_BOOLEAN );
    $offset  = ArgumentGet( 'geto',    ARGUMENT_OPTIONAL );
    $height  = ArgumentGet( 'geth',    ARGUMENT_BOOLEAN );
    $compress= ArgumentGet( 'compress',ARGUMENT_BOOLEAN );


    //
    // Get document id from directory name
    //

    $document_id = GetDocumentIDPrivate( $unused );


    //
    // Maybe publish a new document
    //

    if( $document_id === false )
    {
        $working_brand_id = false;
        $prm = 'working_brand_id=';
        $dd = FSDirectoriesInDirectory( ROOT );
        foreach( $dd as $d ) if( substr( $d, 0, strlen( $prm ) ) === $prm ) $working_brand_id = intval( substr( $d, strlen( $prm ) ) );
        if( $working_brand_id === false ) Error( "PDF Improve: working brand id not specified; create a directory named `working_brand_id=<id>`" );
        $text = explode( '/', ROOT );
        $text = $text[ count( $text ) - 1 ];
        $pdf_ph_path = FSRoot( 'placeholder.pdf' );
        FSRemoveFile( $pdf_ph_path );
        $pdf_ph = new FPDF();
        $pdf_ph->AddPage();
        $pdf_ph->SetFont( 'Courier', '', 16 );
        $pdf_ph->SetXY( 10, 50 );
        $pdf_ph->Cell( min( 20, $pdf_ph->GetStringWidth( $text ) ), 20, $text );
        $pdf_ph->Output( 'F', $pdf_ph_path, true );
        $api_session = new PinaxoApiSession( PINAXO_API_TOKEN );
        $api_session->documents_post( [ 'description' => $text, 'title' => $text, 'type' => 'L', 'brand_id' => $working_brand_id, 'category_id' => 9, 'hd' => 1 ] );
        if( $api_session->status >= 300 ) Error( "PDF Improve: failed to create new document\n{$api_session->response_as_text}" );
        if( ! isset( $api_session->response[ 'document_id' ] ) ) Error( "PDF Improve: api user needs `admin` privileges" );
        $pdf_ph_document_id = $api_session->response[ 'document_id' ];
        $pdf_ph_public_id   = $api_session->response[ 'public_id' ];
        $api_session->documents_pdf_put( $pdf_ph_public_id, $pdf_ph_path );
        if( $api_session->status >= 300 ) Error( "PDF Improve: failed to upload document's pdf\n{$api_session->response_as_text}" );
        FSMakeDir( FSRoot( "working_document_id=$pdf_ph_document_id") );
        FSRemoveFile( $pdf_ph_path );
        EchoNL( "Created placeholder document with id = $pdf_ph_document_id" );
        $document_id = $pdf_ph_document_id;
    }


    //
    // Temp files paths
    //

    $linksPath      = PdfToolsTempFileLinks();
    $pdfImagesPath  = PdfToolsTempFilePdfIm();
    $pdfLinksPath   = PdfToolsTempFilePdfLk();
    $pdfOutlinesPath= PdfToolsTempFilePdfOL();


    //
    // File & arguments check
    //

    if( ! FSFileExists( $pdfPath ) || FSPathGetExtension( $pdfPath ) !== 'pdf' )
    {
        Error( "input pdf missing or not a pdf file: $pdfPath" );
        /*--- QUIT POINT ---*/
    }

    if( $height !== false && $offset !== false )
    {
        EchoNL( "cannot have both `height` and `offset` arguments in tool call" );
        exit(0);
        /*--- QUIT POINT ---*/
    }

    if( $dstPath !== false && $offset !== false )
    {
        EchoNL( "`offset` option specified. no output file will be produced" );
    }

    if( $dstPath !== false && $height !== false )
    {
        EchoNL( "`height` option specified. no output file will be produced" );
    }

    if( $dstPath === false )
    {
        $dstPath = substr( $pdfPath, 0, -4 ) . '.improved.pdf';
    }

    $cmpPath = substr( $dstPath, 0, -4 ) . '.compressed.pdf';

    if( $offset === false && $height === false )
    {
        if( FSPathGetExtension( $dstPath ) !== 'pdf' )
        {
            Error( "output pdf has not pdf extension: $dstPath" );
            /*--- QUIT POINT ---*/
        }

        if( $pdfPath === $dstPath ) // don't overwrite source
        {
            Error( "input and output pdf file must be different: $pdfPath" );
            /*--- QUIT POINT ---*/
        }

        if( $pdfPath === $cmpPath && $compress) // don't overwrite source
        {
            Error( "compressed file would overwrite source: $pdfPath" );
            /*--- QUIT POINT ---*/
        }

        if( FSFileExists( $dstPath ) || FSFileExists( $cmpPath ) ) // overwrite
        {
            EchoNL( "output file exists, will be overwritten: $dstPath" );
            FSRemoveFile( $dstPath );
            FSRemoveFile( $cmpPath );
        }
    }


    //
    // Temp dir, pdfff and pdfidx
    //

    $pdfidxPath = PdfToolsPdfidx( $pdfPath );


    //
    // Parse Products file
    //

    $products = ArrayFromFile( $prdPath );
    EchoNL( ( count( $products ) ) . " links/products parsed" );


    //
    // OFFSET mode
    //

    if( $offset !== false )
    {
        $g_pdf_improve_document_inspection = true;
        PdfOffset( $offset, $products, $pdfidxPath );
        exit(0);
        /*--- QUIT POINT ---*/
    }


    //
    // HEIGHT mode
    //

    if( $height !== false )
    {
        $g_pdf_improve_document_inspection = true;
        PdfHeight( $products, $pdfidxPath );
        exit(0);
        /*--- QUIT POINT ---*/
    }


    if( ! FSFileExists( $linksPath ) )
    {
        //
        // Search for text to be linked, build links+images list
        //

        $linksList = [];

        $i = 1;
        $n = count( $products );
        $milliseconds = 0;
        foreach( $products as $p )
        {
            EchoCR( "Searching for text to turn into links... $i:$n" );
            $i++;

            $code = $p[ 'code' ];

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

            if( is_array( $code ) )
            {
                Error( 'PdfImproveLinksFilter returned array' );
                /*--- QUIT POINT ---*/
            }

            $ms = Milliseconds();
            $getText = $results_filter ? "-text yes " : "";

            $output = FSExecute( [ "pdfidxfind $getText-limit 2500 -pdfidx", $pdfidxPath, "-search", $code ], $status, true /**/ );

            if( $status != 0 )
            {
                Error( "pdfidxfind exited with status $status: searching $code: $output" );
                /*--- QUIT POINT ---*/
            }
            $milliseconds += Milliseconds( $ms );

            $results = json_decode( $output, true );

            $successful_searches += ( count( $results ) > 0 ) ? 1 : 0;


            //
            // Filter the whole set of results (if the filter function is defined)
            //

            if( $results_filter && count( $results ) > 0 )
            {
                $results = PdfImproveResultsFilter( $code, $results );
                if( $results === false )
                {
                    $results = [];
                }
            }


            //
            // Pass each result to the icon-link generator function
            //

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

                // no array: raise error

                if( ! is_array( $links ) )
                {
                    Error( 'PdfImproveLinksProcess must return `false`, a empty array or an array of associative arrays' );
                }

                $links_sets_produced += ( count( $links ) > 0 ) ? 1 : 0;

                // for each link autocomplete the page, url, img if not present with their default values

                foreach( $links as $l )
                {
                    $l['src'] = $code; // the search performed

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

                    if( ! isset( $l['res'] ) )
                    {
                        $l['res'] = '';
                    }

                    if( ! isset( $l['z'] ) )
                    {
                        $l['z'] = 0; // z-index
                    }

                    $l['hash'] = md5(  $l['p'] . "," . $l['l'] . "," . $l['t'] . "," . $l['w'] . "," . $l['h'] );

                    // raise a warning if both `res` and `img` are missing, skip the item

                    if( $l['img'] === '' && $l['res'] === '' )
                    {
                        EchoNL( "WARNING: no `img` and no `res` specified for code-search $code, in page " . ( $r['p'] + 1 ) );
                        continue;
                    }

                    // Fetch URL and other resource info from resource record

                    $res = $l['res'];
                    $err_trailer = "for code-search $code, in page " . ( $r['p'] + 1 );

                    if( $res !== '' )
                    {
                        if( ! isset( $p[ "$res"                      ] ) ) Error( "Unavailable resource `$res` $err_trailer"                              );
                        if( ! isset( $p[ "resource_type_id_for_$res" ] ) ) Error( "Unavailable resource type id for resource of type `$res` $err_trailer" );
                        if( ! isset( $p[ "file_type_id_for_$res"     ] ) ) Error( "Unavailable file type id for resource of type `$res` $err_trailer"     );
                        if( ! isset( $p[ "language_id"               ] ) ) Error( "Missing `language_id` $err_trailer"                                    );
                        if( ! isset( $p[ "code_id"                   ] ) ) Error( "Missing `code_id` $err_trailer"                                        );
                        if( ! isset( $p[ "code"                      ] ) ) Error( "Missing `code` $err_trailer"                                           );
                        if( ! isset( $p[ "brand_id"                  ] ) ) Error( "Missing `brand_id` $err_trailer"                                       );

                        $l[ 'url'              ] = $p[ "$res"                       ];
                        $l[ 'resource_type_id' ] = $p[ "resource_type_id_for_$res"  ];
                        $l[ 'file_type_id'     ] = $p[ "file_type_id_for_$res"      ];
                        $l[ 'language_id'      ] = $p[ 'language_id'                ];
                        $l[ 'product_code_id'  ] = $p[ 'code_id'                    ];
                        $l[ 'product_code'     ] = $p[ 'code'                       ];
                        $l[ 'brand_id'         ] = $p[ 'brand_id'                   ];
                        $l[ 'document_id'      ] = $document_id;
                        $l[ 'value'            ] = $pinaxoAssets->value( $l );
                        $l[ 'pinaxo_url'       ] = "https://www.pinaxo.com/asset/{$l['value']}";
                    }
                    else
                    {
                        $l[ 'url' ]              = '';
                        $l[ 'resource_type_id' ] = 0;
                        $l[ 'file_type_id'     ] = 0;
                        $l[ 'language_id'      ] = 0;
                        $l[ 'product_code_id'  ] = 0;
                        $l[ 'product_code'     ] = '';
                        $l[ 'brand_id'         ] = 0;
                        $l[ 'document_id'      ] = 0;
                        $l[ 'value'            ] = '';
                        $l[ 'pinaxo_url'       ] = '';
                    }

                    // each link is finally added to the global list

                    $linksList[] = $l;

                }
            }

        }


        //
        // Check for duplicate locations
        //

        $duplicates = [];

        ArrayRemoveDuplicates( $linksList, 'hash', function( &$block ) use (&$duplicates) { $duplicates = array_merge( $duplicates, $block ); } );

        if( count( $duplicates ) > 0 )
        {
            EchoNL( "Images/Links in duplicate positions, produced `duplicates.txt` file" );
            ArrayToFile( ROOT . "/duplicates.txt",  $duplicates );
            exit( 0 );
            /*--- QUIT POINT ---*/
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
            $linksText .= "{$l['p']}\t{$l['l']}\t{$l['t']}\t{$l['w']}\t{$l['h']}\t{$l['pinaxo_url']}\t{$l['img']}\n";

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
        EchoNL( "Successful searches: $successful_searches ");
        EchoNL( "Links sets produced: $links_sets_produced ");


        //
        // Write assets file
        //

        EchoNL( "Writing assets file" );
        ArrayToFile( ROOT . '/assets.txt', $linksList );

    }
    else
    {
        EchoNL( "Using existing links-images file: " . FSPathRelative( $linksPath ) );
        EchoNL( "Keeping assets file" );

        // Inspect file to detect links and/or images

        $hasimg = false;
        $hasurl = false;

        $linksText = file_get_contents( $linksPath );
        $linksList = explode( "\n", $linksText );
        foreach( $linksList as $row )
        {
            $parts = explode( "\t", $row );
            $n = count( $parts );
            if( $n >= 6 && $parts[ 5 ] !== '' ) { $hasurl = true; }
            if( $n >= 7 && $parts[ 6 ] !== '' ) { $hasimg = true; }
        }
    }


    //
    // Small report
    //


    EchoNL( "Links:  " . ( $hasurl ? "YES" : "NO" ) );
    EchoNL( "Images: " . ( $hasimg ? "YES" : "NO" ) );


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
        $inPath = $outPath;
        $outPath = $pdfImagesPath;
        $relPath = FSPathRelative( $outPath );

        if( FSFileExists( $outPath ) )
        {
            EchoNL( "Using existing PDF with images: $relPath" );
        }
        else
        {
            EchoCR( "Adding images to PDF..." );
            $output = FSExecute( [ "pdfAddImgs -pdf", $inPath, "-imgs", $linksPath, "-out", $outPath ], $status );
            if( $status != 0 )
            {
                Error( "pdfAddImgs exited with status $status: $output" );
                /*--- QUIT POINT ---*/
            }
            EchoNL( "Produced PDF with images: $relPath" );
        }
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
        $relPath = FSPathRelative( $outPath );

        if( FSFileExists( $outPath ) )
        {
            EchoNL( "Using existing PDF with links: $relPath" );
        }
        else
        {
            EchoCR( "Adding links to PDF..." );
            $output = FSExecute( [ "pdfAddLinks -pdf", $inPath, "-links", $linksPath, "-out", $outPath ], $status );
            if( $status != 0 )
            {
                Error( "pdfAddLinks exited with status $status: $output" );
                /*--- QUIT POINT ---*/
            }
            EchoNL( "Produced PDF with links: $relPath" );
        }
    }
    else
    {
        EchoNL( "Resource file does not specify any link" );
    }


    //
    // Add Outlines to PDF
    //

    $outlinesPath = FSPathEditFilename( $pdfPath, 'outlines.', '', "txt" ); // try as a variation of the source pdf
    if( ! FSFileExists( $outlinesPath ) )
    {
        $filepaths = FSFilesInDirectory( ROOT, FS_FULLPATH ); // search the files in the root directory
        foreach( $filepaths as $p )
        {
            if( StringBegins( FSPathGetFilename( $p ), 'outlines' ) )
            {
                $outlinesPath = $p;
                break;
            }
        }
    }

    if( FSFileExists( $outlinesPath ) )
    {
        $inPath = $outPath;
        $outPath = $pdfOutlinesPath;
        $relPath = FSPathRelative( $outPath );

        if( FSFileExists( $outPath ) )
        {
            EchoNL( "Using existing PDF with outlines: $relPath" );
        }
        else
        {
            EchoCR( "Adding outlines to PDF..." );
            $output = FSExecute( [ "pdfAddOutlines -pdf", $inPath, "-otl", $outlinesPath, "-out", $outPath ], $status );
            if( $status != 0 )
            {
                Error( "pdfAddOutlines exited with status $status: $output" );
                /*--- QUIT POINT ---*/
            }
            $relPath = FSPathRelative( $outPath );
            EchoNL( "Produced PDF with outlines: $relPath" );
            if( $output !== '' )
            {
                echo "pdfAddOutlines messages:\n$output";
                if( ! StringEnds( $output, "\n" ) )
                {
                    echo "\n";
                }
            }
        }
    }
    else
    {
        EchoNL( "Outlines file not present, expected: '[ROOT]/" . FSPathRelative( $outlinesPath ) . "' -or- '[ROOT]/outlines*.txt'" );
    }


    //
    // Take last produced file and copy to destination output file
    //

    FSCopyFile( $outPath, $dstPath );


    //
    // Compress produced file maybe
    //

    if( $compress )
    {
        EchoCR( 'Compressing file...' );
        $output = FSExecute( [ '/usr/local/bin/cpdf -squeeze', $dstPath, '-o', $cmpPath ], $exitStatus );
        $output = "Compressing file: " . ( $exitStatus == 0 ? 'done' : 'FAILED' ) . "\n--------\n    " . trim( str_replace( "\n", "\n    ", $output ) ) . "\n--------";
        EchoNL( $output );
    }


    //
    // Done
    //

    EchoNL( "Done" );
}



//
// PdfIsInspecting
//
// Return `true` if  PdfImprove()  is  running  in
// document inspection mode                     \x
//

function PdfInspectionMode()
{
    global $g_pdf_improve_document_inspection;
    return $g_pdf_improve_document_inspection;
}



//
// Register assets into db loading them from assets file
//

function RegisterAssetsPrivate()
{

    // Load file

    $path = ROOT . '/assets.txt';

    if( ! FSFileExists( $path ) )
    {
        Error( "Assets file not found: $path" );
    }

    $assets = ArrayFromFile( $path );


    // Pinaxo assets interface

    $pinaxoAssets = new PinaxoAssets();


    // File check

    $row = 0;
    foreach( $assets as $a )
    {
        $row++;
        if( ! isset(  $a[ 'document_id'      ] ) ) Error( "`document_id` not specified on row $row"       );
        if( ! isset(  $a[ 'product_code_id'  ] ) ) Error( "`product_code_id` not specified on row $row"   );
        if( ! isset(  $a[ 'resource_type_id' ] ) ) Error( "`resource_type_id` not specified on row $row"  );
        if( ! isset(  $a[ 'file_type_id'     ] ) ) Error( "`file_type_id` not specified on row $row"      );
        if( ! isset(  $a[ 'language_id'      ] ) ) Error( "`language_id` not specified on row $row"       );
        if( ! isset(  $a[ 'brand_id'         ] ) ) Error( "`brand_id` not specified on row $row"          );
        if( ! isset(  $a[ 'value'            ] ) ) Error( "`value` not specified on row $row"             );
        if( ! isset(  $a[ 'url'              ] ) ) Error( "`url` not specified on row $row"               );
        if( ! isset(  $a[ 'pinaxo_url'       ] ) ) Error( "`pinaxo_url` not specified on row $row"        );
        if( ! isset(  $a[ 'product_code'     ] ) ) Error( "`product_code` not specified on row $row"      );
        if( ! is_int( $a[ 'document_id'      ] ) ) Error( "`document_id` is not integer on row $row"      );
        if( ! is_int( $a[ 'product_code_id'  ] ) ) Error( "`product_code_id` is not integer on row $row"  );
        if( ! is_int( $a[ 'resource_type_id' ] ) ) Error( "`resource_type_id` is not integer on row $row" );
        if( ! is_int( $a[ 'file_type_id'     ] ) ) Error( "`file_type_id` is not integer on row $row"     );
        if( ! is_int( $a[ 'language_id'      ] ) ) Error( "`language_id` is not integer on row $row"      );
        if( ! is_int( $a[ 'brand_id'         ] ) ) Error( "`brand_id` is not integer on row $row"         );
        if(!is_string($a[ 'product_code'     ] ) ) Error( "`product_code` is not string on row $row"      );
        if(!is_string($a[ 'value'            ] ) ) Error( "`value` is not string on row $row"             );
        if(!is_string($a[ 'url'              ] ) ) Error( "`url` is not string on row $row"               );
        if(!is_string($a[ 'pinaxo_url'       ] ) ) Error( "`pinaxo_url` is not string on row $row"        );

        if( $a[ 'value' ] !== '' )
        {
            if( $a[ 'value' ] !== $pinaxoAssets->value( $a ) ) Error( "bad asset value on row $row: {$a['value']}" );
            if( $a[ 'pinaxo_url' ] !== "https://www.pinaxo.com/asset/{$a['value']}" ) Error( "bad Pinaxo URL on row $row: {$a['pinaxo_url']} -VS- https://www.pinaxo.com/asset/{$a['value']}" );
            if( substr( $a[ 'url' ], 0, 8 ) !== 'https://' && substr( $a[ 'url' ], 0, 7 ) !== 'http://' ) Error( "bad target URL on row $row: {$a['url']}" );

            if( 0===( $a[ 'document_id'      ] ) ) Error( "`document_id` is `0` zero on row $row"         );
            if( 0===( $a[ 'resource_type_id' ] ) ) Error( "`resource_type_id` is `0` zero on row $row"    );
            if( 0===( $a[ 'file_type_id'     ] ) ) Error( "`file_type_id` is `0` zero on row $row"        );
            if( 0===( $a[ 'language_id'      ] ) ) Error( "`language_id` is `0` zero on row $row"         );
            if( 0===( $a[ 'brand_id'         ] ) ) Error( "`brand_id` is `0` zero on row $row"            );
            if( 0===( $a[ 'product_code_id'  ] ) ) Error( "`product_code_id` is `0` zero on row $row" .
                                                        ( $row === 1 ? "\n🤔 maybe you forgot to invoke the scraper with `-register` in order to register the product code?" : "" ) );
        }
    }


    // Filter assets to actually register

    $data = []; foreach( $assets as $a ) if( $a[ 'value' ] !== '' ) $data[] = $a;
    if( count( $data ) === 0 ) { EchoNL( 'no assets to register' ); exit( 0 );  }


    // Remove duplicate assets but first check that values for the relevant keys are the same

    ArrayRemoveDuplicates( $data, 'value', function( $duplicates )
    {
        $keys = [
            'url',
            'resource_type_id',
            'file_type_id',
            'language_id',
            'product_code_id',
            'product_code',
            'brand_id',
            'document_id',
            'pinaxo_url'
        ];

        foreach( $duplicates as &$row )
        {
            $row[ 'score' ] = 0;
            foreach( $keys as $key )
            {
                if( $row[ $key ] !== $duplicates[ 0 ][ $key ] )
                {
                    Error( "Found duplicate asset with different values: asset-value = {$duplicates[0]['value']}\n" . StringJSON( $duplicates[ 0 ] ) . "\n - - -\n" . StringJSON( $row ) );
                }
            }
        } unset( $row );
        $duplicates[ 0 ][ 'score' ] = 1;
    } );


    // Register assets

    $pinaxoAssets->register( $data );


    // Done

    EchoNL( 'Done' );
    exit( 0 );
}



function UploadFilePrivate()
{
    $document_id = GetDocumentIDPrivate( $x );
    if( $document_id === false ) Error( "cannot retrieve document id from directory named $x<document_id>" );


    // search file to upload in default `2. postflight` directory

    $uplPath = false;
    $dd = FSFilesInDirectory( FSRoot( '2. postflight' ), FS_FULL_PATH );
    foreach( $dd as $d )
    {
        if( StringEnds( $d, '.improved.compressed.pdf' ) ) $uplPath = $d;
        if( StringEnds( $d, '.improved.pdf' ) && $uplPath === false ) $uplPath = $d;
    }
    if( $uplPath === false ) Error( "cannot find document to upload in directory `2. postfight`:\n" . implode( "\n", $dd ) );


    // up the tube

    $api_session = new PinaxoApiSession( PINAXO_API_TOKEN );
    $api_session->documents_get( $document_id );
    if( $api_session->status >= 300 ) Error( "failed fetching document data; status: {$api_session->status}\n{$api_session->response_as_text}" );
    if( ! isset( $api_session->response[ 'public_id' ] ) ) Error( "cannot fetch public_id from response:\n{$api_session->response_as_text}" );
    if( $api_session->response[ 'lock' ] !== '' ) Error( "document is locked" );
    EchoCR( "Uploading $uplPath to {$api_session->response['brand']} :: {$api_session->response['description']}..." );
    $api_session->documents_pdf_put( $api_session->response[ 'public_id' ], $uplPath );
    if( $api_session->status >= 300 ) EchoNL( "Uploading $uplPath: FAILED with status {$api_session->status}\n{$api_session->response_as_text}" );
    else                              EchoNL( "Uploading $uplPath: done" );
}


function GetDocumentIDPrivate( &$prm )
{
    $document_id = false;
    $dd = FSDirectoriesInDirectory( ROOT );
    $prm = 'working_document_id=';
    foreach( $dd as $d ) if( substr( $d, 0, strlen( $prm ) ) === $prm ) $document_id = intval( substr( $d, strlen( $prm ) ) );
    return $document_id;
}





