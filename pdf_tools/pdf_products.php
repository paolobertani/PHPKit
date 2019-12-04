<?php

//
// PdfProducts
//



require_once ROOT . '/include/pdf_tools/pdf_tools.php';



//
// PdfToolsBuildProductsFile
//
// Build   a   `products.txt`    file    from    a
// `resources.txt` file; the file is built in  the
// same location of the resources file;  resources
// file must contain  column  headers  `code`  and
// `description`;  if  `search`   is   not   false
// (default) then each code is searched using  the
// pdfidx file in the `/temp` directory;  `search`
// can  be  true  or  a  single  character  string
// (search modifier) that will be appended to each
// search query; when `search` is  requested  each
// product will be added to the file only if found
//                                              \x

function PdfToolsBuildProductsFile( $res_path, $search = false )
{
    $res = ArrayFromFile( $res_path );

    $out = "";

    $src = is_string( $search ) ? $search : "";

    $pdfidxPath = PDFTOOLS_TEMP_DIR . '/temp.pdfidx';

    if( $search !== false && ! FileExists( $pdfidxPath ) )
    {
        Error( "pdfidx file missing: $pdfidxPath" );
        /*--- QUIT POINT ---*/
    }

    if( ! is_set( $res[0]['code'] || ! is_set( $res[0]['description'] )
    {
        Error( "resource file must have `code` and `description` columns" );
        /*--- QUIT POINT ---*/
    }

    foreach( $res as $r )
    {
        if( $search !== false ) // check if the product's code appears into the document
        {
            $output = Execute( [ "pdfidxfind -limit 10 -pdfidx", $pdfidxPath, "-search", $src . $r['code'] ], $status );
            if( $status != 0 )
            {
                Error( "pdfidxfind exited with status $status: searching $code: $output" );
                /*--- QUIT POINT ---*/
            }

            $results = json_decode( $output, true );

            if( count( $results ) === 0 ) // skip this code
            {
                continue;
            }
        }

        $out = "$src{$r['code']}\t{$r['decription']}\n";
    }

    file_put_contents( dirname( $respath ) . "/products.txt" );
}



