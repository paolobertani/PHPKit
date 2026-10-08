<?php

/*
 *
 *  PdfInspect
 *
 */



require_once ROOT . '/include/pdf_tools/pdf_tools.php';



/*
 *
 *  PdfOffset
 *
 */

function PdfOffset( $heights, $products, $pdfidxPath )
{
    /*
     *
     *  Check for tolerance argument
     *
     */

    $tolerance = ArgumentGet( 'tol', ARGUMENT_OPTIONAL );

    if( $tolerance === false )
    {
        $tolerance = "0";
    }

    if( ! ctype_digit( $tolerance ) )
    {
        Error( 'tolerance value specified in `tol` must be a number' );
        /*--- EXIT POINT ---*/
    }

    $tolerance = (int)$tolerance;


    /*
     *
     *  Show report type
     *
     */

    EchoNL( "Dumping `x` offsets for text height(s) $heights; tolerance = $tolerance" );


    /*
     *
     *  Check PdfImproveLinksFilter is defined
     *
     */

    if( function_exists( 'PdfImproveLinksFilter' ) )
    {
        $filter = true;
    }
    else
    {
        $filter = false;
    }


    /*
     *
     *  Check height value
     *
     */

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


    /*
     *
     *  Search codes
     *
     */

    $offsets = [];
    $n = count( $products );
    $i = 1;
    foreach( $products as $p )
    {
        EchoCR( "$i:$n" );
        $i++;

        $code = $p['code'];

        $code = $filter ? PdfImproveLinksFilter( $code ) : $code;

        if( $code === false )
        {
            continue;
        }

        $output = FSExecute( [ "pdfidxfind -limit 2500 -pdfidx", $pdfidxPath, "-search", $code ], $status );
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
    }


    /*
     *
     *  Dump offsets and pages
     *
     */

    ksort( $offsets );

    if( $tolerance === 0 )
    {
        foreach( $offsets as $x => $pages )
        {
            sort( $pages );
            $i = 1;
            $np = count( $pages );
            $np = $np <= 1 ? '' : " ($np pages)";
            foreach( $pages as $p )
            {
                $p++;
                if( $i === 1 )
                {
                    echo "\nX = $x$np: $p";
                }
                else
                {
                    echo ", $p";
                }
                if( $i === 30 )
                {
                    echo "...";
                    break;
                }
                $i++;
            }
        }
        echo "\n";

        if( ArgumentGet( 'code', ARGUMENT_BOOLEAN ) )
        {
            if( count( $heights ) === 1 )
            {
                $heights = $heights[0];
            }
            else
            {
                $heights = "[" . implode( ", ", $heights ) . "]";
            }

            foreach( $offsets as $x => $pages )
            {
                echo '$'."offsets[] = PdfMakeOffset( $heights, $x, $x, 0, 0, 1 ); // ";
                $i = 1;
                foreach( $pages as $p )
                {
                    $p++;
                    echo " $p";
                    $i++;
                    if( $i === 10 )
                    {
                        echo "...";
                        break;
                    }
                }
                echo "\n";
            }
        }

        return;
        /*--- EXIT POINT ---*/
    }


    /*
     *
     *  Group offsets inside tolerance
     *
     */

    $goff = [];
    $min = -99999;
    foreach( $offsets as $x => $pages )
    {
        if( $x - $min > $tolerance )
        {
            $goff[] = [ 'min' => $x, 'max' => $x, 'pages' => $pages ];
            $min = $x;
        }
        else
        {
            $g = $goff[ count( $goff ) - 1 ];
            if( $x > $g['max'] )
            {
                $g['max'] = $x;
            }
            $g['pages'] = array_unique( array_merge( $g['pages'], $pages ) );
            $goff[ count( $goff ) - 1 ] = $g;
        }
    }


    /*
     *
     *  Dump offsets with tolerance and pages
     *
     */

    foreach( $goff as $g )
    {
        sort( $g['pages'] );
        $i = 1;
        $np = count( $g['pages'] );
        $np = $np <= 1 ? '' : " ($np pages)";
        foreach( $g['pages'] as $p )
        {
            $p++;
            if( $i === 1 )
            {
                $t = $g['max'] - $g['min'];
                if( $t === 0 )
                {
                    echo "\nX = {$g['min']}$np: $p";
                }
                else
                {
                    echo "\nX = [ {$g['min']} ... {$g['max']} ]$np: $p";
                }
            }
            else
            {
                echo ", $p";
            }
            if( $i === 30 )
            {
                echo "...";
                break;
            }
            $i++;
        }
    }
    echo "\n";

    if( ArgumentGet( 'code', ARGUMENT_BOOLEAN ) )
    {
        if( count( $heights ) === 1 )
        {
            $heights = $heights[0];
        }
        else
        {
            $heights = "[" . implode( ", ", $heights ) . "]";
        }

        foreach( $goff as $g )
        {
            echo '$'."offsets[] = PdfMakeOffset( $heights, {$g['min']}, {$g['max']}, 0, 0, 1 ); //";
            $pages = $g['pages'];
            $i = 1;
            foreach( $pages as $p )
            {
                $p++;
                echo " $p";
                $i++;
                if( $i === 10 )
                {
                    echo "...";
                    break;
                }
            }
            echo "\n";
        }
    }

}



