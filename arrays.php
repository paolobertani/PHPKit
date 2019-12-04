<?php

//
//
// ARRAYS
//
//



require_once ROOT . '/include/error.php';



//
// CONSTANTS
//

define( 'ARRAY_ASC',    1 );
define( 'ARRAY_DESC',   2 );



//
// ArrayFromFile
//
// Read  array  of  associative  arrays  from  tab
// separated text file:  first  row  must  contain
// column names that  will  become  array's  keys;
// every row must contain all the columns; only  a
// trailing empty row is allowed  (extra  "\n"  at
// the end of the file)
//                                              \x

function ArrayFromFile( $path )
{
    $text = @file_get_contents( $path );
    if( $text === false )
    {
        Error( "cannot read file: $path" );
    }

    $lines = explode( "\n", $text );
    $n = count( $lines );
    if( $n < 2 )
    {
        Error( "file is empty: $path");
    }

    $out = [];
    $keys = explode( "\t", $lines[ 0 ] );
    $cols = count( $keys );

    for( $i = 1; $i < $n; $i++ )
    {
        if( $lines[ $i ] === '' && $i === $n - 1 )
        {
            break;
        }

        $parts = explode( "\t", $lines[ $i ] );

        if( count( $parts ) !== $cols )
        {
            Error( "mismatch number of columns at row $i" );
        }

        $row = [];

        for( $j = 0; $j < $cols; $j++ )
        {
            $row[ $keys[ $j ] ] = $parts[ $j ];
        }

        $out[] = $row;
    }

    return $out;
}



//
// ArrayToFile
//
// Write an array of associative arrays to  a  tab
// separated text file; the first row will contain
// the inner arrays' keys; every associative array
// into the main array must contain the same keys
//                                              \x

function ArrayToFile( $path, $array )
{
    $out = "";
    $i = 0;
    $row = $array[ 0 ];

    $first = true;
    foreach( $row as $key => $value )
    {
        if( ! $first )
        {
            $out .= "\t";
        }
        else
        {
            $first = false;
        }

        $out .= $key;
    }

    foreach( $array as $row )
    {
        $out .= "\n";
        $first = true;
        foreach( $row as $key => $value )
        {
            if( ! $first )
            {
                $out .= "\t";
            }
            else
            {
                $first = false;
            }

            $out .= $value;
        }
    }

    file_put_contents( $path, $out );
}



//
// ArraySortByKey
//
// Sort an array  of  associative  arrays  by  the
// values of the specified key(s):  a  single  key
// may be specified as string, multiple keys  must
// be specified with an array  of  strings;  every
// associative array must  contain  all  the  keys
// used to sort the main array; sorting order  can
// be  specified  by  appending  `ASC`   (default,
// optional) or `DESC` to  one  ore  more  sorting
// keys;
//                                              \x

function ArraySortByKey( &$array, $keys )
{
    if( is_array( $keys ) )
    {
        $order = [];
        $n = count( $keys );
        for( $i = 0; $i < $n; $i++ )
        {
            $key = $keys[ $i ];
            if( substr( $key, -3, 3 ) === "ASC" )
            {
                $order[] = 1;
                $key = substr( $key, 0, -3 );
            }
            elseif( substr( $key, -4, 4 ) === "DESC" )
            {
                $order[] = -1;
                $key = substr( $key, 0, -4 );
            }
            else
            {
                $order[] = 1;
            }
            $keys[ $i ] = $key;
        }

        usort( $array, function( $a, $b ) use ( $keys, $order, $n )
        {
            for( $i = 0; $i < $n; $i++ )
            {
                $k = $keys[ $i ];
                $o = $order[ $i ];
                if( $a[$k] == $b[$k] )
                {
                    continue;
                }
                return ( ( $a[$k] < $b[$k] ) ? -$o : $o );
            }
            return -1;
        } );
    }
    else
    {
        $key = $keys;
        if( substr( $key, -3, 3 ) === "ASC" )
        {
            $order = 1;
            $key = substr( $key, 0, -3 );
        }
        elseif( substr( $key, -4, 4 ) === "DESC" )
        {
            $order = -1;
            $key = substr( $key, 0, -4 );
        }
        else
        {
            $order = 1;
        }

        usort( $array, function( $a, $b ) use ($key, $order) { return ( ( $a[$key] < $b[$key] ) ? -$order : $order ); } );
    }

}



//
// ArraySortByArray
//
// Sort two arrays based  on  the  values  of  the
// second; option parameter may be used to specify
// sort order: `ARRAY_ASC` (default, optional)  or
// `ARRAY_DESC`
//                                              \x

function ArraySortByArray( &$a1, &$a2, $options = ARRAY_ASC )
{
    array_multisort( $a2, $a1 );

    if( $options === ARRAY_DESC )
    {
        $t1 = array_reverse( $a1 );
        $t2 = array_reverse( $a2 );
        $a1 = $t1;
        $a2 = $t2;
    }
}



//
//
//


