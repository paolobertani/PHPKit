<?php

//
//
// ARRAYS
//
//



require_once ROOT . '/include/error.php';
require_once ROOT . '/include/3rd-parts/phpspreadsheet/autoload.php';



//
// CONSTANTS
//

define( 'ARRAY_ASC',    1 );
define( 'ARRAY_DESC',   2 );



//
// ArrayFromFile
//
// Read an array of associative arrays from  a tab
// separated text file: first row   must   contain
// column names that  will  become  array's  keys;
// every row must contain all the columns;
// an eventuyally trailing empty row (extra   "\n"
// at the end of the file) will be ignored;
// `$null_on_empty`   will  let  non-string  empty
// values to become `null` on file parsing;
// column  names  ending  with  `::`  followed  by
// `i`,  `b`,  `s`  or  `f`  do specify the column
// values' type  (if unspecified then `string`  is
// assumed). Casting to specified types does occur
// on file parsing
//                                              \x

function ArrayFromFile( $path, $null_on_empty = false )
{
    $text = @file_get_contents( $path );
    if( $text === false )
    {
        Error( "cannot read file: $path" );
    }

    $lines = explode( "\n", $text );
    $n = count( $lines );
    if( $n < 2 )
    {
        return [];
    }

    $out  = [];
    $keys = explode( "\t", $lines[ 0 ] );
    $cols = count( $keys );


    // Assign arbitrary column names where missing

    $i = 1;
    foreach( $keys as &$key )
    {
        $key = trim( $key );
        if( $key == '' )
        {
            $key = str_pad( $i, 3, "0", STR_PAD_LEFT);
            $i++;
        }
    } unset( $key );


    // Attempt to get key types

    $types = [];
    foreach( $keys as &$key )
    {
        $parts = explode( "::", $key );
        if( count( $parts ) === 2 )
        {
            $type = $parts[1];
            if( in_array( $type, [ 'i', 'f', 's', 'b' ] ) )
            {
                $types[] = $type;
                $key = $parts[0];
            }
            else
            {
                Error( "invalid type `$type` for column `{$parts[0]}`" );
            }
        }
        else
        {
            $types[] = 's';
        }
    } unset( $key );


    // Skip JUST last line if empty

    if( $lines[ $n - 1 ] === '' ) $n--;


    // Parse rows

    for( $i = 1; $i < $n; $i++ )
    {
        $parts = explode( "\t", $lines[ $i ] );

        if( count( $parts ) < $cols )
        {
             Error( "missing column(s) at row $i" );
        }

        $row = [];

        if( $null_on_empty ) // i DO repeat myself to speed things up
        {
            for( $j = 0; $j < $cols; $j++ )
            {
                $type  = $types[ $j ];
                $value = $parts[ $j ];
                /**/if( $type === 'i' ) $value = $value === '' ? null : intval   ( $value );
                elseif( $type === 'f' ) $value = $value === '' ? null : floatval ( $value );
                elseif( $type === 'b' ) $value = $value === '' ? null : boolval  ( $value );
                $row[ $keys[ $j ] ] = $value;
            }
        }
        else
        {
            for( $j = 0; $j < $cols; $j++ )
            {
                $type  = $types[ $j ];
                $value = $parts[ $j ];
                /**/if( $type === 'i' ) $value = $value === '' ? 0      : intval   ( $value );
                elseif( $type === 'f' ) $value = $value === '' ? 0.0    : floatval ( $value );
                elseif( $type === 'b' ) $value = $value === '' ? false  : boolval  ( $value );
                $row[ $keys[ $j ] ] = $value;
            }
         }

        $out[] = $row;
    }

    return $out;
}



//
// ArrayToFile
//
// Write an array of associative arrays to  a  tab
// separated text file; the first row will contain
// the inner arrays' keys; every associative array
// into the main array must contain the same  keys
// The first row is used to  determine  the  value
// types  that  will  be stored on the column name
// unless `$save_types is set to false`
//
// Note  that  tabs  and  newlines are turned into
// spaces.
//                                              \x

