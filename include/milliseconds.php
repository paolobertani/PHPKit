<?php
/*
 *
 *  Milliseconds
 *
 *  return current time in milliseconds or time elapsed since time in milliseconds passed
 *  or unix epoch time if parameters are passed
 *
 */

function Milliseconds( $since = 0 )
{
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000) - ((int)$since));
}



/*
 *
 *  Microseconds
 *
 *  return current time in microseconds or time elapsed since time in microseconds passed
 *  or unix epoch time if parameters are passed
 *
 */

function Microseconds( $since = 0 )
{
    $mt = explode( ' ', microtime() );
    return intval( $mt[1] * 1E6 ) + intval( round( $mt[0] * 1E6 ) ) - intval( $since );
}



function msleep( $ms )
{
    if( $ms > 0 ) { usleep( $ms * 1000 ); }
}