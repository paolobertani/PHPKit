<?php


//
//      Pinaxo PRODUCT_CODES interface
//
//
//      Version 1.0
//
//      Copyright 2022 Kalei
//


require_once ROOT . '/include/pinaxo/public_api.php';
require_once ROOT . '/include/../pinaxo_private/apitoken.php';
require_once ROOT . '/include/echo.php';


class PinaxoProductCodes
{

    //
    //      Private instance variables
    //

    private $api_session  = null;



    //
    //      Constructor
    //

    public function __construct( $api_session = null )
    {
        if( $api_session === null )
        {
            $api_session = new PinaxoApiSession( PINAXO_API_TOKEN );
        }

        $this->api_session = $api_session;

        return $api_session;
    }



    //
    //     Register
    //

    public function register( &$products )
    {
        EchoCR( "Registering products..." );
        $data = $products; // since `$products` is modified save it into `$data` in order to pass the same data to `product_codes_post` at each iteration
        foreach( $products as &$p ) $p[ 'code_id' ] = 0; unset( $p );
        $session = $this->api_session;
        $progress = '...';
        while( true )
        {
            EchoCR( "Registering products$progress" );
            $status = $session->product_codes_post( $data );
            if( $status === true || $status >= 300 ) break;
            $progress .= '.';
            $registered_codes = $session->response;
            foreach( $registered_codes as $rc )
            {
                foreach( $products as &$p )
                {
                    if( $p[ 'code' ] === $rc[ 'code' ] )
                    {
                        foreach( $rc as $key => $value )
                        {
                            $p[ $key ] = $value;
                        }
                    }
                } unset( $p );
            }
        }

        $failed_count = 0;
        $products_count = count( $products );
        foreach( $products as $p ) { if( $p[ 'code_id' ] === 0 ) $failed_count++; }

        if( $status !== true && $status >= 300 )
        {
            EchoNL( "Registering products failed with status: $status\n{$session->response_as_text}" );
        }
        else
        {
            if( $failed_count === 0 )
            {
                EchoNL( "Registering products: done ($products_count records)" );
            }
            else
            {
                $products_count -= $failed_count;
                EchoNL( "Registered $products_count products; registration failed on $failed_count products (due to missing values)" );
            }
        }
    }



    //
    //     Skip
    //

    public function skip( &$products )
    {
        foreach( $products as &$p ) $p[ 'code_id' ] = 0;
        unset( $p );
    }

}