function ArrayToFile( $path, $array, $store_types = true )
{
    if( count( $array ) === 0 )
    {
        file_put_contents( $path, "" );
        return;
    }

    $row = $array[ 0 ];
    $keys = [];
    $keys_with_type = [];
    $index = 0;
    foreach( $row as $key => $value )
    {
        $key = str_replace( "\t", " ", $key );
        $key = str_replace( "\n", " ", $key );
        $key = str_replace( "\r", " ", $key );
        $key = str_replace( "::", ":", $key );
        $key = trim( $key );

        $keys[] = $key;
        if( ! $store_types )
        {
            $keys_with_type[] = $key;
        }
        else
        {
            /**/if( is_string( $value ) ) $keys_with_type[] = $key . "::s";
            elseif(    is_int( $value ) ) $keys_with_type[] = $key . "::i";
            elseif(  is_float( $value ) ) $keys_with_type[] = $key . "::f";
            elseif(   is_bool( $value ) ) $keys_with_type[] = $key . "::b";
            else Error( "`" . gettype( $value ) . "` is unsupported, key: $key" );
        }
    }
    $keys_with_type = implode( "\t", $keys_with_type );

    $out = "$keys_with_type\n";
    $n = count( $array );
    $m = $n - 1;
    for( $i = 0; $i < $n; $i++ )
    {
        $row = '';
        foreach( $keys as $key )
        {
            if( isset( $array[ $i ][ $key ] ) ) $value = $array[ $i ][ $key ]; else $value = '';

            $value = str_replace( "\t", " ", $value );
            $value = str_replace( "\n", " ", $value );
            $value = str_replace( "\r", " ", $value );

            /**/if( $value === true  ) $row .= "1\t";
            elseif( $value === false ) $row .= "0\t";
            else $row .= "$value\t";
        }
        $out .= substr( $row, 0, -1 );
        if( $i < $m ) $out .= "\n";
    }

    $result = @file_put_contents( $path, $out );

    if( ! $result )
    {
        Error("Cannot write to $path");
    }
}


//
// ArrayToCSV
//
// Turn an array into a CSV string
//
// options and the defaults are
//
// "delimiter"     => ";",
// "enclosure"     => "\"",
// "decimal"       => ".",
// "null"          => "",
// "true"          => "1",
// "false"         => "0"
//
// "null" tells what to do in case a null value is encountered
// default is no value, you may prefer `0` or empty string
// to specify an empty string use the text separator you have choosen:
// "\"\"" - the exact string you write will be put in the CSV string
//

function ArrayToCSV( $array, $options = [] )
{
    if( ! is_array( $array ) )
    {
        Error( "Expected array, " . gettype( $array) . "given" );
    }

    if( ! is_array( $options ) )
    {
        Error( "Expected array as `options` parameter, " . gettype( $options ) . "given" );
    }

    $defaults = [
        "delimiter"     => ";",
        "enclosure"     => "\"",
        "decimal"       => ".",
        "null"          => "",
        "true"          => "1",
        "false"         => "0"
    ];

    foreach( $defaults as $key => $value )
    {
        if( ! array_key_exists( $key, $options ) ) $options[ $key ] = $value;
    }

    $sep = $options[ 'delimiter' ];
    $txt = $options[ 'enclosure' ];
    $dec = $options[ 'decimal' ];
    $nul = $options[ 'null' ];
    $tru = $options[ 'true' ];
    $fal = $options[ 'false' ];

    foreach( $array as &$item )
    {
        ;;;;if( $item === null ) $item = $nul;
        elseif( $item === true ) $item = $tru;
        elseif( $item === false) $item = $fal;
        elseif( is_int( $item ) )    $item = "$item";
        elseif( is_string( $item ) ) $item = $txt . str_replace( $txt, $txt.$txt, $item ) . $txt;
        elseif( is_float( $item ) )  $item = str_replace( ".", $dec, "$item" );
        else Error( "Unknow type" );
    } unset( $item );

    return implode( $sep, $array );
}



//
// ArrayFromCSV
//
// Turn a CSV string into an array
//
// set `is_header` to true when parsing the CSV file header passing also the wanted columns along with their type [ [ 'name' => '...', 'type' => 'i|f|s'], [ ... ], ... ]
// the header is then parsed and the extra columns found will be added with type `x`. With this mode a `columns` array is returned so it can be passed to subsequent calls
//

