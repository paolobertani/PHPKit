<?php

require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/milliseconds.php';

$g_Test_Passed = 0;
$g_Test_Failed = 0;
$g_Test_Milliseconds = 0;

function Test( $description, $result )
{
    global $g_Test_Passed;
    global $g_Test_Failed;

    if( ! is_bool( $result ) )
    {
        Error( "Non boolean result passed" );
    }

    if( $result )
    {
        EchoCR( "PASSED - $description" );
    }
    else
    {
        EchoNL( "FAILED - $description" );
    }

    if( $result )
    {
        $g_Test_Passed++;
    }
    else
    {
        $g_Test_Failed++;
    }
}

function TestBegin()
{
    global $g_Test_Passed;
    global $g_Test_Failed;
    global $g_Test_Milliseconds;

    $g_Test_Passed = 0;
    $g_Test_Failed = 0;
    $g_Test_Milliseconds = Milliseconds();
}

function TestSummary()
{
    global $g_Test_Passed;
    global $g_Test_Failed;
    global $g_Test_Milliseconds;

    $g_Test_Milliseconds = Milliseconds( $g_Test_Milliseconds );

    EchoNL( "Test summary:\nPASSED: $g_Test_Passed\nFAILED: $g_Test_Failed\nTotal:  " . ( $g_Test_Passed + $g_Test_Failed ) . "\nElapsed time: $g_Test_Milliseconds ms" );
}
