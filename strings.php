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
// `false` or an array of strings (never a string)
//
// Functions that  output  an  array  will  return
// `false` (not an empty array) in case the output
// array's item count is 0 (zero)
//



//
// StringLowercase
//

function StringLowercase( $string )
{
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
//
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
//

function StringHas( $string, $has, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && ( is_string( $has ) || is_array( $has ) ) && is_int( $options ) )
    {
        // OK
    }
    else
    {
        Error( "Wrong parameters type: passed @types" );
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

function StringHasPrivate( $str, $has, $not )
{
    $yes = ! $not;

    if( $has === '' )
    {
        return ( $str !== '' ) xor $not;
    }

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

    return ( strpos( $str, $has ) !== false ) xor $not;
}



//
// StringsBetween
//
// Given the  input  string  `$str`  an  array  is
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
//

function StringsBetween( $string, $sm, $em, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && ( is_string( $sm ) && is_string( $em ) && is_int( $options ) ) )
    {
        // OK
    }
    else
    {
        Error( "Wrong parameters type: passed @types");
        /*--- QUIT POINT ---*/
    }


    // `false` on `false` input

    if( $string === false )
    {
        return false;
    }


    // standard mode

    if( is_string( $string ) )
    {
        return StringsBetweenPrivate( $string, $sm, $em, $options );
        /*--- EXIT POINT ---*/
    }


    // array mode

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

function StringsBetweenPrivate( $str, $sm, $em, $options )
{

    // parse options

    $markers = $options & STRING_MARKERS;
    $first   = $options & STRING_FIRST;


    // collect results

    $results = array();

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
// the first parameter must  be  a  string  or  an
// array of strings  in  wich  case  an  array  of
// strings is returned or `false` in  case  of  no
// matches
//
// allowed options:
// STRING_MARKERS
//

function StringBetween( $string, $sm, $em, $options = STRING_NO_OPTIONS )
{

    // parameter type check

    if( ( $string === false || is_string( $string ) || is_array( $string ) ) && ( is_string( $sm ) && is_string( $em ) && is_int( $options ) ) )
    {
        // OK
    }
    else
    {
        Error( "Wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }


    // `false` on `false` input

    if( $string === false )
    {
        return false;
    }


    // parse and rebuild options

    $include = $options & STRING_MARKERS;

    $options = $include ? STRING_MARKERS : STRING_NO_OPTIONS;
    $options = $options | STRING_FIRST;


    // standard mode, a string is returned in case of match

    if( is_string( $string ) )
    {
        $results = StringsBetweenPrivate( $string, $sm, $em, $options );
        if( $results === false )
        {
            return false;
            /*--- EXIT POINT ---*/
        }
        else
        {
            return $results[ 0 ];
            /*--- EXIT POINT ---*/
        }
    }


    // array mode, array | false is returned

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



//
// StringBegins
//
// Returns true if `$string` begins  with  `$with`;
// `$with` can  be  an  array  in  which  case  the
// function returns true if `$string`  begins  with
// at least one of the items of `$with`
//
// `$strig` must be a string or `false
//
// allowed options:
// STRING_CI
//

function StringBegins( $string, $with, $options = STRING_NO_OPTIONS )
{
    // check parameters

    if( ( $string === false || is_string( $string ) ) && ( is_array( $with ) || is_string( $with ) ) && is_int( $options ) )
    {
        // ok
    }
    else
    {
        Error( "Wrong parameters type: passed @types" );
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
// StringReplaceAtBeginning
//
// Replace `src` with `rep` at the beginning of `str`
// Returns `false` if `str` does not begin with `src`
// All parameters must be strings
//


function StringReplaceAtBeginning( $string, $src, $rep )
{
    // check parameters

    if( is_string( $string ) && is_string( $src ) && is_string( $rep ) )
    {
        // ok
    }
    else
    {
        Error( "Wrong parameters type: passed @types" );
        /*--- QUIT POINT ---*/
    }


    if( ! StringBegins( $string, $src ) )
    {
        return false;
        /*--- EXIT POINT ---*/
    }

    $string = $rep . substr( $string, strlen( $src ) );

    return $string;
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

    private $result;



    //
    // CONSTRUCTOR
    //



    function __construct( $result )
    {
        if( $result === false )
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
                    Error( "StringParser: a string or array of strings must be passed to the constructor" );
                }
            }
        }
        else
        {
            Error( "StringParser: a string or array of strings must be passed to the constructor" );
        }

        $this->result = $result;

        return $this;
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
        if( ! is_string( $mask ) )
        {
            Error( "StringParser: `mask` must be string" );
            /*--- QUIT POINT ---*/
        }

        if( $this->result === false ) { return $this; }

        $output = [];
        foreach( $this->result as $str )
        {
            $output[] = trim( $str, $mask );
        }
        $this->result = $output;
        return $this;
    }



    public function append( $what ) // note: append to `false` results in `false`
    {
        if( ! is_string( $what ) )
        {
            Error( "StringParser: parameter must be string" );
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
            Error( "StringParser: parameter must be string" );
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
        if( ! is_string( $search ) || ! is_string( $replace ) )
        {
            Error( "StringParser: parameters must be string" );
            /*--- QUIT POINT ---*/
        }

        if( $this->result === false ) { return $this; }

        $output = [];
        foreach( $this->result as $str )
        {
            $output[] = str_replace( $search, $replace, $str );
        }
        $this->result = $output;
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


}