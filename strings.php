<?php

//
//
//
// Strings
//
//
//



//
// Include
//

require_once ROOT . "/include/error.php";



//
// Constants/options
//

define( 'STRING_NO_OPTIONS',    0 );
define( 'STRING_FIRST',         2 );
define( 'STRING_MARKERS',       4 );
define( 'STRING_CI',            8 );
define( 'STRING_NOT',          16 );
define( 'STRING_WIDE',         32 );
define( 'STRING_SINGLEMARKER', 64 );
define( 'STRING_REPEAT',      128 );



//
// GENERAL RULES:
//
// unless  otherwise  specified,   all   functions
// accept as first parameter a string or an  array
// of strings; when an array of string  is  passed
// the function iterates over all the items of the
// array; in case the function returns an array of
// strings and may alter the items the  count  the
// array may be empty;
//
//                                              \x



//
// StringLowercase
//

function StringLowercase( $string )
{
    if( is_string( $string ) || is_array( $string ) )
    {
        //
    }
    else
    {
        Error( "wrong parameter type: passed @types" );
    }

    if( is_string( $string ) )
    {
        return mb_strtolower( $string );
    }

    if( is_array( $string ) )
    {
        $out = [];
        foreach( $string as $str )
        {
            if( ! is_string( $str ) )
            {
                Error( "`string` as array must be array of strings" );
            }
            $out[] = mb_strtolower( $str );
        }
        return $out;
    }
}



//
// StringUppercase
//

function StringUppercase( $string )
{
    if( is_string( $string ) || is_array( $string ) )
    {
        //
    }
    else
    {
        Error( "wrong parameter type: passed @types" );
    }

    if( is_string( $string ) )
    {
        return mb_strtoupper( $string );
    }

    if( is_array( $string ) )
    {
        $out = [];
        foreach( $string as $str )
        {
            if( ! is_string( $str ) )
            {
                Error( "`string` as array must be array of strings" );
            }
            $out[] = mb_strtoupper( $str );
        }
        return $out;
    }
}



//
// StringHas
//
// returns true if `$string` contains `$has`
//
// an array can be passed as second  parameter  in
// which case the function  returns  true  if  the
// string contains at least one of the strings  in
// the array;
// if `$has` is an empty string  returns  true  if
// `$string` is not empty
//
// an array of strings  can  be  passed  as  first
// parameter: in this case  the  function  returns
// the original array  removing  all  the  strings
// that do not meet  the  `$has`  requirement;
//
// the  option  STRING_NOT  reverses  the   logic:
// `true`  is  returned  if  `$string`  does   not
// contain `$has`
//
// allowed options:
// STRING_NOT
// STRING_CI
//                                              \x

