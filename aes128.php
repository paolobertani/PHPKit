<?php

function aes128Encrypt( $text, $key )
{
    $text = ( string )$text;                                        // cast text to string
    if( 16 !== strlen( $key ) ) $key = hash( 'MD5', $key, true );   // if the key is not 16 characters MD5 it to get 16 bytes
    $padding = 16 - ( strlen( $text ) % 16 );                       // padding characters to make the text multiple of 16 characters
    $text .= str_repeat( chr( $padding ), $padding );               // text is padded with the padding length as a byte value
    $enc = mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $text,        // text is encrypted
        MCRYPT_MODE_CBC, str_repeat( "\0", 16 ) );                  // with cbc mode
    $out = '';                                                      // encrypted data is turned into hex string
    for( $i=0;$i<strlen( $enc );$i++ )
    {
        $out .= str_pad( dechex( ord( $enc{$i} ) ), 2, '0', STR_PAD_LEFT );
    }
    return $out;
}



function aes128Decrypt( $hexdata, $key )
{
    $data = '';
    if( strlen( $hexdata )%2!=0 ) $hexdata .= '0';                  // add a leading 0 if hexdata length is not mod 2
    for( $i=0; $i<strlen( $hexdata ); $i+=2 )                       // turn hexdata into bytes
    {
        $data .= chr( hexdec( substr( $hexdata, $i, 2 ) ) );
    }
    if( 16 !== strlen( $key ) ) $key = hash( 'MD5', $key, true );   // if the key is not 16 characters MD5 it to get 16 bytes
    $data = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, $data,       // data is decripted
        MCRYPT_MODE_CBC, str_repeat( "\0", 16 ) );                  // using cbc
    $padding = ord( $data[strlen( $data ) - 1] );                   // the decripted string is padded, the last byte is padding length
    return substr( $data, 0, -$padding );                           // padding is removed
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


