<?php


/*
 *
 *  INCLUDE
 *
 */

require_once ROOT . '/include/milliseconds.php';



/*
 *
 *  GLOBALS and CONSTANTS
 *
 */

$g_EchoTerminalWidth = 80;
$g_EchoTerminalWidthLast = 0;

define( 'CLEARLINE', chr(27)."[2K\r" );



/*
 *
 *  EchoCR
 *
 *  Clear the terminal line, write a string then remain on the line
 *  The text is truncated if longer than a terminal window line
 *
 */

function EchoCR( $str )
{
    global $g_EchoTerminalWidth;
    global $g_EchoTerminalWidthLast;


    /*
     *  Get terminal window width (in characters) every second
     */

    $ms = Milliseconds();
    if( $ms - $g_EchoTerminalWidthLast > 1000 )
    {
        $g_EchoTerminalWidthLast = $ms;
        $g_EchoTerminalWidth = (int) trim( exec( '/usr/bin/tput cols' ) );
    }


    /*
     *  Truncate string if too long
     */

    if( strlen( $str ) >= $g_EchoTerminalWidth )
    {
        $str = substr( $str, 0, $g_EchoTerminalWidth - 40 ) . " [...] " . substr( $str, -32 );
    }


    /*
     *  Carriage return then output string (then remain on same line)
     */

    echo CLEARLINE . $str;
}



/*
 *
 *  EchoNL
 *
 *  Clear the terminal line, write a string then newline
 *
 */

function EchoNL( $str, $makecr = false )
{
    if( $makecr !== false )
    {
        EchoCR( $str );
        if( is_int( $makecr ) )
        {
            sleep( $makecr );
        }
        return;
        /*--- EXIT POINT ---*/
    }
    echo CLEARLINE . $str . "\n";
}