function StringHas( $string, $has, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && ( is_string( $has ) || is_array( $has ) ) && is_int( $options ) )
    {
        // OK
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // case insensitive

    if( $options & STRING_CI )
    {
        $string = StringLowercase( $string );
        $has = StringLowercase( $has );
    }

    // has not

    $not = ( $options & STRING_NOT ) === STRING_NOT;

    //

    if( ! is_array( $string ) )
    {
        return StringHasPrivate( $string, $has, $not );
        /*--- EXIT POINT ---*/
    }

    $out = [];

    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` array must contain strings" );
            /*--- QUIT POINT ---*/
        }

        if( StringHasPrivate( $str, $has, $not ) )
        {
            $out[] = $str;
        }
    }

    return $out;
}

// operates on a single string, returns `true` or `false`

function StringHasPrivate( $str, $has, $not )
{
    $yes = ! $not;

    // empty `has`

    if( $has === '' )
    {
        return ( $str !== '' ) xor $not;
    }

    // array `has`

    if( is_array( $has ) )
    {
        foreach( $has as $h )
        {
            if( ! is_string( $h ) )
            {
                Error( "`has` as array must be array of strings" );
                /*--- QUIT POINT ---*/
            }

            if( strpos( $str, $h ) !== false )
            {
                return $yes;
                /*--- EXIT POINT --*/
            }
        }
        return $not;
        /*--- EXIT POINT --*/
    }

    // single `has`

    return ( strpos( $str, $has ) !== false ) xor $not;
}



//
// StringsBetween
//
// this  function  always  returns  an  array   of
// strings (note that the array may be empty);
//
// given the input string `$string`  an  array  is
// returned with the substrings surrounded by  the
// start marker and end marker `$sm` and `$em`;
//
// the start marker is searched  FIRST,  then  the
// end marker is searched AFTER the start  marker.
// A start marker  is  skipped  if  another  start
// marker is present before the end marker; when a
// "string  between"  is   found   the   iteration
// proceeds after the found end marker; if an  end
// marker is present before the next start  marker
// it is ignored;
//
// an array of strings  can  be  passed  as  first
// parameter in which case the  function  operates
// on every item and  returns  the  union  of  the
// results found for each item;
//
// `$sm`  as  empty  string  means  beginning   of
// `$string`. `$em` as empty string means  end  of
// `$string;
//
// option STRING_MARKERS: let the  start  and  end
// markers be included in the result(s);
//
// option STRING_FIRST: let the  function  returns
// only the first occurrence;
//
// option STRING_WIDE: does not  attempt  to  find
// the "closest" start mark and end  mark  as  per
// behaviour described above; once a start mark is
// found the function will seek for the first  end
// mark ignoring  start  markers  before  the  end
// mark;
//
// option  STRING_SINGLEMARKER:  this  option   is
// allowed only if `$sm` and `$em`  are  the  same
// and are not empty; end and start marker act  as
// a single marker: an end marker can be the start
// marker of the subsequent "string between";
//
// allowed options:
// STRING_SINGLEMARKER
// STRING_MARKERS
// STRING_FIRST
// STRING_WIDE
//                                              \x

function StringsBetween( $string, $sm, $em, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && ( is_string( $sm ) && is_string( $em ) && is_int( $options ) ) )
    {
        // OK
    }
    else
    {
        Error( "wrong parameters type: passed @types");
        /*--- QUIT POINT ---*/
    }

    // end marker begins with start marker

    if( $sm !== '' && $em !== '' && StringBegins( $em, $sm ) )
    {
        $options = $options | STRING_WIDE; // force wide option
    }

    // single string

    if( is_string( $string ) )
    {
        return StringsBetweenPrivate( $string, $sm, $em, $options );
        /*--- EXIT POINT ---*/
    }


    // array of strings

    $results = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` array must contain strings" );
            /*--- QUIT POINT ---*/
        }

        $res = StringsBetweenPrivate( $str, $sm, $em, $options );

        $results = array_merge( $results, $res );
    }


    // return matches array

    return $results;
}

// operates on single string

function StringsBetweenPrivate( $str, $sm, $em, $options )
{

    // parse options

    $markers = $options & STRING_MARKERS;
    $first   = $options & STRING_FIRST;
    $wide    = $options & STRING_WIDE;
    $single  = $options & STRING_SINGLEMARKER;


    // check single marker

    if( $single && ( $sm !== $em || $sm === '' ) )
    {
        Error( 'STRING_SINGLEMARKER allowed only when start and end markers are the same' );
    }


    // collect results

    $results = [];

    $idx = 0;
    $len = strlen( $str );

    $sml = strlen( $sm );
    $eml = strlen( $em );

    while( true )
    {
        if( $sml === 0)
        {
            $s = 0;
        }
        else
        {
            $s = strpos( $str, $sm, $idx );
            if( $s === false )
            {
                break;
            }
        }

        $idx = $s + $sml;

        if( $eml === 0 )
        {
            $e = $len;
        }
        else
        {
            $e = strpos( $str, $em, $idx );
            if( $e === false )
            {
                break;
            }
            while( $sm !== $em && $sml !== 0 && ! $wide )
            {
                $s2 = strpos( $str, $sm, $idx );
                if( $s2 === false || $s2 >= $e + $eml )
                {
                    break;
                }
                $s = $s2;
                $idx = $s + $sml;
            }
        }

        if( $markers )
        {
            $results[] = substr( $str, $s, $e - $s + $eml );
        }
        else
        {
            $results[] = substr( $str, $s + $sml, $e - $s - $sml );
        }

        if( $first )
        {
            break;
        }

        if( ! $single )
        {
            $idx = $e + $eml;
        }
        else
        {
            $idx = $e;
        }

        if( $idx >= $len - 1 )
        {
            break;
        }

        if( $sml === 0 || $eml === 0 )
        {
            break;
        }
    }


    // return matches array

    return $results;
}



//
// StringBetween
//
// operates like StringsBetween but only the first
// occurrence is returned as string; if  an  array
// of strings is passed as first parameter then an
// array of strings is returned;
//
// EXCEPTION:  this  is  the  only  function  that
// accepts `false` as input string;  the  function
// will return `false`; this allow nesting two ore
// more `StringBetween`
//
// options: see StringsBetween;
//
// NOTE: when a string is passed and there  is  no
// match then `false` is returned;
//
// allowed options:
// STRING_MARKERS
// STRING_WIDE
//                                              \x

function StringBetween( $string, $sm, $em, $options = STRING_NO_OPTIONS )
{
    // false string

    if( $string === false )
    {
        return false;
    }

    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && ( is_string( $sm ) && is_string( $em ) && is_int( $options ) ) )
    {
        // OK
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }


    // use StringsBetween

    $result = StringsBetween( $string, $sm, $em, $options | STRING_FIRST );

    if( is_array( $string ) )
    {
        return $result;
        /*--- EXIT POINT ---*/
    }

    if( count( $result ) === 0 )
    {
        return false;
        /*--- EXIT POINT ---*/
    }

    return $result[0];
}



//
// StringBegins
//
// Returns true if `$string` begins  with  `$with`;
// `$with` can  be  an  array  in  which  case  the
// function returns true if `$string`  begins  with
// at least one of the items of `$with`;
//
// allowed options:
// STRING_CI
//

function StringBegins( $string, $with, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( is_string( $string ) && ( is_array( $with ) || is_string( $with ) ) && is_int( $options ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }


    // array case: multiple $with

    if( is_array( $with ) )
    {
        foreach( $with as $w )
        {
            if( ! is_string( $w ) )
            {
                Error( "`with` as array must be array of strings" );
                /*--- QUIT POINT ---*/
            }

            if( StringBegins( $string, $w, $options ) )
            {
                return true;
                /*--- EXIT POINT ---*/
            }
        }
        return false;
        /*--- EXIT POINT ---*/
    }


    // string case: single $with

    $len = strlen( $with );

    if( strlen( $string ) < $len )
    {
        return false;
        /*--- EXIT POINT ---*/
    }


    // cut string

    $string = substr( $string, 0, $len );


    // case insensitive ?

    if( $options & STRING_CI )
    {
        $string = StringLowercase( $string );
        $with   = StringLowercase( $with );
    }


    // compare

    return $string === $with;
}



//
// StringEnds
//
// Returns true if  `$string`  ends  with  `$with`;
// `$with` can  be  an  array  in  which  case  the
// function returns true if `$string`  begins  with
// at least one of the items of `$with`;
//
// allowed options:
// STRING_CI
//

function StringEnds( $string, $with, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( is_string( $string ) && ( is_array( $with ) || is_string( $with ) ) && is_int( $options ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }


    // `false` on `false` input

    if( $string === false )
    {
        return false;
    }


    // array case: multiple $with

    if( is_array( $with ) )
    {
        foreach( $with as $w )
        {
            if( ! is_string( $w ) )
            {
                Error( "`with` as array must be array of strings" );
                /*--- QUIT POINT ---*/
            }

            if( StringEnds( $string, $w, $options ) )
            {
                return true;
                /*--- EXIT POINT ---*/
            }
        }
        return false;
        /*--- EXIT POINT ---*/
    }


    // string case: single $with

    $len = strlen( $with );

    if( strlen( $string ) < $len )
    {
        return false;
        /*--- EXIT POINT ---*/
    }


    // cut string

    $string = substr( $string, -$len, $len );


    // case insensitive ?

    if( $options & STRING_CI )
    {
        $string = StringLowercase( $string );
        $with   = StringLowercase( $with );
    }


    // compare

    return $string === $with;
}



//
// StringReplaceAtBeginning
//
// Replace `src` with `rep` at the beginning of `str`
// Returns `false` if `str` does not begin with `src`
// All parameters must be strings;
//
// allowed options:
// STRING_CI
//

function StringReplaceAtBeginning( $string, $src, $rep, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( is_string( $string ) && is_string( $src ) && is_string( $rep ) && is_int( $options ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }


    // purge options from unsupported flags

    $options = $options & STRING_CI;


    if( ! StringBegins( $string, $src, $options ) )
    {
        return false;
        /*--- EXIT POINT ---*/
    }

    $string = $rep . substr( $string, strlen( $src ) );

    return $string;
}



//
// StringCompare
//
// compare the two strings passed  as  parameters;
// both parameters must be string.
//
// allowed options:
// STRING_CI
//

function StringCompare( $a, $b, $options = STRING_NO_OPTIONS )
{
    if( is_string( $a ) && is_string( $b ) && is_int( $options ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    if( $options & STRING_CI )
    {
        $a = mb_strtolower( $a );
        $b = mb_strtolower( $b );
    }

    return $a === $b;
}



//
// StringReplace
//

function StringReplace( $string, $search, $replace, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && is_string( $search ) && is_string( $replace ) && is_int( $options ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    if( $search === $replace )
    {
        return $string;
        /*--- EXIT POINT ---*/
    }

    // single string

    if( is_string( $string ) )
    {
        while( strpos( $string, $search ) !== false )
        {
            $string = str_replace( $search, $replace, $string );
            if( ! ( $options & STRING_REPEAT ) )
            {
                break;
            }
        }

        return $string;
        /*--- EXIT POINT ---*/
    }

    // array of strings

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` as array must be array of strings");
            /*--- QUIT POINT ---*/
        }

        while( strpos( $str, $search ) !== false )
        {
            $str = str_replace( $search, $replace, $str );
            if( ! ( $options & STRING_REPEAT ) )
            {
                break;
            }
        }

        $out[] = $str;
    }
    return $out;
}



