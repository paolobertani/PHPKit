<?php



/*
 *
 *  Pinaxo ASSETS interface
 *
 *
 *  Version 1.0
 *
 *  Copyright 2022 Kalei
 *
 */




namespace Kalei\PinaxoApi
{

    require_once ROOT . '/include/pinaxo/public_api.php';
    require_once ROOT . '/include/error.php';
    require_once ROOT . '/include/aes128.php';

    require_once ROOT . '/include/../pinaxo_private/apitoken.php';
    require_once ROOT . '/include/../pinaxo_private/assets.php';



    class Assets
    {

        /*
         *
         *  Private instance variables
         *
         */

        private $api_session            = null,
                $assets                 = null,
                $languages              = null,
                $resource_types         = null,
                $file_types             = null,
                $languages_flipped      = null,
                $resource_types_flipped = null,
                $file_types_flipped     = null;



        /*
         *
         *  Constructor
         *
         */

        public function __construct( $api_session = null )
        {
            $this->languages              = [];
            $this->resource_types         = [];
            $this->file_types             = [];
            $this->languages_flipped      = [];
            $this->resource_types_flipped = [];
            $this->file_types_flipped     = [];

            $this->languages      [ 0 ] = '';
            $this->resource_types [ 0 ] = '';
            $this->file_types     [ 0 ] = '';

            if( $api_session === null )
            {
                $api_session = new Session( PINAXO_API_TOKEN );
            }

            $this->api_session = $api_session;

            $status = $api_session->assets_tables_get();
            if( $status === Session::STATUS_OK )
            {
                foreach( $api_session->response[ 'languages'      ] as $record ) $this->languages     [ $record[ 'id' ] ] = $record[ 'language'      ];
                foreach( $api_session->response[ 'file_types'     ] as $record ) $this->file_types    [ $record[ 'id' ] ] = $record[ 'file_type'     ];
                foreach( $api_session->response[ 'resource_types' ] as $record ) $this->resource_types[ $record[ 'id' ] ] = $record[ 'resource_type' ];

                $this->languages_flipped      = array_flip( $this->languages      );
                $this->resource_types_flipped = array_flip( $this->resource_types );
                $this->file_types_flipped     = array_flip( $this->file_types     );
            }
            else
            {
                Error( "cannot load assets related tables: status = $status\n" . json_encode( $api_session->response, JSON_PRETTY_PRINT ) );
            }

            return $api_session;
        }



        /*
         *
         *  Get IDs
         *
         */

        public function language_id( $value )
        {
            if( isset( $this->languages_flipped[ $value ] ) ) return $this->languages_flipped[ $value ];
            Error( "undefined language: $value" );
        }

        public function resource_type_id( $value )
        {
            if( isset( $this->resource_types_flipped[ $value ] ) ) return $this->resource_types_flipped[ $value ];
            Error( "undefined resource type: $value" );
        }

        public function file_type_id( $value )
        {
            if( isset( $this->file_types_flipped[ $value ] ) ) return $this->file_types_flipped[ $value ];
            Error( "undefined file type: $value" );
        }



        /*
         *
         *  Produce value from associative array
         *
         */

        public function value( $params )
        {
            if( !  is_array( $params )                    ) Error( "Expected associative array"        );
            if( !  isset( $params[ 'document_id'      ] ) ) Error( "`document_id` not specified"       );
            if( !  isset( $params[ 'product_code_id'  ] ) ) Error( "`product_code_id` not specified"   );
            if( !  isset( $params[ 'resource_type_id' ] ) ) Error( "`resource_type_id` not specified"  );
            if( !  isset( $params[ 'file_type_id'     ] ) ) Error( "`file_type_id` not specified"      );
            if( !  isset( $params[ 'language_id'      ] ) ) Error( "`language_id` not specified"       );
            if( ! is_int( $params[ 'document_id'      ] ) ) Error( "`document_id` is not integer"      );
            if( ! is_int( $params[ 'product_code_id'  ] ) ) Error( "`product_code_id` is not integer"  );
            if( ! is_int( $params[ 'resource_type_id' ] ) ) Error( "`resource_type_id` is not integer" );
            if( ! is_int( $params[ 'file_type_id'     ] ) ) Error( "`file_type_id` is not integer"     );
            if( ! is_int( $params[ 'language_id'      ] ) ) Error( "`language_id` is not integer"      );
            if(   0 === ( $params[ 'document_id'      ] ) ) Error( "`document_id` is zero `0`"         );
            if(   0 === ( $params[ 'product_code_id'  ] ) ) Error( "`product_code_id` is zero `0`"     );
            if(   0 === ( $params[ 'resource_type_id' ] ) ) Error( "`resource_type_id` is zero `0`"    );
            if(   0 === ( $params[ 'file_type_id'     ] ) ) Error( "`file_type_id` is zero `0`"        );


            $document_id        = $params[ 'document_id'      ];
            $product_code_id    = $params[ 'product_code_id'  ];
            $resource_type_id   = $params[ 'resource_type_id' ];
            $file_type_id       = $params[ 'file_type_id'     ];
            $language_id        = $params[ 'language_id'      ];
            $unused_1           = 0;
            $unused_2           = 0;
            $signature          = hexdec( PINAXO_ASSETS_SIG );

            $params_string = "$document_id/$product_code_id/$resource_type_id/$file_type_id/$language_id/$unused_1/$unused_2/$signature";

            $value = aes128EncryptParams( $params_string, PINAXO_ASSETS_KEY, [ 3, 4, 1, 1, 1, 1, 1, 3 ] );

            return $value;
        }



        /*
         *
         *  Register
         *
         */

        public function register( $assets )
        {
            $session = $this->api_session;
            $progress = '...';
            $created_count = 0;
            $overwritten_count = 0;
            while( true )
            {
                EchoCR( "Registering assets$progress" );
                $status = $session->assets_post( $assets );
                if( $status === true || $status >= 300 ) break;
                $progress .= '.';
                $created_count += $session->response[ 'created' ];
                $overwritten_count += $session->response[ 'overwritten' ];
            }

            if( $status !== true && $status >= 300 )
            {
                EchoNL( "Registering assets failed with status: $status\n{$session->response_as_text}" );
            }
            else
            {
                EchoNL( "Registering assets: done\nCreated: $created_count\nOverwritten: $overwritten_count" );
            }
        }

    }

}