function ArrayFromCSV( &$str, $options, $columns, $is_header = false )
{
    // Manage params

    if( ! is_string( $str     ) ) Error( "Expected `str` as string, "    . gettype( $str     ) . " given" );
    if( ! is_array ( $options ) ) Error( "Expected `options` as array, " . gettype( $options ) . " given" );
    if( ! is_array ( $columns ) ) Error( "Expected `columns` as array, " . gettype( $columns ) . " given" );


    // Manage options and defaults

    $defaults = [
        "delimiter"     => ";",
        "enclosure"     => "\"",
        "decimal"       => ".",
        "null"          => NULL
    ];

    foreach( $defaults as $key => $value )
    {
        if( ! array_key_exists( $key, $options ) ) $options[ $key ] = $value;
    }

    $dlm = $options[ 'delimiter' ];
    $qts = $options[ 'enclosure' ];
    $dec = $options[ 'decimal' ];
    $nul = $options[ 'null' ];


    // Detect columns from first row? In case `columns` array is returned

    if( $is_header )
    {
        $wanted_columns = $columns;

        $idx = strpos( $str, "\n" );
        if( $idx === false ) Error( "The file contains a single line" );

        $str2 = substr( $str, 0, $idx );
        $str = substr( $str, $idx + 1 );

        $has_enc = strpos( $str2, $qts );
        if( $has_enc !== false ) Error( "The header contains enclosure characters" );

        $cols = explode( $dlm, $str2 );
        $columns = [];

        $cnt = count( $cols );

        for( $idx = 0; $idx < $cnt; $idx++ )
        {
            $c = $cols[ $idx ];

            if( isset( $wanted_columns[ $c ] ) )
            {
                $columns[ $idx ] = [ 'name' => $c, 'type' => $wanted_columns[ $c ] ];
            }
            else
            {
                $columns[ $idx ] = [ 'name' => $c, 'type' => 'x' ];
            }
        }

        return $columns;

        /*--- EXIT POINT ---*/
    }

    // ---


    // Output

    $array = [];


    // End of file?

    if( $str === "" || $str === "\n" ) return false;


    // Vars

    $colCnt = count( $columns );
    $colIdx = 0;
    $len = strlen( $str );
    $idx = 0;

    for( $colIdx = 0; $colIdx < $colCnt; $colIdx++ )
    {
        $type = $columns[ $colIdx ][ 'type' ];
        $name = $columns[ $colIdx ][ 'name' ];

        $instring = false;
        $wasstring = false;

        $value = '';

        while( true )
        {
            $char = substr( $str, $idx, 1 );
            $nextChar = substr( $str, $idx + 1, 1);

            if( $idx >= $len )
            {
                $eol = true;
                break;
            }

            if( $char === $dlm || $char === "\n" )
            {
                if( $instring )
                {
                    $value .= $char;
                    $idx++;
                    continue;
                }
                else
                {
                    $idx++;
                    break;
                }
            }

            if( $char === $qts )
            {
                if( $instring )
                {
                    if( $nextChar === $qts )
                    {
                        $value .= $char;
                        $idx++;
                        $idx++;
                        continue;
                    }
                    else
                    {
                        $instring = false;
                        $idx++;
                        continue;
                    }
                }
                else
                {
                    $wasstring = true;
                    $instring = true;
                    $idx++;
                    continue;
                }
            }

            $value .= $char;
            $idx++;
        }

        if( $type === 'x' ) continue;

        if( $value === '' )
        {
            if( ! $wasstring )
            {
                $value = $nul;
            }
        }
        else
        {
            if( $type === 'f' ) $value = floatval( str_replace( $dec, '.', $value ) );
            if( $type === 'i' ) $value = intval( $value );
            if( $type === 's' ) $value = "$value";
        }

        $array[ $name ] = $value;
    }

    $str = substr( $str, $idx );

    return $array;
}



//
// ArrayFromFileCSV
//
// Read array of associative arrays from CSV  text
// file: first row  should  contain  column  names
// that will become array's keys; every  row  must
// contain all the columns; only a trailing  empty
// row is allowed (extra "\n" at the  end  of  the
// file)                                        \x