//
// StringRemove
//
// remove  every  occurrency   of   `$what`   from
// `$string`; `$string` can be an array of strings
// in wich case the operation is performed on each
// element; `$what` can be an array of strings  in
// wich case every string in the array is  removed
// from  the  source;  the  function  removes  the
// strings from the longest to the shortes      \x
//

function StringRemove( $string, $what )
{
    // parameter check

    if( ( is_string( $string ) || is_array( $string ) ) && ( is_string( $what ) || is_array( $what ) ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // single "what" case

    if( is_string( $what ) )
    {
        $string = StringReplace( $string, $what, "" );
        return $string;
        /*--- EXIT POINT ---*/
    }

    // get lengths

    $len = [];

    foreach( $what as $w )
    {
        $len[] = strlen( $w );
    }

    // order $what by lenght desc

    array_multisort( $len, $what );

    $what = array_reverse( $what );

    // remove occurrencies

    foreach( $what as $w )
    {
        $string = StringReplace( $string, $w, "" );
    }

    // return result

    return $string;
}



//
// StringTrim
//

function StringTrim( $string, $mask = " \t\n\r\0\x0B" )
{
    // false case

    if( $string === false )
    {
        return '';
        /*--- EXIT POINT ---*/
    }

    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && is_string( $mask ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // single string

    if( is_string( $string ) )
    {
        return trim( $string, $mask );
        /*--- EXIT POINT ---*/
    }

    // array of strings

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` as array must be array of strings");
            /*--- QUIT POINT ---*/
        }
        $out[] = trim( $str, $mask );
    }
    return $out;
}



//
// StringSubstring
//
// in the simplest form acts as mb_substr  on  the
// passed  string  with  `start`  and  `len`;   if
// `start` is `false`  then  an  empty  string  is
// returned; `string` can be an array of  strings,
// in this case an array of strings  is  returned;
// each string get substring applied;  if  `start`
// is `false` an empty array is returned;  `start`
// and `len` can be (both) arrays: the item  count
// of `string`,  `start`  and  `len`  must  match,
// every string get substring  applied  using  the
// start and len  value  from  `start`  and  `len`
// arrays at the same index.  Again  if  start  is
// `false` then  the  corresponding  item  in  the
// strings array is removed
//                                              \x

function StringSubstring( $string, $start, $len )
{

    // parameter type check

    /**/if( is_string( $string ) && ( is_int( $start ) || $start === false) && is_int( $len ) )
    {
        // ok
    }
    elseif( is_array ( $string ) && ( is_int( $start ) || $start === false) && is_int( $len ) )
    {
        // ok
    }
    elseif( is_array ( $string ) && is_array( $start ) && is_array( $len ) && count( $string ) === count( $start ) && count( $start ) === count( $len ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type or mix: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // single string

    if( is_string( $string ) )
    {
        if( $start === false )
        {
            return "";
            /*--- EXIT POINT ---*/
        }

        return mb_substr( $string, $start, $len );
        /*--- EXIT POINT ---*/
    }

    // array of strings with single `start`, `len`

    if( is_int( $start ) || $start === false )
    {
        $out = [];
        foreach( $string as $str )
        {
            if( ! is_string( $str ) )
            {
                Error( '`string` as array must be array of strings' );
                /*--- QUIT POINT ---*/
            }

            if( $start !== false )
            {
                $substr = mb_substr( $str, $start, $len );
                if( $substr !== '' )
                {
                    $out[] = $substr;
                }
            }
        }
        return $out;
        /*--- EXIT POINT ---*/
    }

    // array of strings with arrays of `start`, `len`

    $out = [];
    $n = count( $string );
    for( $i = 0; $i < $n; $i++ )
    {
        $str = $string[ $i ];
        $s = $start[ $i ];
        $l = $len[ $i ];

        if( ! is_string( $str ) ) { Error( '`string` as array must be array of strings' ); }                /*--- QUIT POINT ---*/
        if( ! is_int( $s ) && $s !== false ) { Error( 'items in array `start` must be int or false' ); }    /*--- QUIT POINT ---*/
        if( ! is_int( $l ) ) { Error( '`len` as array must be array of ints' ); }                           /*--- QUIT POINT ---*/

        if( $s !== false )
        {
            $substr = mb_substr( $str, $s, $l );
            if( $substr !== '' )
            {
                $out[] = $substr;
            }
        }
    }
    return $out;
}



//
// StringPosition
//
// `string` can ba a  string,  in  this  case  the
// function operates just as  mb_strpos;  `string`
// can be an array of strings in  which  case  the
// function operates as mb_strpos on  each  string
// returning an array of results of the same  size
// of `string`; the  returned  array  may  contain
// integer values or `false` (string not found)
//                                              \x

function StringPosition( $string, $search, $offset = 0 )
{
    if( ( is_string( $string ) || is_array( $string ) ) && is_string( $search ) && is_int( $offset ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // string

    if( is_string( $string ) )
    {
        return @mb_strpos( $string, $search, $offset );
        /*--- EXIT POINT ---*/
    }

    // array

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` as array must be array of strings" );
            /*--- QUIT POINT ---*/
        }

        $out[] = @mb_strpos( $str, $search, $offset );
    }
    return $out;
}



//
// StringHtmlToText
//
// Convert html to text stripping  html  tags  and
// converting  html  entities   to   corresponding
// characters; the `flags` parameter is passed  to
// `html_entity_decode`;
// ADDITIONALLY:  LN,  CR  are  removed;  mupliple
// spaces   are   turned    in    single    space;
// subsequently,  &nbsp;  is  turned  into  space,
// line-breaks tags are turned into newlines;
//                                              \x

function StringHtmlToText( $string, $flags = ENT_QUOTES | ENT_HTML5 )
{
    // parameter type check

    if( is_string( $string ) || is_array( $string ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // string

    if( is_string( $string ) )
    {
        $result = StringHtmlToText( [ $string ], $flags );
        return $result[0];
        /*--- EXIT POINT ---*/
    }

    // array

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` must be array of strings");
            /*--- QUIT POINT ---*/
        }

        $str = StringReplace( $str, "\n", " " );
        $str = StringReplace( $str, "\r", " " );
        $str = StringReplace( $str, "&nbsp;", " " );
        $str = StringReplace( $str, "<br>", "\n" );
        $str = StringReplace( $str, "<br/>", "\n" );
        $str = StringReplace( $str, "<br />", "\n" );
        while( StringPosition( $str, "  " ) !== false )
        {
            $str = StringReplace( $str, "  ", " " );
        }

        $out[] = html_entity_decode( strip_tags( $str ), $flags );
    }

    return $out;
}



//
// StringPercentEscape
//
// escapes  with  the  percent  %hh   form   every
// character except the ones passed to  `$except`;
// alphanumerical characters are never escaped
//                                              \x

function StringPercentEscape( $string, $except = "" )
{
    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && is_string( $except ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // default exceptions

    $except = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$except";

    // string

    if( is_string( $string ) )
    {
        $result = StringPercentEscape( [ $string ], $except );
        return $result[0];
        /*--- EXIT POINT ---*/
    }

    // array

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` must be array of strings");
            /*--- QUIT POINT ---*/
        }

        $o = "";
        $n = strlen( $str );
        for( $i = 0; $i < $n; $i++ )
        {
            $c = $str[ $i ];
            if( strpos( $except, $c ) === false )
            {
                $c = strtoupper( dechex( ord( $c ) ) );
                if( strlen( $c ) === 1 ) $c = "0$c";
                $c = "%$c";
            }
            $o .= $c;
        }
        $out[] = $o;
    }

    return $out;
}



//
// StringCompact
//
// convert newlines, tabs, etc.. into  space  then
// remove multiple spaces and trim the string
//                                              \x

function StringCompact( $string, $preserve = "" )
{
    // parameter type check

    if( ( is_string( $string ) || is_array( $string ) ) && is_string( $preserve ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // string

    if( is_string( $string ) )
    {
        $result = StringCompact( [ $string ], $preserve );
        return $result[0];
        /*--- EXIT POINT ---*/
    }

    // entities to turn into space

    $remove = [ "\n", "\r", "\t" ];


    // entities to preserve

    $preserve = str_split( $preserve );
    $remove = array_diff( $remove, $preserve );
    $preserve[] = ' ';

    // array

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "`string` must be array of strings");
            /*--- QUIT POINT ---*/
        }

        foreach( $remove as $r )
        {
            $str = StringReplace( $str, $r, " " );
        }

        foreach( $preserve as $p )
        {
            $pp = $p.$p;
            while( StringPosition( $str, $pp ) !== false )
            {
                $str = StringReplace( $str, $pp, $p );
            }
        }

        $str = trim( $str );

        $out[] = $str;
    }
    return $out;
}



