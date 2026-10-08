<?php

namespace Kalei\PinaxoApi;

trait documents
{

    /*
    *
    *  /documents/
    *
    *  POST
    *
    */

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


        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  /documents/{id}
    *
    *  PUT
    *
    */

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


        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  /documents/{id}
    *
    *  GET
    *
    */

    function documents_get( $id = null )
    {
        $handle = curl_init();

        $url = $this->documents_get_url( $id );

        curl_setopt( $handle, CURLOPT_URL,              $url );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       array(  "Authorization: {$this->token}" ) );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );


        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  /documents/brand_id={id}
    *
    *  GET
    *
    *  Expand the single-object brand_id selector response into a list of document objects by following
    *  the returned occurrences_id values.
    *
    */

    function documents_get_all_by_brand_id( $brand_id )
    {
        $status = $this->documents_get( 'brand_id=' . $brand_id );

        if( $status >= 300 )
        {
            return $status;
        }

        $document_ids = $this->response[ 'occurrences_id' ] ?? [];
        $documents = [];

        foreach( $document_ids as $document_id )
        {
            $status = $this->documents_get( (int) $document_id );

            if( $status >= 300 )
            {
                return $status;
            }

            $documents[] = $this->response;
        }

        $this->status = self::STATUS_OK;
        $this->response = $documents;
        $this->response_as_text = json_encode( $documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        return $this->status;
    }



    /*
    *
    *  documents_get_url
    *
    *  Build the GET documents URL while normalizing known selector shorthand to the explicit API format.
    *
    */

    private function documents_get_url( $id )
    {
        if( $id === null || $id === false || $id === '' )
        {
            return "{$this->domain}/api/v1/documents";
        }

        if( preg_match( '/^md5-([a-f0-9]{32})$/i', $id, $matches ) )
        {
            $id = 'MD5=' . strtolower( $matches[1] );
        }
        elseif( preg_match( '/^md5=([a-f0-9]{32})$/i', $id, $matches ) )
        {
            $id = 'MD5=' . strtolower( $matches[1] );
        }
        elseif( preg_match( '/^public_id=(.+)$/i', $id, $matches ) )
        {
            $id = 'PUBLIC_ID=' . $matches[1];
        }

        return "{$this->domain}/api/v1/documents/$id";
    }



    /*
    *
    *  /documents/{id}
    *
    *  DELETE
    *
    */

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


        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  /documents/{id}/pdf
    *
    *  /documents/{id}/chunks
    *
    *  PUT
    *
    */

    function documents_pdf_put( $id, $absolutePath, $progress = false )
    {
        $file_size = filesize( $absolutePath );
        $chunk_size = 20 * 1024 * 1024;

        $format_megabytes = function( $bytes )
        {
            $megabytes = number_format( $bytes / 1024 / 1024, 1, '.', '' );
            $megabytes = rtrim( rtrim( $megabytes, '0' ), '.' );

            return $megabytes === '' ? '0' : $megabytes;
        };

        if( $progress )
        {
            EchoCR( "Uploading 0 : " . $format_megabytes( $file_size ) . " MB" );
        }

        if( $file_size > $chunk_size )
        {
            $file_handle = fopen( $absolutePath, "r" );
            $uploaded_bytes = 0;

            while( ! feof( $file_handle ) )
            {
                $chunk = fread( $file_handle, $chunk_size );

                if( $chunk === false || $chunk === '' )
                {
                    break;
                }

                $chunk_length = strlen( $chunk );

                $handle = curl_init();

                curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/chunk" );
                curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "PUT" );
                curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );
                curl_setopt( $handle, CURLOPT_POSTFIELDS,       $chunk );

                curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
                "Content-Type: application/pdf",
                "Content-Length: $chunk_length" ] );

                if( $progress )
                {
                    $uploaded_bytes_before_chunk = $uploaded_bytes;

                    curl_setopt( $handle, CURLOPT_NOPROGRESS, false );
                    curl_setopt(
                        $handle,
                        CURLOPT_PROGRESSFUNCTION,
                        function( $resource, $download_size, $downloaded, $upload_size, $uploaded ) use ( $file_size, $uploaded_bytes_before_chunk, $format_megabytes )
                        {
                            $uploaded_total = $uploaded_bytes_before_chunk + $uploaded;

                            if( $uploaded_total > $file_size )
                            {
                                $uploaded_total = $file_size;
                            }

                            EchoCR( "Uploading " . $format_megabytes( $uploaded_total ) . " : " . $format_megabytes( $file_size ) . " MB" );

                            return 0;
                        }
                    );
                }

                $text = curl_exec( $handle );
                $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

                $this->set_response_from_text( $text );

                if( $text === false || $this->status >= 300 )
                {
                    fclose( $file_handle );

                    if( $progress )
                    {
                        echo CLEARLINE;
                    }

                    return $this->status;
                }

                $uploaded_bytes += $chunk_length;

                if( $progress )
                {
                    EchoCR( "Uploading " . $format_megabytes( $uploaded_bytes ) . " : " . $format_megabytes( $file_size ) . " MB" );
                }
            }

            fclose( $file_handle );

            $handle = curl_init();

            curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/chunk" );
            curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "PUT" );
            curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );
            curl_setopt( $handle, CURLOPT_POSTFIELDS,       '' );

            curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
            "Content-Type: application/pdf",
            "Content-Length: 0" ] );

            $text = curl_exec( $handle );
            $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

            $this->set_response_from_text( $text );

            if( $progress )
            {
                echo CLEARLINE;
            }

            return $this->status;
        }

        $file_handle = fopen( $absolutePath, "r" );

        $handle = curl_init();
        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/pdf" );
        curl_setopt( $handle, CURLOPT_PUT,              true );
        curl_setopt( $handle, CURLOPT_UPLOAD,           true );
        curl_setopt( $handle, CURLOPT_INFILE,           $file_handle );
        curl_setopt( $handle, CURLOPT_INFILESIZE,       $file_size );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}",
        "Content-Type: application/pdf" ] );

        if( $progress )
        {
            curl_setopt( $handle, CURLOPT_NOPROGRESS, false );
            curl_setopt(
                $handle,
                CURLOPT_PROGRESSFUNCTION,
                function( $resource, $download_size, $downloaded, $upload_size, $uploaded ) use ( $file_size, $format_megabytes )
                {
                    if( $uploaded > $file_size )
                    {
                        $uploaded = $file_size;
                    }

                    EchoCR( "Uploading " . $format_megabytes( $uploaded ) . " : " . $format_megabytes( $file_size ) . " MB" );

                    return 0;
                }
            );
        }

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        fclose( $file_handle );

        $this->set_response_from_text( $text );

        if( $progress )
        {
            echo CLEARLINE;
        }

        return $this->status;
    }



    /*
    *
    *  /documents/{id}/pdf
    *
    *  GET
    *
    */

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

        fclose( $file_handle );

        /*
        *  If something goes wrong there is an error message in the output file.
        *  The file is read, message retrieved then the file is deleted.
        */

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



    /*
    *
    *  /documents/{id}/search/{search}
    *
    *  GET
    *
    *  Search a document by plain text; the text is URL-encoded for the API route.
    *
    */

    function documents_search_get( $id, $search )
    {
        $handle = curl_init();
        $search = rawurlencode( $search );

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/search/$search" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  /documents/{id}/markdown/{page_numbers_zero_based}
    *
    *  GET
    *
    *  Get one or more zero-based pages of worker-produced markdown as plain text.
    *
    */

    function documents_markdown_get( $id, $page_numbers_zero_based )
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/documents/$id/markdown/$page_numbers_zero_based" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        if( $this->status >= 300 )
        {
            $this->set_response_from_text( $text );
        }
        else
        {
            $this->set_response_to_text( $text );
        }

        return $this->status;
    }



    /*
    *
    *  /documents/{id}/viewer
    *
    *  GET
    *
    */

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



    /*
    *
    *  /documents/{id}/session
    *
    *  GET
    *
    */

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


        $this->set_response_from_text( $text );

        return $this->status;
    }

}