function ArrayFromFileCSV( $path, $options = null, $requestedColumns = null )
{
    // manage params

    if( $requestedColumns === null ) $requestedColumns = [];
    if( $options === null ) $options = [];

    if( ! is_array( $options ) ) Error( "Expected array as `options` parameter, " . gettype( $options ) . "given" );
    if( ! is_array( $requestedColumns ) ) Error( "Expected array as `columns` parameter, " . gettype( $requestedColumns ) . "given" );


    // load file

    $text = file_get_contents( $path );

    if( $text === false )
    {
        Error( "cannot read file: $path" );
    }


    // normalize line terminators

    $text = str_replace( "\r\n", "\n", $text );
    $text = str_replace( "\r", "\n", $text );
    $text = trim( $text, "\n" );

    $pro = isset( $options[ 'progress' ] ) && $options[ 'progress' ] === true;


    // manage header

    $columns = ArrayFromCSV( $text, $options, $requestedColumns, true );


    // load data

    $array = [];

    $i = 1;

    while( true )
    {
        $row = ArrayFromCSV( $text, $options, $columns );

        if( $row === false ) break;
        $array[] = $row;

        if( $pro && $i % 1000 === 0 ) { echo "."; $i++; }
    }

    return $array;
}



//
// ArrayToFileCSV
//
// Write an array of associative arrays to  a  CSV
// text file; every  associative  array  into  the
// main array must contain the same keys        \x

function ArrayToFileCSV( $path, $array, $options = [] )
{
    if( ! is_array( $options ) )
    {
        Error( "Expected array as `options` parameter, " . gettype( $options ) . "given" );
    }

    if( count( $array ) === 0 )
    {
        file_put_contents( $path, "" );
        return;
    }

    $text = "";

    $keys = array_keys( $array[ 0 ] );

    $headerOptions = $options;
    $headerOptions['text'] = '';

    $text .= ArrayToCSV( $keys, $headerOptions ) . "\n";

    foreach( $array as $row )
    {
        $text .= ArrayToCSV( $row, $options ) . "\n";
    }

    file_put_contents( $path, $text );
}



//
// ArrayToXLS
//
// Write array to XLS file
//

function ArrayToXLS( $xlsPath, $array )
{
    $autosize = true;

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    $cols = [ '', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                 'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
                 'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
                 'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ' ];

    $spreadsheet->getDefaultStyle()->getFont()->setName('Verdana');
    $spreadsheet->getDefaultStyle()->getFont()->setSize(12);

    $keys = array_keys( $array[0] );
    $rows = count( $array ) + 1;

    $w = count( $keys );
    $spreadsheet->getActiveSheet()->getStyle("A1:{$cols[$w]}1")->getFont()->setBold( true );

    $x = 1;
    foreach( $keys as $k )
    {
        $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow( $x, 1, $k );
        $x++;
    }

    $y = 2;
    foreach( $array as $item )
    {
        $x = 1;
        foreach( $keys as $k )
        {
            $value = $item[$k];
            if( is_string( $value ) )
            {
                $spreadsheet->getActiveSheet()->getStyle( "{$cols[$x]}$y" )->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                $spreadsheet->getActiveSheet()->setCellValueExplicit( "{$cols[$x]}$y", $value, PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING );
            }
            else
            {
                $spreadsheet->getActiveSheet()->setCellValue( "{$cols[$x]}$y", $value );
            }
            $x++;
        }
        $y++;
    }

    if( $autosize )
    {
        $x = 1;
        foreach( $keys as $k )
        {
            $spreadsheet->getActiveSheet()->getColumnDimension( $cols[ $x ] )->setAutoSize(true);
            $x++;
        }
    }

    $spreadsheet->getActiveSheet()->setSelectedCell('A1');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter( $spreadsheet, 'Xls' );
    $writer->save( $xlsPath );

    unset( $writer );
    unset( $spreadsheet );
}



//
// ArrayFromXLS
//
// Read array from XLS or XLSX file
//
// Only one sheet can be selected, default 0 (first)
//

