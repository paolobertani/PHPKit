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
// separated text file: first row  should  contain
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

        $partn = count( $parts );

        $row = [];

        for( $j = 0; $j < $cols; $j++ )
        {
            if( $j < $partn )
            {
                $row[ $keys[ $j ] ] = $parts[ $j ];
            }
            else
            {
                $row[ $keys[ $j ] ] = "";
            }
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
    if( count( $array ) === 0 )
    {
        file_put_contents( $path, "" );
        return;
    }

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
// ArrayHasDuplicates
//
// given an array of  associative  arrays  returns
// true if two (or more) items have the same value
// for  the  specified  key;  the  array  will  be
// ordered by the specified key;
// optionally a `$flagKey`  may  be  specified  in
// which case the corresponding value in duplicate
// records will be flagged with `$flag`
//                                              \x

function ArrayHasDuplicates( &$array, $key, $flagKey = false, $flag = "@" )
{
    ArraySortByKey( $array, $key );
    $last = null;
    $n = count( $array );
    $duplicates = false;
    for( $i = 0; $i < $n; $i++ )
    {
        if( $array[$i][$key] === $last )
        {
            $duplicates = true;
            if( $flagKey !== false )
            {
                $array[$i][$flagKey] .= $flag;
            }
            else
            {
                return true;
            }
            /*--- EXIT POINT ---*/
        }
        $last = $array[$i][$key];
    }
    return $duplicates;
}



//
// ArrayRemoveDuplicates
//
// the  function  receives  the  `$array`  to   be
// processed and  the  `$key`  for  the  duplicate
// values; `$chooser`  is  a  function  (callable)
// whose purpose is described later;
//
// the function has two operative  modes:  chooser
// and manager
//
// chooser:
// items with the same value for the specified key
// are aggregated into an array and passed to  the
// `$chooser` function; the "chooser" must  add  a
// value for the key  specificed  as  `$score_key`
// (default: 'score'); for every  group  of  items
// with the same key only  the  one  with  highest
// score will be kept
//
// manager:
// to enable `manager` mode `$score_key` is set to
// `false`; items with  the  same  value  for  the
// specified key are aggregated into an array  and
// passed to the  "duplicate  manager"  `$chooser`
// function; the duplicate manager may  alter  any
// values of the received array that will  replace
// the values in the original array (normally  the
// manager will alter the key with  duplicates  to
// make them unique but this is not mandatory)  \x
//

function ArrayRemoveDuplicates( &$array, $key, $chooser, $score_key = 'score' )
{
    // manage score key

    if( $score_key === null || $score_key === '' )
    {
        $score_key = false;
    }

    // item count

    $n = count( $array );
    if( $n < 2 )
    {
        return $array;
        /*--- EXIT POINT ---*/
    }

    // sort array

    ArraySortByKey( $array, $key );

    // init output

    $out = [];

    // add NULL item at the end of the array to let the last block flush

    $array[][$key] = NULL;
    $n++;

    // init the first block with the first item

    if( $score_key !== false ) { $array[0][$score_key] = 0; }
    $block = [ $array[0] ];
    $last  = $array[0][$key];

    // start from the second item

    for( $i = 1; $i < $n; $i++ )
    {
        $item = $array[$i];
        if( $score_key !== false ) { $item[$score_key] = 0; }

        if( $item[$key] === $last )
        {
            // duplicate: add the item to the block

            $block[] = $item;
        }
        else // not a duplicate
        {
            // if the block have more than one item run the chooser and sort by score desc.

            if( count( $block ) > 1 )
            {
                $chooser( $block );
                if( $score_key !== false ) { ArraySortByKey( $block, $score_key."DESC" ); }
            }

            // chooser: add to output the first item of the block

            if( $score_key !== false )
            {
                unset( $block[0][$score_key] );
                $out[] = $block[0];
            }

            // manager: add to output the block

            if( $score_key === false )
            {
                foreach( $block as $b )
                {
                    $out[] = $b;
                }
            }

            // initialize a new block with the new item

            $last = $item[$key];
            $block = [ $item ];
        }
    }

    // set the array passed by reference to output array

    $array = $out;
}




