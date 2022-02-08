<?php



require_once ROOT . '/strings.php';



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