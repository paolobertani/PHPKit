<?php

//
// Includes
//

require_once ROOT . '/include/strings.php';


//
// Globals
//

$gQueryCache = []; // query files (not results) are cached



//
// QueryExecute
//
//
// Execute a query; returns result as array of associative arrays.
// For UPDATE, INSERT... returns the number of affected rows
// Returns false on error
//

function QueryExecute( $name, &$error, $params = null )
{
    $error = "";

    @$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
    if( ! $mysqli )
    {
        $error = "$name: Unable to connect to database.";
        return false;
    }
    if( $mysqli->connect_errno )
    {
        $error = "$name: Unable to connect to database: {$mysqli->connect_error}";
        $mysqli->close();
        return false;
    }

    $result = $mysqli->set_charset( 'utf8' );
    if( ! $result )
    {
        $error = "$name: Unable to set charset to UTF-8: {$mysqli->error}";
        $mysqli->close();
        return false;
    }

    $query = QueryLoad( $mysqli, $name, $error, $params );
    if( $query === false )
    {
        // Error set by QueryLoad
        $mysqli->close();
        return false;
    }

    $result = $mysqli->query( $query );
    if( $result === false )
    {
        $error = "$name Unable to perform query: {$mysqli->error} | " . QueryMinify( $query );
        $mysqli->close();
        return false;
    }

    if( $result === true ) // for UPDATE, INSERT...
    {
        $output = $mysqli->affected_rows;
    }
    else
    {
        $output = $result->fetch_all( MYSQLI_ASSOC );
    }

    $mysqli->close();

    return $output;
}


//
// QueryLoad
//
// Takes the parametrized query and substitute {{parameters}} with values in the passed array
//
// If a filename with extension `.sql` is passed the query is loaded from /sql
//
// WARNING: to be used only with trusted data!
//

function QueryLoad( $mysqli, $name, &$error, $params = null )
{
    global $gQueryCache;

    $error = "";

    // Received the name of a query to load from disk

    if( substr( $name, -4, 4 ) == '.sql' )
    {
        // Look in the cache first

        $sql = false;

        foreach( $gQueryCache as $query )
        {
            if( $query['name'] == $name )
            {
                $sql = $query['sql'];
                break;
            }
        }

        // Load from disk

        if( $sql === false )
        {
            $sql = @file_get_contents( ROOT . '/sql/' . $name );

            if( $sql === false )
            {
                $error = "Unable to find query named `{$name}`";
                return false;
            }

            // Save in the cache

            $gQueryCache[] = [ 'name' => $name, 'sql' => $sql ];
        }
    }
    else // Received the sql
    {
        $sql = $name;
    }

    if( $params === null )
    {
        $params = array();
    }

    // Inject params into the query

    foreach( $params as $key => $value )
    {
        // Every passed parameter must be present
        $token = '{{' . $key . '}}';
        if( ! StringHas( $sql, $token ) )
        {
            $error = "Parameter `$key` not found in query `$name`";
            return false;
        }

        // Strings are escaped then enclosed between double quotes
        if( is_string( $value ) )
        {
            $value = '"' . $mysqli->real_escape_string( $value ) . '"';
        }

        // Parameters are replaced with values
        $sql = str_replace( $token, $value, $sql );
    }

    // There must be no tokens left

    $remainder = StringBetween( $sql, '{{' , '}}' );
    if( $remainder !== false)
    {
        $error = "Value for parameter `$remainder` in query `$name` not provided";
        return false;
    }

    // Done

    return $sql;
}



//
// QueryMinify
//
// Shorten a query by eliminating linefeeds
// and multiple spaces
//

function QueryMinify( $query )
{
    $query = str_replace( "\n", " ", $query );

    while( strpos( $query, "  " ) !== false )
    {
        $query = str_replace( "  ", " ", $query );
    }

    return $query;
}