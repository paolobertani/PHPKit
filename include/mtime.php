<?php declare(strict_types=1);



/*
 *
 *  mdate
 *
 *  Formats a microsecond timestamp as YYYY-MM-DD HH:MM:SS.mmm
 *  If $microseconds is null, the current time is used.
 *
 */

function mdate( ?int $microseconds = null ): string
{
    if( $microseconds === null )
    {
        $microseconds = mtime();
    }

    $seconds = intdiv( $microseconds, 1000 );
    $decimalPart = str_pad( (string) ( $microseconds % 1000), 3, '0', STR_PAD_LEFT );

    return date( 'Y-m-d H:i:s', $seconds ) . '.' . $decimalPart;
}



/*
 *
 *  mtime
 *
 *  Returns the current Unix timestamp in microseconds.
 *  If a past timestamp is provided, returns the difference in microseconds.
 *
 */

function mtime( ?int $sinceMilliseconds = null  ): int
{
    $phpMicroTime = explode(' ', microtime());
    $microseconds = ( (int) $phpMicroTime[1] ) * 1000 + ( (int) round( $phpMicroTime[0] * 1000 ) );

    if( $sinceMilliseconds === null )
    {
        return $microseconds;
    }
    else
    {
        return $microseconds - $sinceMilliseconds;
    }
}



/*
 *
 *  mdate_to_mtime
 *
 *  Parses a timestamp in the format YYYY-MM-DD HH:MM:SS.mmm
 *  and returns the corresponding Unix timestamp in microseconds.
 *
 */

function mdate_to_mtime( string $mdate ): int
{
    $parts = explode('.', $mdate, 2);

    if( count($parts) !== 2 )
    {
        throw new \InvalidArgumentException('invalid mdate format');
    }

    [$datePart, $msecPart] = $parts;

    if( strlen($msecPart) !== 3 || !ctype_digit($msecPart) )
    {
        throw new \InvalidArgumentException('invalid microsecond part');
    }

    $seconds = strtotime($datePart);

    if( $seconds === false )
    {
        throw new \InvalidArgumentException('invalid date part');
    }

    return $seconds * 1000 + (int) $msecPart;
}



/*
 *
 *  utime
 *
 *  Returns the current monotonic time in MICROseconds.
 *  If a past timestamp is provided, returns the difference in usecs.
 *
 *  WARNING: the time is NOT relative to unixtime!
 *
 */

function utime(?int $sinceMicroseconds = null): int
{
    $microseconds = intdiv(hrtime(true), 1000);

    if ($sinceMicroseconds === null)
    {
        return $microseconds;
    }
    else
    {
        return $microseconds - $sinceMicroseconds;
    }
}