function ArrayFromXLS( $xlsPath, $sheet = 0 )
{
    if( substr( $xlsPath, -5 ,5 ) === '.xlsx' )
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    }
    elseif( substr( $xlsPath, -4 ,4 ) === '.xls' )
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
    }
    else
    {
        Error( "Expected xls or xlsx file: $xlsPath given");
    }

    $reader->setReadDataOnly( true );
    $spreadsheet = $reader->load( $xlsPath );

    // Read only first sheet
    // Assume columns names are in the first row

    $columns = [];

    for( $x = 1; $x < 52; $x++ )
    {
        $value = $spreadsheet->getSheet( $sheet )->getCellByColumnAndRow( $x, 1 )->getValue();
        if( $value == '' ) break;
        $columns[] = $value;
    }


    // Assume data ends on the first row with all cells empty

    $array = [];
    $n = count( $columns );
    $y = 2;
    while( true )
    {
        $row = [];

        $end = true;
        for( $x = 0; $x < $n; $x++ )
        {
            $value = $spreadsheet->getSheet( $sheet )->getCellByColumnAndRow( $x + 1, $y )->getCalculatedValue();
            if( $value !== '' && $value !== null )
            {
                $end = false;
            }
            $row[ $columns[ $x ] ] = $value;
        }

        if( $end ) break;

        $array[] = $row;

        $y++;
    }

    unset( $reader );
    unset( $spreadsheet );

    return $array;
}



//
// ArraySortByKey
//
// Sort an array  of  associative  arrays  by  the
// values of the specified key(s):  a  single  key
// may be specified as string, multiple keys  must
// be specified with an array  of  strings;  every
// associative array must  contain  all  the  keys
// used to sort the main array; sorting order  can
// be  specified  by  appending  `ASC`   (default,
// optional) or `DESC` to  one  ore  more  sorting
// keys;
//                                              \x

function ArraySortByKey( &$array, $keys )
{
    if( ! is_array( $array ) )
    {
        Error( "array expected" );
        /*--- QUIT POINT ---*/
    }

    if( is_array( $keys ) )
    {
        $order = [];
        $n = count( $keys );
        for( $i = 0; $i < $n; $i++ )
        {
            $key = $keys[ $i ];
            if( substr( $key, -3, 3 ) === "ASC" )
            {
                $order[] = 1;
                $key = substr( $key, 0, -3 );
            }
            elseif( substr( $key, -4, 4 ) === "DESC" )
            {
                $order[] = -1;
                $key = substr( $key, 0, -4 );
            }
            else
            {
                $order[] = 1;
            }
            $keys[ $i ] = $key;
        }

        usort( $array, function( $a, $b ) use ( $keys, $order, $n )
        {
            for( $i = 0; $i < $n; $i++ )
            {
                $k = $keys[ $i ];
                $o = $order[ $i ];
                if( $a[$k] == $b[$k] )
                {
                    continue;
                }
                return ( ( $a[$k] < $b[$k] ) ? -$o : $o );
            }
            return -1;
        } );
    }
    else
    {
        $key = $keys;
        if( substr( $key, -3, 3 ) === "ASC" )
        {
            $order = 1;
            $key = substr( $key, 0, -3 );
        }
        elseif( substr( $key, -4, 4 ) === "DESC" )
        {
            $order = -1;
            $key = substr( $key, 0, -4 );
        }
        else
        {
            $order = 1;
        }

        usort( $array, function( $a, $b ) use ($key, $order) { return ( ( $a[$key] < $b[$key] ) ? -$order : $order ); } );
    }

}



//
// ArraySortByArray
//
// Sort two arrays based  on  the  values  of  the
// second; option parameter may be used to specify
// sort order: `ARRAY_ASC` (default, optional)  or
// `ARRAY_DESC`
//                                              \x

function ArraySortByArray( &$a1, &$a2, $options = ARRAY_ASC )
{
    array_multisort( $a2, $a1 );

    if( $options === ARRAY_DESC )
    {
        $t1 = array_reverse( $a1 );
        $t2 = array_reverse( $a2 );
        $a1 = $t1;
        $a2 = $t2;
    }
}



//
// ArrayHasDuplicates
//
// given an array of  associative  arrays  returns
// true if two (or more) items have the same value
// for  the  specified  key;  the  array  will  be
// ordered by the specified key;
// optionally a `$flagKey`  may  be  specified  in
// which case the corresponding value in duplicate
// records will be flagged with `$flag`
//                                              \x

