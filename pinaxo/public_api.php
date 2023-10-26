<?php



//
//      Pinaxo API Php Library
//
//
//      API Version         v1
//
//      Library version     3.0
//
//      Copyright 2013-2023 Kalei
//



require_once ROOT . '/include/3rd-parts/html2text/html2text.php';



class PinaxoApiSession
{

    private $token  = null,
            $domain = null,
            $ssl    = null,


            // batch processing management

            $batch_progress = false,    // false: batch ready or completed; true: batch in progress
            $batch_fn       = '',       // function performing batch processing
            $batch_count    = 0,        // batch number
            $batch_data     = [],       // batch data is stored upon first request
            $batch_size     = 0;        // size of batches



    //
    //      Public instance variables
    //

    public  $response           = null,
            $response_as_text   = null,
            $status             = null;



    //
    // Status codes
    //

    const

        // SUCCESS

            STATUS_OK                   =   200,
            STATUS_CREATED              =   201,
            STATUS_NO_CONTENT           =   204,

        // REDIRECTION

            STATUS_MOVED_PERMANENTLY    =   301,
            STATUS_NOT_MODIFIED         =   304,

        // CLIENT ERROR

            STATUS_BAD_REQUEST          =   400,
            STATUS_UNAUTHORIZED         =   401,
            STATUS_FORBIDDEN            =   403,
            STATUS_NOT_FOUND            =   404,
            STATUS_METHOD_NOT_ALLOWED   =   405,
            STATUS_CONFLICT             =   409,
            STATUS_TOO_MANY_REQUESTS    =   429,

        // SERVER ERROR

            STATUS_INTERNAL_SERVER_ERROR=   500,
            STATUS_SERVICE_UNAVAILABLE  =   503;



    //
    //      API Session Constructor
    //

    function __construct( $api_token, $host = "www.pinaxo.com", $ssl = true )
    {
        $this->token = $api_token;
        $this->ssl = $ssl;

        $scheme = $ssl ? "https" : "http";

        $this->domain = "$scheme://$host";

        if( ! $ssl )
        {
            echo "Warning: SSL is off.\n";
            echo "Proceed only on local development evironments.\n";
            echo "Pinaxo APIs require HTTPS connections.\n";
        }
    }



    //
    //      /documents/
    //
    //      POST
    //

    function documents_post( $params )
    {
        $json_params = json_encode( $params );
        $size = strlen( $json_params );

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "POST" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_POSTFIELDS,       $json_params );
        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
                                                          "Content-Type: application/json",
                                                          "Content-Length: $size" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /documents/{id}
    //
    //      PUT
    //

    function documents_put( $id, $params )
    {
        $json_params = json_encode( $params );
        $size = strlen( $json_params );

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "PUT" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_POSTFIELDS,       $json_params );
        curl_setopt( $handle, CURLOPT_HTTPHEADER,       array(  "Authorization: {$this->token}",
                                                                "Content-Type: application/json",
                                                                "Content-Length: $size" ) );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /documents/{id}
    //
    //      GET
    //

    function documents_get( $id = null )
    {
        $handle = curl_init();

        $url = ( $id === null || $id === false || $id === '' ) ? "{$this->domain}/api/v1/documents" : "{$this->domain}/api/v1/documents/$id";

        curl_setopt( $handle, CURLOPT_URL,              $url );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       array(  "Authorization: {$this->token}" ) );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /documents/{id}
    //
    //      DELETE
    //

    function documents_delete( $id )
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id" );
        curl_setopt( $handle, CURLOPT_FOLLOWLOCATION,   false );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "DELETE" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /documents/{id}/pdf
    //
    //      PUT
    //

