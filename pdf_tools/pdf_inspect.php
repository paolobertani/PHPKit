<?php

//
// PdfInspect
//



require_once ROOT . '/include/pdf_tools/pdf_tools.php';



//
// PdfOffset
//

function PdfOffset( $heights, $products, $pdfidxPath )
{

    EchoNL( "Dumping `x` offsets for text height(s) $heights" );

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
    // Check height value
    //

    $heights = explode( ",", $heights );
    $n = count( $heights );
    for( $i = 0; $i < $n; $i++ )
    {
        if( ! ctype_digit( $heights[ $i ] ) )
        {
            Error( 'height value specified in `offset` must be a number' );
            /*--- EXIT POINT ---*/
        }
        $heights[ $i ] = (int)$heights[ $i ];
    }


    //
    // Search codes
    //

    $offsets = [];
    $n = count( $products );
    $i = 1;
    foreach( $products as $p )
    {
        EchoCR( "$i:$n" );

        $code = $p['code'];

        $code = $filter ? PdfImproveLinksFilter( $code ) : $code;

        $output = Execute( [ "pdfidxfind -limit 2500 -pdfidx", $pdfidxPath, "-search", $code ], $status );
        if( $status != 0 )
        {
            Error( "pdfidxfind exited with status $status: searching $code: $output" );
            /*--- QUIT POINT ---*/
        }

        $results = json_decode( $output, true );

        foreach( $results as $r )
        {
            if( ! in_array( $r['h'], $heights ) )
            {
                continue;
            }

            $x = $r['l'];

            if( ! isset( $offsets[$x] ) )
            {
                $offsets[$x] = [];
            }

            if( ! in_array( $r['p'], $offsets[$x] ) )
            {
                $offsets[$x][] = $r['p'];
            }
        }
        $i++;
    }


    //
    // Dump offsets and pages
    //

    foreach( $offsets as $x => $pages )
    {
        $i = 1;
        foreach( $pages as $p )
        {
            $p++;
            if( $i === 1 )
            {
                echo "\nX = $x: $p";
            }
            else
            {
                echo ", $p";
            }
            if( $i === 10 )
            {
                break;
            }
            $i++;
        }
    }
    echo "\n";
}


//
//
//



