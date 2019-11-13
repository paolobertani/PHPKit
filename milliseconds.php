<?php
//
// Milliseconds
//
// return current time in milliseconds or time elapsed since time in milliseconds passed
//

function Milliseconds( $since = 0 )
{
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000) - ((int)$since));
}
