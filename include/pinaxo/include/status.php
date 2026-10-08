<?php

namespace Kalei\PinaxoApi;

trait status
{

    /*
     *
     *  /status
     *
     *  GET
     *
     */

    function status_get()
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/status" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );


        $this->set_response_from_text( $text );

        return $this->status;
    }

}