//
// StringFromFloat
//
// convert float to string ignoring locale
// optionally using specified precision
//

function StringFromFloat( $f, $p = null )
{
    if( $p !== null ) return number_format($f, $p, '.', '');
    $s = trim( number_format($f, 10, '.', ''), '0' );
    if( substr( $s, -1 ) == '.' ) $s .= '0';
    return $s;
}


//
// StringTruncateMaybe
//
// Truncate a string if exceeds length
//

function StringTruncateMaybe( $str, $len, $end='...' )
{
    if( strlen( $str ) > $len )
    {
        $str = mb_substr( $str,  0, $len - strlen( $end ) ) . $end;
    }
    return $str;
}



//
// StringParser
//
// a parser  can  be  initialized  either  with  a
// string or an array of strings;
//
// every parser operator will  return  the  parser
// object allowing method chaining
//                                              \x

class StringParser
{

    private $result,
            $original,
            $storage;


            // the internal `result` must always be  an  array
            // of strings
            //                                              \x


    //
    // CONSTRUCTOR
    //



    function __construct( $result )
    {
        if( is_string( $result ) )
        {
            $result = [ $result ];
        }
        elseif( is_array( $result ) )
        {
            foreach( $result as $str )
            {
                if( ! is_string( $str ) )
                {
                    Error( "a string or array of strings must be passed" );
                }
            }
        }
        else
        {
            Error( "a string or array of strings must be passed" );
        }

        $this->result = $result;
        $this->original = $result;

        return $this;
    }



