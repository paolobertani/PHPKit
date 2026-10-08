<?php

/*
 *
 *  PdfTools
 *
 */

require_once ROOT . '/include/echo.php';
require_once ROOT . '/include/error.php';
require_once ROOT . '/include/arrays.php';
require_once ROOT . '/include/arguments.php';
require_once ROOT . '/include/fs.php';
require_once ROOT . '/include/milliseconds.php';



define( 'PDFTOOLS_TEMP_DIR', ROOT . '/temp' );



function PdfToolsPdfidx( $pdfPath )
{
    FSMakeDir( PDFTOOLS_TEMP_DIR );

    $pdfffPath  = PdfToolsTempFilePdfff();
    $pdfidxPath = PdfToolsTempFilePdfidx();
    $linksPath  = PdfToolsTempFileLinks();

    if( ! FSFileExists( $pdfffPath ) )
    {
        EchoNL( "Generating pdfff file" );
        $output = FSExecute( [ 'pdfff -suppress_warnings yes -rewrite yes -pdf', $pdfPath, '-out', $pdfffPath ], $status );
        if( $status != 0 )
        {
            Error( "pdfff exited with status $status: $output" );
            /*--- QUIT POINT ---*/
        }
        FSRemoveFile( $pdfidxPath ); // if the pdfff was generated then let the pdfidx be rebuilt
    }
    else
    {
        EchoNL( "Using existing pdfff file" );
    }

    if( ! FSFileExists( $pdfidxPath ) )
    {
        EchoNL( "Generating pdfidx file" );
        $output = FSExecute( [ 'pdfidx -pdfff', $pdfffPath, '-pdfidx', $pdfidxPath ], $status );
        if( $status != 0 )
        {
            Error( "pdfidx exited with status $status: $output" );
            /*--- QUIT POINT ---*/
        }
        FSRemoveFile( $linksPath ); // if the pdfidx was regenerated then let the links list file be rebuilt
    }
    else
    {
        EchoNL( "Using existing pdfidxfile" );
    }

    return $pdfidxPath;
}



function PdfToolsDeleteTempDir()
{
    if( ! FSDirectoryExists( PDFTOOLS_TEMP_DIR ) )
    {
        return;
    }

    if( ! ArgumentGet( 'cleanup', ARGUMENT_BOOLEAN ) )
    {
        EchoNL( "Keeping temp files" );
        return;
    }

    $files = FSFilesInDirectory( PDFTOOLS_TEMP_DIR );
    foreach( $files as $f )
    {
        EchoNL( "Removing temp file: $f" );
        FSRemoveFile( PDFTOOLS_TEMP_DIR . "/$f" );
    }
}


function PdfToolsTempFilePdfff() { return PDFTOOLS_TEMP_DIR . '/temp.pdfff'; }

function PdfToolsTempFilePdfidx(){ return PDFTOOLS_TEMP_DIR . '/temp.pdfidx'; }

function PdfToolsTempFileLinks() { return ROOT . '/temp/temp.lks-0.txt'; }

function PdfToolsTempFilePdfIm() { return ROOT . '/temp/temp.pdf-1-images.pdf'; }

function PdfToolsTempFilePdfLk() { return ROOT . '/temp/temp.pdf-2-links.pdf'; }

function PdfToolsTempFilePdfOL() { return ROOT . '/temp/temp.pdf-3-outlines.pdf'; }
