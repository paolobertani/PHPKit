<?php

define( 'ARGUMENT_OPTIONAL', 1 );
define( 'ARGUMENT_BOOLEAN', 2 );

function ArgumentGet( $name, $options = 0 )
{
    global $argv;

    if( substr( $name, 0, 1 ) != '-' )
    {
        $name = "-$name";
    }

    if( $options == ARGUMENT_BOOLEAN )
    {
        return in_array( $name, $argv );
    }

    $n = count( $argv );

    for( $i = 1; $i < $n - 1; $i++ )
    {
        if( $argv[ $i ] == $name )
        {
            if( substr( $argv[ $i + 1 ], 0, 1 ) != '-' )
            {
                return $argv[ $i + 1 ];
            }
        }
    }

    if( $options == ARGUMENT_OPTIONAL )
    {
        return false;
    }

    echo "Expected parameter $name\n";
    exit(0);
}