/*
 *
 *  PdfHeight
 *
 */

function PdfHeight( $products, $pdfidxPath )
{
    /*
     *
     *  Check for tolerance argument
     *
     */

    $tolerance = ArgumentGet( 'tol', ARGUMENT_OPTIONAL );

    if( $tolerance === false )
    {
        $tolerance = "0";
    }

    if( ! ctype_digit( $tolerance ) )
    {
        Error( 'tolerance value specified in `tol` must be a number' );
        /*--- EXIT POINT ---*/
    }

    $tolerance = (int)$tolerance;


    /*
     *
     *  Show report type
     *
     */

    EchoNL( "Dumping heights for codes' text, tolerance = $tolerance" );


    /*
     *
     *  Check PdfImproveLinksFilter is defined
     *
     */

    if( function_exists( 'PdfImproveLinksFilter' ) )
    {
        $filter = true;
    }
    else
    {
        $filter = false;
    }


    /*
     *
     *  Search codes
     *
     */

    $heigths = [];
    $n = count( $products );
    $i = 1;
    foreach( $products as $p )
    {
        EchoCR( "$i:$n" );
        $i++;

        $code = $p['code'];

        $code = $filter ? PdfImproveLinksFilter( $code ) : $code;

        if( $code === false )
        {
            continue;
        }

        $output = FSExecute( [ "pdfidxfind -limit 2500 -pdfidx", $pdfidxPath, "-search", $code ], $status );
        if( $status != 0 )
        {
            Error( "pdfidxfind exited with status $status: searching $code: $output" );
            /*--- QUIT POINT ---*/
        }

        $results = json_decode( $output, true );

        foreach( $results as $r )
        {
            $h = $r['h'];

            if( ! isset( $heights[ $h ] ) )
            {
                $heights[ $h ] = [];
            }

            if( ! in_array( $r['p'], $heights[ $h ] ) )
            {
                $heights[ $h ][] = $r['p'];
            }
        }
    }


    /*
     *
     *  Dump heights and pages
     *
     */

    ksort( $heights );

    if( $tolerance === 0 )
    {
        foreach( $heights as $h => $pages )
        {
            sort( $pages );
            $i = 1;
            $np = count( $pages );
            $np = $np <= 1 ? '' : " ($np pages)";
            foreach( $pages as $p )
            {
                $p++;
                if( $i === 1 )
                {
                    echo "\nH = $h$np: $p";
                }
                else
                {
                    echo ", $p";
                }
                if( $i === 30 )
                {
                    echo "...";
                    break;
                }
                $i++;
            }
        }
        echo "\n";
        return;
        /*--- EXIT POINT ---*/
    }



    /*
     *
     *  Group heights inside tolerance
     *
     */

    $gheights = [];
    $min = -99999;
    foreach( $heights as $h => $pages )
    {
        if( $h - $min > $tolerance )
        {
            $gheights[] = [ 'min' => $h, 'max' => $h, 'pages' => $pages ];
            $min = $h;
        }
        else
        {
            $g = $gheights[ count( $gheights ) - 1 ];
            if( $h > $g['max'] )
            {
                $g['max'] = $h;
            }
            $g['pages'] = array_unique( array_merge( $g['pages'], $pages ) );
            $gheights[ count( $gheights ) - 1 ] = $g;
        }
    }


    /*
     *
     *  Dump offsets with tolerance and pages
     *
     */

    foreach( $gheights as $g )
    {
        sort( $g['pages'] );
        $i = 1;
        foreach( $g['pages'] as $p )
        {
            $p++;
            if( $i === 1 )
            {
                $t = $g['max'] - $g['min'];
                if( $t === 0 )
                {
                    echo "\nX = {$g['min']}: $p";
                }
                else
                {
                    echo "\nX = [ {$g['min']} ... {$g['max']} ]: $p";
                }
            }
            else
            {
                echo ", $p";
            }
            if( $i === 30 )
            {
                echo "...";
                break;
            }
            $i++;
        }
    }
    echo "\n";
}


