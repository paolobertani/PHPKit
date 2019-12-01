<?php

require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/milliseconds.php';

$g_Test_Passed = 0;
$g_Test_Failed = 0;
$g_Test_Count  = 0;
$g_Test_Milliseconds = 0;

function Test( $description, $operation, $result = null )
{
    global $g_Test_Passed;
    global $g_Test_Failed;
    global $g_Test_Count;

    if( $result === null )
    {
        $result = $operation;
    }

    if( ! is_bool( $result ) )
    {
        Error( "Non boolean result passed" );
    }

    $g_Test_Count++;

    if( $result )
    {
        if( $g_Test_Count % 100 === 0 )
        {
            EchoCR( "Test count: $g_Test_Count" );
        }
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
    global $g_Test_Count;

    $g_Test_Passed = 0;
    $g_Test_Failed = 0;
    $g_Test_Count = 0;
    $g_Test_Milliseconds = Milliseconds();
}

function TestSummary()
{
    global $g_Test_Passed;
    global $g_Test_Failed;
    global $g_Test_Milliseconds;
    global $g_Test_Count;

    $g_Test_Milliseconds = Milliseconds( $g_Test_Milliseconds );

    EchoNL( "Test summary:\nPASSED: $g_Test_Passed\nFAILED: $g_Test_Failed\nTotal: $g_Test_Count\nElapsed time: $g_Test_Milliseconds ms" );
}