    //
    // GETTING OUTPUT
    //



    public function result()
    {
        return $this->result;
    }



    public function first() // returns `false` with no results
    {
        if( count( $this->result ) === 0 )
        {
            return false;
        }
        else
        {
            return $this->result[0];
        }
    }



    public function string() // returns empty string with no results
    {
        if( count( $this->result ) === 0 )
        {
            return "";
        }
        else
        {
            return $this->result[0];
        }
    }



    public function count()
    {
        return count( $this->result );
    }



    //
    // CHAINABLE OPERATORS
    //



    public function get_result( &$output )
    {
        $output = $this->result;
        return $this;
    }



    public function get_first( &$output ) // gives `false` with no results
    {
        if( count( $this->result ) === 0 )
        {
            $output = false;
        }
        else
        {
            $output = $this->result[0];
        }

        return $this;
    }



    public function get_string( &$output ) // gives empty string with no results
    {
        if( count( $this->result ) === 0 )
        {
            $output = "";
        }
        else
        {
            $output = $this->result[0];
        }

        return $this;
    }


    public function get_index_having( &$output, $str )
    {
        $i = 0;
        foreach( $this->result as $res )
        {
            if( StringHas( $res, $str ) )
            {
                $output = $i;
                return $this;
                /*--- EXIT POINT ---*/
            }
            $i++;
        }
        $output = false;
        return $this;
    }


