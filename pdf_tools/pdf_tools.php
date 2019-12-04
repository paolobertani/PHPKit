<?php

//
// PdfTools
//

require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/arrays.php';
require_once ROOT . '/include/arguments.php';
require_once ROOT . '/include/filesystem.php';
require_once ROOT . '/include/milliseconds.php';



define( 'PDFTOOLS_TEMP_DIR', ROOT . '/temp' );



function PdfToolsPdfidx( $pdfPath )
{
    MakeDir( PDFTOOLS_TEMP_DIR );

    $pdfffPath  = PDFTOOLS_TEMP_DIR . '/temp.pdfff';
    $pdfidxPath = PDFTOOLS_TEMP_DIR . '/temp.pdfidx';

    if( ! FileExists( $pdfffPath ) )
    {
        EchoNL( "Generating pdfff file" );
        $output = Execute( [ 'pdfff -suppress_warnings yes -rewrite yes -pdf', $pdfPath, '-out', $pdfffPath ], $status );
        if( $status != 0 )
        {
            EchoNL( "pdfff exited with status $status: $output" );
            exit(0);
        }
        RemoveFile( $pdfidxPath ); // if the pdfff was generated then let the pdfidx be rebuilt
    }
    else
    {
        EchoNL( "Using existing pdfff file" );
    }

    if( ! FileExists( $pdfidxPath ) )
    {
        EchoNL( "Generating pdfidx file" );
        $output = Execute( [ 'pdfidx -pdfff', $pdfffPath, '-pdfidx', $pdfidxPath ], $status );
        if( $status != 0 )
        {
            EchoNL( "pdfidx exited with status $status: $output" );
            exit(0);
        }
        RemoveFile( $linksPath ); // if the pdfidx was regenerated then let the links list file be rebuilt
    }
    else
    {
        EchoNL( "Using existing pdfidxfile" );
    }
}



function PdfToolsDeleteTempDir()
{
    if( ! DirectoryExists( PDFTOOLS_TEMP_DIR ) )
    {
        return;
    }

    if( ArgumentGet( 'keep',   ARGUMENT_BOOLEAN ) )
    {
        EchoNL( "Keeping temp files" );
        return;
    }

    $files = FilesInDirectory( PDFTOOLS_TEMP_DIR );
    foreach( $files as $f )
    {
        EcnoNL( "Removing temp file: $f" );
        RemoveFile( PDFTOOLS_TEMP_DIR . "/$f" );
    }
}