function ArrayHasDuplicates( &$array, $key, $flagKey = false, $flag = "@" )
{
    ArraySortByKey( $array, $key );
    $last = null;
    $n = count( $array );
    $duplicates = false;
    for( $i = 0; $i < $n; $i++ )
    {
        if( $array[ $i ][ $key ] === $last )
        {
            $duplicates = true;
            if( $flagKey !== false )
            {
                $array[ $i ][ $flagKey ] .= $flag;
            }
            else
            {
                return true;
            }
            /*--- EXIT POINT ---*/
        }
        $last = $array[ $i ][ $key ];
    }
    return $duplicates;
}



//
// ArrayRemoveDuplicates
//
// the  function  receives  the  `$array`  to   be
// processed and  the  `$key`  for  the  duplicate
// values; `$chooser`  is  a  function  (callable)
// whose purpose is described later;
//
// the function has two operative  modes:  chooser
// and manager
//
// chooser:
// items with the same value for the specified key
// are aggregated into an array and passed to  the
// `$chooser` function; the "chooser" must  add  a
// value for the key  specificed  as  `$score_key`
// (default: 'score'); for every  group  of  items
// with the same key only  the  one  with  highest
// score will be kept
//
// manager:
// to enable `manager` mode `$score_key` is set to
// `false`; items with  the  same  value  for  the
// specified key are aggregated into an array  and
// passed to the  "duplicate  manager"  `$chooser`
// function; the duplicate manager may  alter  any
// values of the received array that will  replace
// the values in the original array (normally  the
// manager will alter the key with  duplicates  to
// make them unique but this is not mandatory)  \x
//

function ArrayRemoveDuplicates( &$array, $key, $chooser, $score_key = 'score' )
{
    // manage score key

    if( $score_key === null || $score_key === '' )
    {
        $score_key = false;
    }

    // item count

    $n = count( $array );
    if( $n < 2 )
    {
        return $array;
        /*--- EXIT POINT ---*/
    }

    // sort array

    ArraySortByKey( $array, $key );

    // init output

    $out = [];

    // add NULL item at the end of the array to let the last block flush

    $array[][ $key ] = NULL;
    $n++;

    // init the first block with the first item

    if( $score_key !== false ) { $array[ 0 ][ $score_key ] = 0; }
    $block = [ $array[ 0 ]  ];
    $last  = $array[ 0 ][ $key ];

    // start from the second item

    for( $i = 1; $i < $n; $i++ )
    {
        $item = $array[ $i ];
        if( $score_key !== false ) { $item[ $score_key ] = 0; }

        if( $item[ $key ] === $last )
        {
            // duplicate: add the item to the block

            $block[] = $item;
        }
        else // not a duplicate
        {
            // if the block have more than one item run the chooser and sort by score desc.

            if( count( $block ) > 1 )
            {
                $chooser( $block );
                if( $score_key !== false ) { ArraySortByKey( $block, $score_key."DESC" ); }
            }

            // chooser: add to output the first item of the block

            if( $score_key !== false )
            {
                unset( $block[ 0 ][ $score_key ] );
                $out[] = $block[ 0 ];
            }

            // manager: add to output the block

            if( $score_key === false )
            {
                foreach( $block as $b )
                {
                    $out[] = $b;
                }
            }

            // initialize a new block with the new item

            $last = $item[ $key ];
            $block = [ $item  ];
        }
    }

    // set the array passed by reference to output array

    $array = $out;
}



//
// ArrayFind
//
// returns  the  index  of  the  item   with   the
// specified key and value; returns false in  case
// of  no  match;  optionally  `$offset`  may   be
// specified                                    \x
//

function ArrayFind( $array, $key, $value, $offset = 0 )
{
    if( ! is_array( $array ) )
    {
        Error( "ArrayFind: not an array" );
    }

    $n = count( $array );
    for( $i = $offset; $i < $n; $i++ )
    {
        if( ! is_array( $array[ $i ] ) )
        {
            Error( "ArrayFind: item at index $i is not an array: {$array[$i]}" );
        }
        if( ! array_key_exists( $key, $array[ $i ] ) )
        {
            Error( "ArrayFind: item at index $i is missing key: $key" );
        }
        if( $array[ $i ][ $key ] === $value )
        {
            return $i;
            /*--- EXIT POINT ---*/
        }
    }
    return false;
}