    public function get_count( &$output )
    {
        $output = count( $this->result );
        return $this;
    }



    public function save( $name )
    {
        $this->storage[ $name ] = $this->result;
        return $this;
    }



    public function restore( $name = null )
    {
        if( $name === null )
        {
            $this->result = $this->original;
            return $this;
            /*--- EXIT POINT ---*/
        }

        if( isset( $this->storage[ $name ] ) )
        {
            $this->result = $this->storage[ $name ];
        }
        else
        {
            Error( "$name not found" );
        }

        return $this;
    }



    public function select( $start, $count = 1 )
    {
        if( ( ( is_int( $start ) && $start >= 0 ) || $start === false ) && is_int( $count ) && $count >= 0 )
        {
            // ok
        }
        else
        {
            Error( "parameters must be int >=0; `start` can be `false`" );
        }

        $out = [];

        if( $start !== false )
        {
            for( $i = $start; $i < $start + $count; $i++ )
            {
                if( isset( $this->result[ $i ] ) )
                {
                    $out[] = $this->result[ $i ];
                }
            }
        }

        $this->result = $out;

        return $this;
    }



    public function has( $what, $options = STRING_NO_OPTIONS )
    {
        $this->result = StringHas( $this->result, $what, $options );
        return $this;
    }



    public function between( $sm, $em, $options = STRING_NO_OPTIONS )
    {
        $this->result = StringsBetween( $this->result, $sm, $em, $options );
        return $this;
    }



