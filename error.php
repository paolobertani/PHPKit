<?php

function Error( $msg )
{
    $trace = debug_backtrace();

    if( strpos( $msg, "@types" ) !== false )
    {
        $types = [];
        foreach( $trace[1]['args'] as $arg )
        {
            $types[] = gettype( $arg );
        }
        $types = implode( ", ", $types );
        $msg = str_replace( "@types", $types, $msg );
    }

    echo "$msg\n";

    $include = is_link( ROOT."/include" ) ? readlink( ROOT."/include" ) : false;

    $n = count( $trace );
    for( $i = 1; $i < $n; $i++ )
    {
        $file = $trace[$i]['file'];
        if( strpos( $file, ROOT."/" ) === 0 )
        {
            $file = substr( $file, strlen( ROOT ) + 1 );
        }
        elseif( $include && strpos( $file, $include ) === 0 )
        {
            $file = "include" . substr( $file, strlen( $include ) );
        }
        echo "$file : {$trace[$i]['function']} : {$trace[$i]['line']}\n";
    }

    exit(0);
}