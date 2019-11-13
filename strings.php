<?php

function StringHas( $str, $has )
{
    return ( strpos( $str, $has ) !== false );
}



function StringBetween( $str, $sm, $em, $include = false )
{
    $r = StringsBetween( $str, $sm, $em, $include, true );
    if( count( $r ) == 0 )
    {
        return false;
    }
    else
    {
        return $r[ 0 ];
    }
}



function StringsBetween( $str, $sm, $em, $include = false, $onlyFirst = false )
{
    $results = array();

    $i = 0;

    $sml = strlen( $sm );
    $eml = strlen( $em );

    while( true )
    {
        $s = strpos( $str, $sm, $i );
        if( $s === false )
        {
            break;
        }

        $i = $s + $sml;

        $e = strpos( $str, $em, $i );
        if( $e === false )
        {
            break;
        }

        if( $include )
        {
            $results[] = substr( $str, $s, $e - $s + $eml );
        }
        else
        {
            $results[] = substr( $str, $s + $sml, $e - $s - $sml );
        }

        if( $onlyFirst )
        {
            break;
        }

        $i = $e + $eml;
    }

    return $results;
}



function StringTruncateAround( $s, $l )
{
    if( mb_strlen( $s ) <= $l )
    {
        return $s;
    }

    $p = mb_strpos( $s, ' ', $l );
    if( $p === false )
    {
        $p = $l + 1;
    }

    $s = mb_substr( $s, 0, $p ) . '...';

    return $s;
}



function StringBegins( $s, $with )
{
    $l = strlen( $with );

    if( strlen( $s ) >= $l && substr( $s, 0, $l ) == $with )
    {
        return true;
    }

    return false;
}


//
// StringFromFloat
//
// convert float to string ignoring locale
// optionally using specified precision
//

function StringFromFloat( $f, $p = null )
{
    if( $p !== null ) return number_format($f, $p, '.', '');
    $s = trim( number_format($f, 10, '.', ''), '0' );
    if( substr( $s, -1 ) == '.' ) $s .= '0';
    return $s;
}



//
// StringReplaceAtBeginning
//
// Replace `src` with `rep` at the beginning of `str`
// Returns FALSE if `str` does not begin with `src`
//


function StringReplaceAtBeginning( $str, $src, $rep )
{
    if( ! StringBegins( $str, $src ) )
    {
        return false;
        /*--- EXIT POINT ---*/
    }

    $str = $rep . substr( $str, strlen( $src ) );

    return $str;
}