//
// ArraySet
//
// set keys/values for the item at  the  specified
// index; if index  is  `false`  then  the  passed
// record is added to the array
//                                              \x

function ArraySet( &$array, $index, $record )
{
    if( $index === false )
    {
        $array[] = $record;
    }
    else
    {
        foreach( $record as $key => $value )
        {
            $array[ $index ][ $key ] = $value;
        }
    }
}



//
// ArrayFix
//
// fixes an array  setting  a  default  value  for
// missing keys in every record
//                                              \x

function ArrayFix( &$array, $fix = '' )
{
    $keys = [];
    foreach( $array as $row )
    {
        foreach( $row as $key => $value )
        {
            $keys[] = $key;
        }
    }
    $keys = array_unique( $keys );
    foreach( $array as $row )
    {
        foreach( $keys as $key )
        if( ! array_key_exists( $key, $row ) )
        {
            $row[ $key ] = $fix;
        }
    }
}



//
// ArrayRemoveColumn
//
// Remove all the values with a given key
//

function ArrayRemoveColumn( &$array, $key )
{
    foreach( $array as &$record )
    {
        if( array_key_exists( $key, $record ) )
        {
            unset( $record[$key] );
        }
    } unset( $record );
}


//
// ArraySplit
//
// Split the array in sub-arrays by the given key
//

function ArraySplit( &$array, $key )
{
    $output = [];

    foreach( $array as &$record )
    {
        if( trim( $record[ $key ] )=== '' )
        {
            $record[ $key ] = 'UNDEFINED';
        }
    } unset( $record );

    foreach( $array as $record )
    {
        if( ! array_key_exists( $record[ $key ], $output ) )
        {
            $output[ $record[ $key ] ] = [];
        }
    } unset( $record );

    foreach( $array as $record )
    {
        $output[ $record[ $key ] ][] = $record;
    }

    return $output;
}



//
// ArrayInsertOrUpdate
//
// Insert or update a record
//

function ArrayInsertOrUpdate( &$array, $key, $value, $record )
{
    $index = ArrayFind( $array, $key, $value );

    if( is_callable( $record ) )
    {
        if( $index === false )
        {
            $array[] = $record( null );
        }
        else
        {
            $array[ $index ] = $record( $array[ $index ] );
        }
    }
    else
    {
        if( $index === false )
        {
            $array[] = $record;
        }
        else
        {
            foreach( $record as $k => $v )
            {
                $array[ $index ][ $k ] = $v;
            }
        }
    }
}



//
// ArrayInsertOrReplace
//
// Insert or replace a record
//

function ArrayInsertOrReplace( &$array, $key, $record )
{
    $value = $record[ $key ];

    $index = ArrayFind( $array, $key, $value );

    if( $index === false )
    {
        $array[] = $record;
    }
    else
    {
        $array[ $index ] = $record;
    }
}



//
// ArrayRequire
//
// Check the array of arrays have all the keys per each row
//

function ArrayRequire( $array, $keys )
{
    $keys = explode( ',', $keys );
    $i = 0;
    $missing = [];
    foreach( $array as $row )
    {
        $i++;
        foreach( $keys as $k )
        {
            if( ! array_key_exists( $k, $row ) ) $missing[] = $k;
        }
        if( count( $missing ) !== 0 )
        {
            $missing = implode( ',', $missing );
            Error( "array is missing key(s) $missing at row $i" );
        }
    }
}



//
// ArrayRowRequire
//
// Check the array have all the keys
//

function ArrayRowRequire( $row, $keys )
{
    ArrayRequire( [ $row ], $keys );
}






//
// ArrayJoin
//
// Join the second array to the first by key
//

function ArrayJoin( &$arrayLeft, $arrayRight, $keyLeft, $keyRight, $missing = NULL )
{
    if( $missing === null ) $missing = [];

    foreach( $arrayLeft as &$leftRow )
    {
        $index = ArrayFind( $arrayRight, $keyRight, $leftRow[$keyLeft] );
        if( $index !== false )
        {
            $rightRow = $arrayRight[$index];
        }
        else
        {
            $rightRow = $missing;
        }

        foreach( $rightRow as $k => $v )
        {
            if( $k !== $keyRight ) $leftRow[$k] = $v;
        }
    } unset( $leftRow );
}
