<?php
//
//
// Signals
//
//


//
// INCLUDE
//

require_once ROOT . '/include/error.php';




//
// Global
//

$g_signal_shutdown = false;
$g_signal_installed = false;



//
// Listen for quit (from ActivityMonitor)
// kill -s 15 <pid> from terminal
// or CTRL-C from terminal
//

function SignalInstall()
{
    global $g_signal_installed;

    if( $g_signal_installed )
    {
        Error( 'signal already installed' );
        /*--- QUIT POINT ---*/
    }

    $g_signal_installed = true;

    declare(ticks = 1);

    global $g_signal_shutdown;
    $g_signal_shutdown = false;

    $success = true;
    $success = $success & pcntl_signal( SIGTERM, 'SignalHandlerPrivate' ); // Quit or kill -s 15 <pid>
    $success = $success & pcntl_signal( SIGINT,  'SignalHandlerPrivate' ); // Ctrl-C

    if( ! $success )
    {
        Error( 'could not install signal handler' );
        /*--- QUIT POINT ---*/
    }
}



//
// Are signals installed
//

function SignalIsInstalled()
{
    global $g_signal_installed;
    return $g_signal_installed;
}



//
// A "quit" signal has been received
//

function SignalQuitReceived()
{
    global $g_signal_shutdown;
    return $g_signal_shutdown;
}



//
// PRIVATE
//

function SignalHandlerPrivate( $signo, $siginfo )
{
    global $g_signal_shutdown;
    $g_signal_shutdown = true;
}

