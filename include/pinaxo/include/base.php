<?php

namespace Kalei\PinaxoApi;

require_once ROOT . '/include/3rd-parts/html2text/html2text.php';



trait base
{

    private $token  = null,
    $domain = null,
    $ssl    = null,
    $lang   = 'it',


    /*
    *  batch processing management
    */

    $batch_progress = false,    // false: batch ready or completed; true: batch in progress
    $batch_fn       = '',       // function performing batch processing
    $batch_count    = 0,        // batch number
    $batch_data     = [],       // batch data is stored upon first request
    $batch_size     = 0;        // size of batches



    /*
    *
    *  Public instance variables
    *
    */

    public  $response           = null,
    $response_as_text   = null,
    $status             = null;



    /*
    *
    *  Status codes
    *
    */

    const

        /*
    *  SUCCESS
    */

    STATUS_OK                   =   200,
    STATUS_CREATED              =   201,
    STATUS_NO_CONTENT           =   204,

    /*
    *  REDIRECTION
    */

    STATUS_MOVED_PERMANENTLY    =   301,
    STATUS_NOT_MODIFIED         =   304,

    /*
    *  CLIENT ERROR
    */

    STATUS_BAD_REQUEST          =   400,
    STATUS_UNAUTHORIZED         =   401,
    STATUS_FORBIDDEN            =   403,
    STATUS_NOT_FOUND            =   404,
    STATUS_METHOD_NOT_ALLOWED   =   405,
    STATUS_CONFLICT             =   409,
    STATUS_TOO_MANY_REQUESTS    =   429,

    /*
    *  SERVER ERROR
    */

    STATUS_INTERNAL_SERVER_ERROR=   500,
    STATUS_SERVICE_UNAVAILABLE  =   503;



    /*
    *
    *  API Session Constructor
    *
    */

    function __construct( $api_token, $lang = 'it' )
    {
        $this->token = $api_token;
        $this->lang = $this->normalize_language( $lang );

        $this->ssl = true;
        $this->domain = "https://www.pinaxo.com";
    }



    /*
    *
    *  Normalize the language identifier used for API requests.
    *
    */

    private function normalize_language( $lang )
    {
        $lang = strtolower( substr( trim( "$lang" ), 0, 2 ) );

        if( $lang === '' )
        {
            $lang = 'it';
        }

        return $lang;
    }



    /*
    *
    *  Build common HTTP headers for Pinaxo API requests.
    *
    */

    private function api_headers( $additional_headers = [] )
    {
        $headers = [
            "Authorization: {$this->token}",
            "Accept-Language: {$this->lang}"
        ];

        foreach( $additional_headers as $additional_header )
        {
            $headers[] = $additional_header;
        }

        return $headers;
    }



    /*
    *
    *  Set the response to associative array from the text received (json encoded data in case of success)
    *
    */

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
                $this->response_as_text = \convert_html_to_text( $text, true );
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



    /*
    *
    *  Set the response to the plain text passed
    *
    */

    private function set_response_to_text( $text )
    {
        $text = $text === null ? '<null>'  : "$text";
        $text = $text === true ? '<true>'  : "$text";
        $text = $text === false? '<false>' : "$text";

        $this->response = $text;
        $this->response_as_text = $text;
    }

}