    public function trim( $mask = " \t\n\r\0\x0B" )
    {
        $this->result = StringTrim( $this->result, $mask );
        return $this;
    }



    public function append( $what )
    {
        if( ! is_string( $what ) )
        {
            Error( "parameter must be string" );
            /*--- QUIT POINT ---*/
        }

        $output = [];
        foreach( $this->result as $str )
        {
            $output[] = $str . $what;
        }
        $this->result = $output;
        return $this;
    }



    public function prepend( $what )
    {
        if( ! is_string( $what ) )
        {
            Error( "parameter must be string" );
            /*--- QUIT POINT ---*/
        }

        $output = [];
        foreach( $this->result as $str )
        {
            $output[] = $what . $str;
        }
        $this->result = $output;
        return $this;
    }



    public function replace( $search, $replace, $options = STRING_NO_OPTIONS )
    {
        $this->result = StringReplace( $this->result, $search, $replace, $options );
        return $this;
    }



    public function remove( $what )
    {
        $this->result = StringRemove( $this->result, $what );
        return $this;
    }



    public function lowercase()
    {
        $this->result = StringLowercase( $this->result );
        return $this;
    }



    public function uppercase()
    {
        $this->result = StringUppercase( $this->result );
        return $this;
    }



    public function html_to_text( $flags = ENT_QUOTES | ENT_HTML5 )
    {
        $this->result = StringHtmlToText( $this->result, $flags );
        return $this;
    }



    public function percent_escape( $except = "" )
    {
        $this->result = StringPercentEscape( $this->result, $except );
        return $this;
    }



    public function compact( $preserve = '' )
    {
        $this->result = StringCompact( $this->result, $preserve );
        return $this;
    }



    public function position( &$position, $offset = 0 )
    {
        $position = StringPosition( $this->result, $offset );
        return $this;
    }



    public function substring( $start, $len )
    {
        $this->result = StringSubstring( $this->result, $start, $len );
        return $this;
    }



    // executes the callback `function`: the  function
    // receives a string  as  input  and  must  return
    // either `false` (the item will be  removed  from
    // the  results  array)  or  a  string  that  will
    // replace the input string                     \x

    public function execute( $function )
    {
        if( ! is_callable( $function ) )
        {
            Error( "parameter must be a function" );
        }

        $out = [];

        foreach( $this->result as $input )
        {
            $output = $function( $input );
            if( $output === false )
            {
                continue;
            }

            if( ! is_string( $output ) )
            {
                Error( "callback function must return a string or `false`" );
            }

            $out[] = $output;
        }

        $this->result = $out;

        return $this;
    }



    // join result items in a single item

    public function join( $glue = '' )
    {
        $this->result = array( implode( $glue, $this->result ) );
        return $this;
    }

}