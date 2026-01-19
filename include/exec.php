<?php
//
//
// Exec
//
//


//
// INCLUDE
//

require_once ROOT . '/include/strings.php';
require_once ROOT . '/include/version.php';



//
// Check version
//

function ExecShouldRestart( &$version )
{
    $source = file_get_contents( __DIR__ . '/version.php' );
    $version = StringBetween( $source, "define( 'INCLUDE_VERSION', '", "'" );
    if( $version !== INCLUDE_VERSION )
    {
        return true;
    }
    return false;
}



//
// Restart (with optional additional arguments)
//

function ExecRestart( $abs_path, $more_args = null )
{
    global $argv;

    if( $more_args === null )
    {
        $more_args = [];
    }

    $argv[0] = $abs_path;

    foreach( $more_args as $a )
    {
        $argv[] = $a;
    }

    pcntl_exec( $_SERVER['_'], $argv );

    exit(0);
}