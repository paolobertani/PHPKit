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
// `description`; optional `modifier` is a  search
// modifier appended to each code; if `search`  is
// not false (default) then each code is  searched
// using the pdfidx file in the `/temp` directory;
// when `search` is requested each product will be
// added to the file only if found.
// This  tool  can  be  used  either  just   after
// scraping (but normally no pdfidx is  available)
// or after building improved pdf
//                                              \x

function PdfToolsBuildProductsFile( $res_path, $modifier = '', $search = false )
{
    $res = ArrayFromFile( $res_path );

    $out = "";

    $pdfidxPath = PDFTOOLS_TEMP_DIR . '/temp.pdfidx';

    if( $search !== false && ! FileExists( $pdfidxPath ) )
    {
        Error( "pdfidx file missing: $pdfidxPath" );
        /*--- QUIT POINT ---*/
    }

    if( ! isset( $res[0]['code'] ) || ! isset( $res[0]['description'] ) )
    {
        Error( "resource file must have `code` and `description` columns" );
        /*--- QUIT POINT ---*/
    }

    foreach( $res as $r )
    {
        if( $r['description'] === '' ) // skip products without description
        {
            continue;
        }

        if( $search ) // check if the product's code appears into the document
        {
            $output = Execute( [ "pdfidxfind -limit 10 -pdfidx", $pdfidxPath, "-search", $modifier . $r['code'] ], $status );
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

        $out .= "$modifier{$r['code']}\t{$r['description']}\n";
    }

    $out_path = dirname( $res_path ) . "/products.txt";

    file_put_contents( $out_path, $out );

    EchoNL( "built products file: $out_path" );
}



