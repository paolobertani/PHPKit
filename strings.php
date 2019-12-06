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



//
// GENERAL RULES:
//
// unless  otherwise  specified,   all   functions
// accept as first parameter a string, an array of
// strings or `false`.
//
// Passing `false` will  result  in  the  function
// returning `false`; if an array is  passed  then
// the function iterates over all the strings into
// the array.
//
// When  passed  an  array  of  strings  as  first
// parameter  the  function  will  always   return
// `false` or an array of strings  (not a string);
// a  notable  exception  to  this  rule  is   the
// behaviour of StringBetween
//
// Functions that  output  an  array  will  return
// `false` (not an empty array) in case the output
// array's item count is 0 (zero)
//                                              \x



//
// StringLowercase
//

function StringLowercase( $string )
{
    if( $string === false || is_string( $string ) || is_array( $string ) )
    {
        //
    }
    else
    {
        Error( "parameter must be `false`, string or array of strings: passed @types" );
    }

    if( $string === false )
    {
        return false;
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
                Error( "string must be array of strings" );
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
    if( $string === false || is_string( $string ) || is_array( $string ) )
    {
        //
    }
    else
    {
        Error( "parameter must be `false`, string or array of strings: passed @types" );
    }

    if( $string === false )
    {
        return false;
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
                Error( "string must be array of strings" );
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
// that do not meet  the  `$has`  requirement;  in
// case no items meet the requirement  `false`  is
// returned (instead of an empty array)
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

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && ( is_string( $has ) || is_array( $has ) ) && is_int( $options ) )
    {
        // OK
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    if( $string === false )
    {
        return false;
        /*--- EXIT POINT ---*/
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

    if( count( $out ) === 0 )
    {
        return false;
        /*--- EXIT POINT ---*/
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
                Error( "StringHas: `has` array must contain strings" );
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
// strings with at least one item or `false`;
//
// given the  input  string  `$str`  an  array  is
// returned with the substrings surrounded by  the
// start marker and end marker `$sm`, `$em`
//
// the start marker is searched  FIRST,  then  the
// end marker is searched AFTER the start  marker.
// Then the iteration proceeds after the found end
// marker; if a end marker is present  before  the
// start marker it is ignored
//
// in  case  there  are  no  matches  `false`   is
// returned (instead of an empty array)
//
// an array of strings  can  be  passed  as  first
// parameter in which case the  function  operates
// on every item and  returns  the  union  of  the
// results found for each item
//
// `$sm`  as  empty  string  means  beginning   of
// `$string`. `$em` as empty string means  end  of
// `$string
//
// option STRING_MARKERS let  the  start  and  end
// markers be included in the result(s)
// option STRING_FIRST let  the  function  returns
// only the first occurrence
//
// allowed options:
// STRING_MARKERS
// STRING_FIRST
//                                              \x

function StringsBetween( $string, $sm, $em, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && ( is_string( $sm ) && is_string( $em ) && is_int( $options ) ) )
    {
        // OK
    }
    else
    {
        Error( "wrong parameters type: passed @types");
        /*--- QUIT POINT ---*/
    }


    // `false` on `false` input

    if( $string === false )
    {
        return false;
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

        if( $res !== false )
        {
            $results = array_merge( $results, $res );
        }
    }


    // return `false` with no results

    if( count( $results ) === 0 )
    {
        return false;
        /*--- EXIT POINT ---*/
    }


    // otherwise return matches array

    return $results;
}

// operates on single string

function StringsBetweenPrivate( $str, $sm, $em, $options )
{

    // parse options

    $markers = $options & STRING_MARKERS;
    $first   = $options & STRING_FIRST;


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

        $idx = $e + $eml;

        if( $idx >= $len - 1 )
        {
            break;
        }

        if( $sml === 0 || $eml === 0 )
        {
            break;
        }
    }


    // return `false` with no results

    if( count( $results ) === 0 )
    {
        return false;
        /*--- EXIT POINT ---*/
    }


    // otherwise return matches array

    return $results;
}



//
// StringBetween
//
// operates like StringsBetween but only the first
// occurrence is returned as string; returns false
// in case of no match;
// DIFFERENTLY from StringsBetween, if  the  first
// parameter is a string, a string (or `false`) is
// returned; if the first parameter  is  an  array
// then an array (or `false`) is returned
//
// allowed options:
// STRING_MARKERS
//                                              \x

function StringBetween( $string, $sm, $em, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && ( is_string( $sm ) && is_string( $em ) && is_int( $options ) ) )
    {
        // OK
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


    // parse and rebuild options

    $options = $options & STRING_MARKERS;
    $options = $options | STRING_FIRST;


    // use StringsBetween

    $result = StringsBetween( $string, $sm, $em, $options);

    if( $result === false )
    {
        return false;
        /*--- EXIT POINT ---*/
    }

    if( is_array( $string ) )
    {
        return $result;
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
// at least one of the items of `$with`
//
// `$string` must be a string or `false`
//
// allowed options:
// STRING_CI
//

function StringBegins( $string, $with, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) ) && ( is_array( $with ) || is_string( $with ) ) && is_int( $options ) )
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
                Error( "`with` array must contain strings" );
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
// at least one of the items of `$with`
//
// `$string` must be a string or `false`
//
// allowed options:
// STRING_CI
//

function StringEnds( $string, $with, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) ) && ( is_array( $with ) || is_string( $with ) ) && is_int( $options ) )
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
                Error( "`with` array must contain strings" );
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
// All parameters must be strings
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

function StringReplace( $string, $search, $replace )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && is_string( $search ) && is_string( $replace ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // `false`

    if( $string === false )
    {
        return false;
    }

    // single string

    if( is_string( $string ) )
    {
        return str_replace( $search, $replace, $string );
        /*--- EXIT POINT ---*/
    }

    // array of strings

    $out = [];
    foreach( $string as $str )
    {
        if( ! is_string( $str ) )
        {
            Error( "first parameter must be string or array of strings");
            /*--- QUIT POINT ---*/
        }
        $out[] = str_replace( $search, $replace, $str );
    }
    return $out;
}



//
// StringTrim
//

function StringTrim( $string, $mask = " \t\n\r\0\x0B" )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && is_string( $mask ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // `false`

    if( $string === false )
    {
        return false;
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
            Error( "first parameter must be string or array of strings");
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
// `string` is `false` or `start` is `false`  then
// `false` is returned. `string` can be  an  array
// of strings (false not allowed), in this case an
// array of strings of  the  same  length  of  the
// input  array  is  returned;  each  string   get
// substring applied; if  `start`  is  `false`  an
// array of empty strings is returned; `start` and
// `len` can be (both) arrays: the item  count  of
// `string`, `start` and `len` must  match,  every
// string get substring applied  using  the  start
// and len value from `start` and `len` arrays  at
// the same index. Again if start is `false`  then
// the corresponding string is turned into a empty
// string                                       \x

function StringSubstring( $string, $start, $len )
{

    // false case first, without checking `start` and `len`

    if( $string === false )
    {
        return false;
        /*--- EXIT POINT ---*/
    }

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
            return false;
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
                Error( '`string` must be array of strings' );
                /*--- QUIT POINT ---*/
            }

            if( $start === false )
            {
                $out[] = "";
            }
            else
            {
                $out[] = mb_substr( $str, $start, $len );
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

        if( ! is_string( $str ) ) { Error( '`string` must be array of strings' ); }                         /*--- QUIT POINT ---*/
        if( ! is_int( $s ) && $s !== false ) { Error( 'items in array `start` must be int or false' ); }    /*--- QUIT POINT ---*/
        if( ! is_int( $l ) ) { Error( '`len` must be array of ints' ); }                                    /*--- QUIT POINT ---*/

        if( $s === false )
        {
            $out[] = '';
        }
        else
        {
            $out[] = mb_substr( $str, $s, $l );
        }
    }
    return $out;
}



//
// StringPosition
//
// `string` can ba a  string,  in  this  case  the
// function operates just as  mb_strpos;  `string`
// can be false and the  function  returns  false;
// `string` can be an array of  strings  in  which
// case the function operates as mb_strpos on each
// string returning an array  of  results  of  the
// same size of `string`; the returned  array  may
// contain integer values or `false`  (string  not
// found)
//                                              \x

function StringPosition( $string, $search, $offset = 0 )
{
    if( ( is_string( $string ) || $string === false || is_array( $string ) ) && is_string( $search ) && is_int( $offset ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // false

    if( $string === false )
    {
        return false;
        /*--- EXIT POINT ---*/
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
            Error( "`string` must be array of strings");
            /*--- QUIT POINT ---*/
        }

        $out[] = @mb_strpos( $str, $search, $offset );
    }
    return $out;
}



//
// StringHtmlToText
//
// Convert html to text stripping html tags and
// converting html entities to corresponding
// characters; the `flags` parameter is passed to
// `html_entity_decode`
// ADDITIONALLY:  LN,  CR  are  removed;  mupliple
// spaces   are   turned    in    single    space;
// subsequently,  &nbsp;  is  turned  into  space,
// line-breaks tags are turned into newlines
//                                              \x

function StringHtmlToText( $string, $flags = ENT_QUOTES | ENT_HTML5 )
{
    // parameter type check

    if( $string === false || is_string( $string ) || is_array( $string ) )
    {
        // ok
    }
    else
    {
        Error( "wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }

    // `false`

    if( $string === false )
    {
        return false;
    }

    // string

    if( is_string( $string ) )
    {
        $string = StringReplace( $string, "\n", "" );
        $string = StringReplace( $string, "\r", "" );
        $string = StringReplace( $string, "&nbsp;", " " );
        $string = StringReplace( $string, "<br>", "\n" );
        $string = StringReplace( $string, "<br/>", "\n" );
        $string = StringReplace( $string, "<br />", "\n" );
        while( StringPosition( $string, "  " ) !== false )
        {
            $string = StringReplace( $string, "  ", " " );
        }

        return html_entity_decode( strip_tags( $string ), $flags );
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

        $str = StringReplace( $str, "\n", "" );
        $str = StringReplace( $str, "\r", "" );
        $str = StringReplace( $str, "&nbsp;", " " );
        $str = StringReplace( $str, "<br>", "\n" );
        $str = StringReplace( $str, "<br/>", "\n" );
        $str = StringReplace( $str, "<br />", "\n" );
        while( StringPosition( $str, "  " ) !== false )
        {
            $string = StringReplace( $str, "  ", " " );
        }

        $out[] = html_entity_decode( strip_tags( $str ), $flags );
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
// StringParser
//
//
// a parser can be initialized either with a
// string, an array of strings or `false`
//
// The output will be either `false` or an array
// of strings with at least one item
//
// every parser operator will return the parser
// object allowing methods chains
//

class StringParser
{

    private $result,
            $original,
            $storage;



    //
    // CONSTRUCTOR
    //



    function __construct( $result )
    {
        if( $result === null )
        {
            Error( "NULL passed to the constructor" );
        }

        return $this->restore( $result );
    }



    //
    // GETTING OUTPUT
    //



    public function result() // <array> | false
    {
        return $this->result;
    }



    public function first() // <string> | false
    {
        if( $this->result === false )
        {
            return false;
        }
        else
        {
            return $this->result[0];
        }
    }



    public function string() // <string>
    {
        if( $this->result === false )
        {
            return "";
        }
        else
        {
            return $this->result[0];
        }
    }



    public function count() // <int>
    {
        if( $this->result === false )
        {
            return 0;
        }
        else
        {
            return count( $this->result );
        }
    }



    //
    // CHAINABLE OPERATORS
    //



    public function restore( $result = null )
    {
        if( $result === null )
        {
            $this->result = $this->original;
            return $this;
        }

        if( $this->result === false )
        {
            // this is allowed
        }
        elseif( is_string( $result ) )
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



    public function save( $name )
    {
        $this->storage[ $name ] = $this->result;
    }



    public function load( $name )
    {
        if( isset( $this->storage[ $name ] ) )
        {
            $this->result = $this->storage[ $name ];
        }
        else
        {
            Error( "$name not found" );
        }
    }



    public function select( $start, $count = 1 )
    {
        if( is_int( $start ) && is_int( $count ) && $start >= 0 && $count >= 0 )
        {
            // ok
        }
        else
        {
            Error( "parameters must be int and positive" );
        }

        if( $this->result === false ) { return $this; }

        $out = [];

        for( $i = $start; $i < $start + $count; $i++ )
        {
            if( isset( $this->result[ $i ] ) )
            {
                $out[] = $this->result[ $i ];
            }
        }

        if( count( $out ) === 0 )
        {
            $out = false;
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



    public function append( $what ) // note: append to `false` results in `false`
    {
        if( ! is_string( $what ) )
        {
            Error( "parameter must be string" );
            /*--- QUIT POINT ---*/
        }

        if( $this->result === false ) { return $this; }

        $output = [];
        foreach( $this->result as $str )
        {
            $output[] = $str . $what;
        }
        $this->result = $output;
        return $this;
    }



    public function prepend( $what ) // note: prepend to `false` results in `false`
    {
        if( ! is_string( $what ) )
        {
            Error( "parameter must be string" );
            /*--- QUIT POINT ---*/
        }

        if( $this->result === false ) { return $this; }

        $output = [];
        foreach( $this->result as $str )
        {
            $output[] = $what . $str;
        }
        $this->result = $output;
        return $this;
    }



    public function replace( $search, $replace )
    {
        $this->result = StringReplace( $this->result, $search, $replace );
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

}