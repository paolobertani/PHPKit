<?php

//
// Sort an array of associative arrays
//

function ArraySortByKeyAsc( &$array, $key )
{
    usort( $array, function( $a, $b ) use ($key) { return ( ( $a[$key] < $b[$key] ) ? -1 : 1 ); } );
}

function ArraySortByKeyDesc( &$array, $key )
{
    usort( $array, function( $a, $b ) use ($key) { return ( ( $a[$key] > $b[$key] ) ? -1 : 1 ); } );
}

//
// Sort two arrays based on the values of the second
//

function ArraySortByArrayAsc( &$a1, &$a2 )
{
    array_multisort( $a2, $a1 );
}

function ArraySortByArrayDesc( &$a1, &$a2 )
{
    array_multisort( $a2, $a1 );
    $t1 = array_reverse( $a1 );
    $t2 = array_reverse( $a2 );
    $a1 = $t1;
    $a2 = $t2;
}

