<?php

function aes128Encrypt( $text, $key, $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0" )
{
    return bin2hex( openssl_encrypt( $text, 'aes-128-cbc', strlen( $key ) === 16 ? $key : hash( 'md5', $key, true ), OPENSSL_RAW_DATA, strlen( $iv ) === 16 ? $iv : hash( 'md5', $iv, true ) ) );
}



function aes128Decrypt( $hexdata, $key, $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0" )
{
    return openssl_decrypt( hex2bin( $hexdata ), 'aes-128-cbc', strlen( $key ) === 16 ? $key : hash( 'md5', $key, true ), OPENSSL_RAW_DATA, strlen( $iv ) === 16 ? $iv : hash( 'md5', $iv, true ) );
}



function aes128EncryptParams( $params, $key, $psize = null )
{
    if( $psize === null )
        $psize = array( 3, 2, 2, 2, 2, 2, 2 );      // the size in bytes reserved for each param
    $params = aes128xexpl( '/', $params );          // turn params string into array
    $n = count( $params );                          // how many parameters passed
    $m = count( $psize );                           // how many parameters allowed
    while( $n < $m )
    {
        $params[] = pow( 256, $psize[ $n ] ) - 1;   // unspecified params are set to 'all bits set'
        $n++;
    }
    $binary = '';
    for( $i = 0; $i < $n ; $i++ )
    {
        $binary .= aes128xitob( $params[ $i ], $psize[ $i ] );    // convert integers to binary
    }
    return aes128Encrypt( $binary, $key );           // encrypt
}



// return false if source data is encrypted incorrectly, array otherwise

function aes128DecryptParams( $hexdata, $key, $map )
{
    $map = aes128xexpl( '/', $map );
    $psize = array( );
    $n = count( $map );
    for( $i = 0; $i < $n; $i++ )
    {
        $ks = aes128xexpl( ':', $map[ $i ] );
        $psize[] = $ks[1];
        $map[$i] = $ks[0];
    }

    $binary = aes128Decrypt( $hexdata, $key );   // decript the hex data

    $len = strlen( $binary );
    if( $len == 0 ) return false;

    $params = array( );
    $j = 0;
    $eof = false;
    for( $i = 0; $i < $n; $i++ )
    {
        if( $eof )
        {
            $params[ $map[ $i ] ] = null;
        }
        else
        {
            $ps = $psize[ $i ];
            if( $j + $ps > $len )
            {
                return false; // bad encryption
            }
            $int = aes128xbtoi( substr( $binary, $j, $ps ), $ps );  // convert to int each piece
            $j += $ps;                                              // byte counter
            if( $int == pow( 256, $ps ) - 1 )                       // if its all-bits-set exit, last parameter reached
            {
                $eof = true;
                $params[ $map[ $i ] ] = null;
            }
            else
            {
                $params[ $map[ $i ] ] = $int;
            }
        }
    }
    return $params;
}



function aes128xexpl( $d, $str )
{
    $str = trim( $str, $d );
    if( $str == '' ) return array();
    $dd = $d . $d;
    while( strpos( $str, $dd  ) !== false )
        $str = str_replace( $dd, $d, $str );
    return explode( $d, $str );
}



function aes128xitob( $int, $n )
{
    $bin = '';
    for( $i = 0; $i < $n ; $i++ )
    {
        $bin .= chr( $int % 256 );
        $int = intval( $int / 256 );
    }
    return $bin;
}



function aes128xbtoi( $bin, $n )
{
    $int = 0;
    for( $i = 0; $i < $n ; $i++ )
    {
        $int += ord( $bin[ $i ] ) * pow( 256, $i );
    }
    return $int;
}


