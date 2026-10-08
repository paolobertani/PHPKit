<?php

namespace Kalei\PinaxoApi;

trait etim
{

    /*
    *
    *  /etim/groups
    *  /etim/groups/{group_id}
    *
    *  GET
    *
    */

    function etim_groups_get( $group_id = null )
    {
        $path = 'groups';

        if( $group_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $group_id );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/classes
    *  /etim/classes/{class_id}
    *
    *  GET
    *
    */

    function etim_classes_get( $class_id = null )
    {
        $path = 'classes';

        if( $class_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $class_id );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/classes/group_id={group_id}
    *
    *  GET
    *
    */

    function etim_classes_get_by_group_id( $group_id )
    {
        return $this->etim_get( 'classes/group_id=' . $this->etim_url_segment( $group_id ) );
    }



    /*
    *
    *  /etim/idrolab_taxonomy
    *
    *  GET
    *
    */

    function etim_idrolab_taxonomy_get()
    {
        return $this->etim_get( 'idrolab_taxonomy' );
    }



    /*
    *
    *  /etim/idrolab_taxonomy/sectors
    *  /etim/idrolab_taxonomy/sectors/{sector_id}
    *
    *  GET
    *
    */

    function etim_idrolab_taxonomy_sectors_get( $sector_id = null )
    {
        $path = 'idrolab_taxonomy/sectors';

        if( $sector_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $sector_id );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/idrolab_taxonomy/macrofamilies
    *  /etim/idrolab_taxonomy/macrofamilies/{sector_id}
    *  /etim/idrolab_taxonomy/macrofamilies/{sector_id}/{macrofamily_id}
    *
    *  GET
    *
    */

    function etim_idrolab_taxonomy_macrofamilies_get( $sector_id = null, $macrofamily_id = null )
    {
        $path = 'idrolab_taxonomy/macrofamilies';

        if( $sector_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $sector_id );
        }

        if( $macrofamily_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $macrofamily_id );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/idrolab_taxonomy/families
    *  /etim/idrolab_taxonomy/families/{sector_id}
    *  /etim/idrolab_taxonomy/families/{sector_id}/{macrofamily_id}
    *  /etim/idrolab_taxonomy/families/{sector_id}/{macrofamily_id}/{family_id}
    *
    *  GET
    *
    */

    function etim_idrolab_taxonomy_families_get( $sector_id = null, $macrofamily_id = null, $family_id = null )
    {
        $path = 'idrolab_taxonomy/families';

        if( $sector_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $sector_id );
        }

        if( $macrofamily_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $macrofamily_id );
        }

        if( $family_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $family_id );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/price_units
    *
    *  GET
    *
    */

    function etim_price_units_get()
    {
        return $this->etim_get( 'price_units' );
    }



    /*
    *
    *  /etim/units
    *
    *  GET
    *
    */

    function etim_units_get()
    {
        return $this->etim_get( 'units' );
    }



    /*
    *
    *  /etim/values/{feature_id}/{class_id}
    *
    *  GET
    *
    */

    function etim_values_get( $feature_id, $class_id )
    {
        $path = 'values/' . $this->etim_url_segment( $feature_id ) . '/' . $this->etim_url_segment( $class_id );

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/features/{class_id}
    *
    *  GET
    *
    */

    function etim_features_get( $class_id )
    {
        return $this->etim_get( 'features/' . $this->etim_url_segment( $class_id ) );
    }



    /*
    *
    *  /etim/products/{brand_id}
    *  /etim/products/{brand_id}/{code}
    *
    *  GET
    *
    */

    function etim_products_get( $brand_id, $code = null )
    {
        $path = 'products/' . $this->etim_url_segment( $brand_id );

        if( $code !== null )
        {
            $path .= '/' . $this->etim_url_segment( $code );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/products/{brand_id}/{code}
    *
    *  PUT
    *
    */

    function etim_products_put( $brand_id, $code, $params )
    {
        $path = 'products/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code );

        return $this->etim_json_request( 'PUT', $path, $params );
    }



    /*
    *
    *  /etim/products/{brand_id}/{code}
    *
    *  POST
    *
    */

    function etim_products_post( $brand_id, $code, $params )
    {
        $path = 'products/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code );

        return $this->etim_json_request( 'POST', $path, $params );
    }



    /*
    *
    *  /etim/products/{brand_id}/{code}
    *
    *  DELETE
    *
    */

    function etim_products_delete( $brand_id, $code )
    {
        $path = 'products/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code );

        return $this->etim_json_request( 'DELETE', $path );
    }



    /*
    *
    *  /etim/products_features/{brand_id}/{code}
    *  /etim/products_features/{brand_id}/{code}/{feature_id}
    *
    *  GET
    *
    */

    function etim_products_features_get( $brand_id, $code, $feature_id = null )
    {
        $path = 'products_features/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code );

        if( $feature_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $feature_id );
        }

        return $this->etim_get( $path );
    }



    /*
    *
    *  /etim/products_features/{brand_id}/{code}/{feature_id}
    *
    *  PUT
    *
    */

    function etim_products_features_put( $brand_id, $code, $feature_id, $params )
    {
        $path = 'products_features/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code ) . '/' . $this->etim_url_segment( $feature_id );

        return $this->etim_json_request( 'PUT', $path, $params );
    }



    /*
    *
    *  /etim/products_features/{brand_id}/{code}/{feature_id}
    *
    *  POST
    *
    */

    function etim_products_features_post( $brand_id, $code, $feature_id, $params )
    {
        $path = 'products_features/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code ) . '/' . $this->etim_url_segment( $feature_id );

        return $this->etim_json_request( 'POST', $path, $params );
    }



    /*
    *
    *  /etim/products_features/{brand_id}/{code}
    *  /etim/products_features/{brand_id}/{code}/{feature_id}
    *
    *  DELETE
    *
    */

    function etim_products_features_delete( $brand_id, $code, $feature_id = null )
    {
        $path = 'products_features/' . $this->etim_url_segment( $brand_id ) . '/' . $this->etim_url_segment( $code );

        if( $feature_id !== null )
        {
            $path .= '/' . $this->etim_url_segment( $feature_id );
        }

        return $this->etim_json_request( 'DELETE', $path );
    }



    /*
    *
    *  Execute a GET request against the ETIM public API namespace.
    *
    */

    private function etim_get( $path )
    {
        $handle = curl_init();

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/etim/$path" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    "GET" );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       $this->api_headers() );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );


        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  Execute a JSON request against the ETIM public API namespace.
    *
    */

    private function etim_json_request( $method, $path, $params = null )
    {
        $handle = curl_init();
        $headers = [ 'Content-Type: application/json' ];

        curl_setopt( $handle, CURLOPT_URL,              "{$this->domain}/api/v1/etim/$path" );
        curl_setopt( $handle, CURLOPT_CUSTOMREQUEST,    $method );
        curl_setopt( $handle, CURLOPT_RETURNTRANSFER,   true );

        if( $params !== null )
        {
            $json_params = json_encode( $params );
            $headers[] = 'Content-Length: ' . strlen( $json_params );

            curl_setopt( $handle, CURLOPT_POSTFIELDS,   $json_params );
        }

        curl_setopt( $handle, CURLOPT_HTTPHEADER,       $this->api_headers( $headers ) );

        $text = curl_exec( $handle );
        $this->status = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

        if( $this->status == self::STATUS_NO_CONTENT )
        {
            $text = '{ "message": "no content" }';
        }

        $this->set_response_from_text( $text );

        return $this->status;
    }



    /*
    *
    *  Encode one API path segment without touching the endpoint separators.
    *
    */

    private function etim_url_segment( $value )
    {
        return rawurlencode( "$value" );
    }

}
