<?php

namespace Kalei\PinaxoApi;

trait brands
{

    /*
    *
    *  /brands
    *
    *  GET
    *
    */

    function brands_get( $id = null )
    {
        $handle = curl_init();

        $id = $id === null ? '' : "/$id";

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/brands$id" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET");
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       [ "Authorization: {$this->token}" ] );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );


        $this->set_response_from_text( $text );

        return $this->status;
    }

}

