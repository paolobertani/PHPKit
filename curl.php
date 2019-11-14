<?php

//
//
// CURL
//
//



//
// CONSTANTS
//

define( 'CURL_USERAGENT', "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:45.0) Gecko/20100101 Firefox/45.0" );  // Pretend to be Firefox
define( 'CURL_LANGUAGE',  "Accept-Language: it-IT,it;q=0.8,en-US;q=0.5,en;q=0.3" );
define( 'CURL_ACCEPT',    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" );
define( 'CURL_COOKIES',   ROOT . "/cookies.txt" );



//
// GLOBALS
//

$g_CurlDebug = false;



//
// CurlDebug
//
// Set debug mode
//

function CurlDebug( $d )
{
    global $g_CurlDebug;

    $g_CurlDebug = $d;
}



//
// CurlEncode
//
// Given a key value pairs array build a query string
// without leading `?` to be appended to URL or used
// as `$post` parameter.
// Passing an array into `$post` to `Curl`
// will encode the data as multipart/form-data,
// while passing a URL-encoded string will encode the data
// as application/x-www-form-urlencoded.
//

function CurlEncode( $params )
{
    $i = 0;
    $result = "";
    foreach( $params as $key => $val )
    {
        $result .= ( $i == 0 ? "" : "&" ) . urlencode( $key ) . "=" . urlencode( $val );
        $i++;
    }
    return $result;
}



//
// Curl
//
// Execute a request via CURL
// `$post` can be associative array, url-encoded string or `true`
// `$headers` can be an array or a string of "\n" separated values
// Call without parameters to discard cookies file
//

function Curl( $url = false, $post = null, $headers = null )
{
    // Just discard cookies?

    if( $url === false )
    {
        if( is_file( CURL_COOKIES ) )
        {
            unlink( CURL_COOKIES );
        }
        return;
        /*--- EXIT POINT ---*/
    }

    // Debug?

    global $g_CurlDebug;


    // Init curl

    $handle = curl_init();


    // Headers

    if( is_string( $headers ) )
    {
        $headers = explode( "\n", $headers );
    }
    elseif( is_array( $headers ) )
    {
        // noop
    }
    else
    {
        $headers = array();
    }

    $h = array();

    $h = CurlSetHeaderPrivate( $h, CURL_USERAGENT );
    $h = CurlSetHeaderPrivate( $h, CURL_LANGUAGE );
    $h = CurlSetHeaderPrivate( $h, CURL_ACCEPT );

    $n = count( $headers );
    for( $i = 0; $i < $n; $i++ )
    {
        $h = CurlSetHeaderPrivate( $h, $headers[ $i ] );
    }


    // Set CURLOPTs

    curl_setopt( $handle, CURLOPT_URL,              $url );
    curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );
    curl_setopt( $handle, CURLOPT_FOLLOWLOCATION,   true );
    curl_setopt( $handle, CURLOPT_AUTOREFERER,      true );
    curl_setopt( $handle, CURLOPT_MAXREDIRS,        3 );
    curl_setopt( $handle, CURLOPT_HTTPHEADER,       $h );
    curl_setopt( $handle, CURLOPT_COOKIEFILE,       CURL_COOKIES );
    curl_setopt( $handle, CURLOPT_COOKIEJAR,        CURL_COOKIES );

    curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST,   0);
    curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER,   0);

    curl_setopt( $handle, CURLOPT_TIMEOUT,          30);


    // Pass `$post` as true to make a POST request without sending data

    if( $post !== null && $post !== false )
    {
        curl_setopt( $handle, CURLOPT_POST,         true );
    }

    if( $post !== true && $post !== false )
    {
        curl_setopt( $handle, CURLOPT_POSTFIELDS,   $post );
    }


    // Retrieve headers

    $response_headers = [];

    curl_setopt( $handle, CURLOPT_HEADERFUNCTION,
        function( $curl, $header ) use ( &$response_headers )
        {
            $len = strlen( $header );
            $header = explode(':', $header, 2);
            if( count( $header ) < 2 ) { return $len; } // ignore invalid headers
            $response_headers[ strtolower( trim( $header[0] ) ) ] = trim( $header[1] );
            return $len;
        }
    );



    // Send request, get response

    $response = curl_exec( $handle );


    // Catch error

    $errnum = curl_errno( $handle );
    $error = $errnum == 0 ? '' : curl_strerror( $errnum );


    // Get status

    $status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );


    // Get effective URL in case of redirect

    $eurl = curl_getinfo( $handle, CURLINFO_EFFECTIVE_URL );


    // Cleanup

    curl_close( $handle );


    // Debug

    if( $g_CurlDebug )
    {
        echo "---\n";
        echo "url:    ".$url."\n";
        echo "post:   ".$postfields."\n";
        echo "status: ".$status."\n";
        echo "errnum: ".$errnum."\n";
        echo "error:  ".$error."\n";
        echo "hdrs:\n".implode( "\n", $headers )."\n";
        echo "---\n";
        echo $response."\n";
    }


    // Build and return result

    $result = [];
    $result[ 'response' ] = $response;
    $result[ 'headers'  ] = $response_headers;
    $result[ 'status'   ] = $status;
    $result[ 'errnum'   ] = $errnum;
    $result[ 'error'    ] = $error;
    $result[ 'url'      ] = $eurl;

    return $result;
}



//
// Private
//



// Add/Remove header

function CurlSetHeaderPrivate( $headers, $entry )
{
    $out = array();

    $e = CurlHeaderNameValuePrivate( $entry );
    $entryName = $e[0];
    $entryVal  = $e[1];

    $n = count( $headers );
    for( $i = 0; $i < $n; $i++ )
    {
        $h = CurlHeaderNameValuePrivate( $headers[ $i ] );
        $name = $h[0];
        $val  = $h[1];

        if( $name == $entryName )
        {
            $entryName = "";
            if( $entryVal != "" )
            {
                $out[] = $entry;
            }
        }
        else
        {
            $out[] = $headers[ $i ];
        }
    }

    if( $entryName != "" && $entryVal != "" )
    {
        $out[] = $entry;
    }

    return $out;
}



// Split a header entry in name and value

function CurlHeaderNameValuePrivate( $h )
{
    $nv = array( "", "" );

    $h = trim( $h );
    if( $h == "" )
    {
        return $nv;
    }

    $i = strpos( $h, ":" );
    if( $i===false )
    {
        echo "WARNING: CURL - Bad Header\n";
        return $nv;
    }

    $nv[0] = trim( substr( $h, 0, $i ) );

    if( $i < strlen( $h ) )
    {
        $nv[ 1 ] = trim( substr( $h, $i + 1 ) );
    }

    $nv[0] = strtolower( $nv[0] );

    return $nv;
}