    function documents_pdf_put( $id, $absolutePath )
    {
        $file_handle = fopen( $absolutePath, "r" );

        $handle = curl_init();
        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/pdf" );
        curl_setopt( $handle, CURLOPT_PUT,              true );
        curl_setopt( $handle, CURLOPT_UPLOAD,           true );
        curl_setopt( $handle, CURLOPT_INFILE,           $file_handle );
        curl_setopt( $handle, CURLOPT_INFILESIZE,       filesize( $absolutePath ) );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
                                                          "Content-Type: application/pdf" ] );
        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /documents/{id}/pdf
    //
    //      GET
    //

    function documents_pdf_get( $id, $absolutePath )
    {
        $file_handle = fopen( $absolutePath, "w" );

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/pdf" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_FOLLOWLOCATION,   true );
        curl_setopt( $handle, CURLOPT_FILE,             $file_handle );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );
        fclose( $file_handle );

        // If something goes wrong there is an error message in the output file.
        // The file is read, message retrieved then the file is deleted.

        if($this->status >= 300)
        {
            $file_handle = fopen( $absolutePath, "r" );
            $text = fread( $file_handle, 8192 );
            fclose( $file_handle );
            unlink( $absolutePath );
            $this->set_response_from_text( $text );
        }
        else
        {
            $this->set_response_from_text( '{ "message": "pdf saved to file" }' );
        }

        return $this->status;
    }



    //
    //      /documents/{id}/viewer
    //
    //      GET
    //

    function documents_viewer_get( $id, $params, $user_agent = "" )
    {
        $json_params = json_encode( $params );
        $size = strlen( $json_params );

        $headers = [ "Authorization: {$this->token}",
                     "Content-Type: application/json",
                     "Content-Length: $size" ];

        if( ! empty ( $user_agent  ) )
        {
            $headers[] = "User-Agent: " . $user_agent;
        }

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/viewer" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_POSTFIELDS,       $json_params );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       $headers );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        if( $this->status >= 300 ) // In case of error a JSON object is expected in the response
        {
            $this->set_response_from_text( $text );
        }
        else // Otherwise the response is plain text (html)
        {
            $this->set_response_to_text( $text );
        }

        return $this->status;
    }



    //
    //      /documents/{id}/session
    //
    //      GET
    //

    function documents_session_get( $id, $params, $user_agent = "" )
    {
        $json_params = json_encode( $params );
        $size = strlen( $json_params );

        $headers = [ "Authorization: {$this->token}",
                     "Content-Type: application/json",
                     "Content-Length: $size" ];

        if( ! empty ( $user_agent  ) )
        {
            $headers[] = "User-Agent: " . $user_agent;
        }

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/session" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_POSTFIELDS,       $json_params );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       $headers );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /status
    //
    //      GET
    //

    function status_get()
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/status" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /brands
    //
    //      GET
    //

    function brands_get()
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/brands" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /pricelist/{brand_id}
    //
    //      GET
    //

    function pricelist_get( $brand_id )
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/pricelist/$brand_id" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /product_codes
    //
    //      POST
    //
    //      this functions supports batch processing:
    //      place the function call in a `while` loop;
    //      send `input` and `batch_size` at each call
    //      function returns API call's returned status code
    //      and puplic property `response` is set.
    //      Keep calling the function until instead of the status code
    //      boolean `true` is returned. At this point the job is done,
    //      break the loop.
    //      If something goes wrong then an error is raised.
    //

    function product_codes_post( $input, $batch_size = 500 )
    {
        $data = $this->manage_batch( __FUNCTION__, $input, $batch_size );
        if( $data === true ) return true; // job done

        $json_data = json_encode( $data );
        $size = strlen( $json_data );

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/product_codes" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "POST" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_POSTFIELDS,       $json_data );
        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
                                                          "Content-Type: application/json",
                                                          "Content-Length: $size" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /assets
    //
    //      POST
    //
    //      this functions supports batch processing (see above)
    //

    function assets_post( $input, $batch_size = 500 )
    {
        $data = $this->manage_batch( __FUNCTION__, $input, $batch_size );
        if( $data === true ) return true; // job done

        $json_data = json_encode( $data );
        $size = strlen( $json_data );

        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/assets" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "POST" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_POSTFIELDS,       $json_data );
        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
                                                          "Content-Type: application/json",
                                                          "Content-Length: $size" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    //
    //      /assets/tables
    //
    //      GET
    //
    //      get assets related tables
    //

    function assets_tables_get()
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/assets/tables" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        curl_close( $handle );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    // split large sets of data into smaller batches

    private function manage_batch( $fn, $data, $batch_size )
    {
        if( ! is_array( $data ) )
        {
            throw new Exception( "[Pinaxo Public API] $fn: expected array data" );
        }

        if( $this->batch_progress )
        {
            // check data and function are the same

            if( $this->batch_fn !== $fn )
            {
                throw new Exception( "[Pinaxo Public API] $fn: batch process in progress with another function ({$this->batch_fn})" );
            }

            if( json_encode( $data ) !== json_encode( $this->batch_data ) )
            {
                throw new Exception( "[Pinaxo Public API] $fn: batch process in progress with another data set" );
            }
        }
        else
        {
            // initialize batch params

            $this->batch_progress = true;
            $this->batch_fn = $fn;
            $this->batch_count = 0;
            $this->batch_data = $data;
            $this->batch_size = $batch_size;
        }

        $index = $this->batch_count * $this->batch_size;
        $this->batch_count++;
        $data_size = count( $this->batch_data );
        if( $index >= $data_size ) // all batches have been managed
        {
            // perform cleanup

            $this->batch_progress = false;
            $this->batch_fn = '';
            $this->batch_count = 0;
            $this->batch_data = [];
            $this->batch_size = 0;

            // return `true` ==> job done

            return true;
        }

        $data = [];
        $n = $index + $this->batch_size;
        if( $n > $data_size ) $n = $data_size;
        for( $i = $index; $i < $n; $i++ )
        {
            $data[] = $this->batch_data[ $i ];
        }

        // return data to be managed

        return $data;
    }



    //
    // Set the response to associative array from the text receives (json encoded data in case of success)
    //

    private function set_response_from_text( $text )
    {
        $text = $text === null ? '<null>'  : "$text";
        $text = $text === true ? '<true>'  : "$text";
        $text = $text === false? '<false>' : "$text";

        $this->response = json_decode( $text, true );

        $this->response_as_text = null;

        if( $this->response === null ) // something went wrong
        {
            $this->response = [
                'error'     =>  'software failure',
                'message'   =>  $text
            ];
            $json = false;

            if( substr( $text, 0, 15 ) === '<!DOCTYPE html>' || ( strpos( $text, '<html>' ) !== false && strpos( $text, '<html>' ) < 20 ) )
            {
                $this->response_as_text = convert_html_to_text( $text, true );
            }
        }
        else
        {
            $json = true;
        }

        if( $this->response_as_text === null )
        {
            $this->response_as_text = json_encode( $this->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        }

        return $json;
    }



    //
    // Set the response to the plain text passed
    //

    private function set_response_to_text( $text )
    {
        $text = $text === null ? '<null>'  : "$text";
        $text = $text === true ? '<true>'  : "$text";
        $text = $text === false? '<false>' : "$text";

        $this->response = $text;
        $this->response_as_text = $text;
    }

}


