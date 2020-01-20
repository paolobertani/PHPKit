<?php

//
//
// Filesystem
//
//



//
// INCLUDE
//

require_once ROOT . "/include/error.php";



//
// CONSTANTS AND OPTIONS
//


define( 'FS_NO_OPTIONS',        0 );
define( 'FS_FULLPATH',          1 );
define( 'FS_ZIP_DELETE',        2 );



//
// Execute a command line tool
// Accepts a string or array of strings
// When an array is passed arguments are escaped except first
//
// Note: stderr is redirected into stdout;
//       both are catched into `$output`
//       none go directly on the terminal
//

function Execute( $cmd, &$exitStatus )
{
    $output = array();
    $exitStatus = 0;

    if( is_array( $cmd ) )
    {
        $arr = $cmd;

        $cmd = $arr[0];

        for( $i = 1; $i < count($arr); $i++ )
        {
            $cmd .= ' ' . escapeshellarg( $arr[ $i ] );
        }
    }

    $cmd .= " 2>&1"; // send stderr to stdout catching both

    exec( $cmd, $output, $exitStatus );

    $output = implode( "\n", $output );

    return $output;
}



//
// The given path points to an existing file
//

function FileExists( $f )
{
    clearstatcache( true );
    return is_file( $f );
}



//
// The given path points to an existing directory
//

function DirectoryExists( $d )
{
    clearstatcache( true );
    return is_dir( $d );
}



//
// Make all the directories to build up the path provided
//

function MakeDirectoryTree( $d, $mode = 0755 )
{
    if( DirectoryExists( $d ) )
    {
        return;
    }

    $result = @mkdir( $d, $mode, true );

    if( ! $result )
    {
        Error( "Cannot make directory tree: $d" );
        /*--- QUIT POINT ---*/
    }
}

// Shortcut

function MakeDir( $d, $mode = 0755 )
{
    return MakeDirectoryTree( $d );
}



//
// Copy a file
//

function CopyFile( $s, $d )
{
    if( ! copy( $s, $d ) )
    {
        Error( "Cannot copy $s to $d\n" );
        /*--- QUIT POINT ---*/
    }
}



//
// Attempt to produce a relative path
//

function PathRelative( $path, $root = false )
{
    if( $root === false )
    {
        $root = getcwd();
    }

    if( $root === false )
    {
        return $path;
    }

    PathAppendSlash( $root );

    if( substr( $path, 0, 1 ) !== '/' )
    {
        return $path; // path must be absolute
    }

    if( strlen( $root ) > strlen( $path ) )
    {
        return $path;
    }

    if( substr( $path, 0, strlen( $root ) ) === $root )
    {
        return substr( $path, strlen( $root ) );
    }

    return $path;
}



//
// Given a path return a list with the filenames (not full paths) of the files (not directories)
// in that directory. The directory must exists otherwise an error is raised.
//
// NOTE:
// Items that begins with dot `.` are excluded
// Symbolic links are excluded
//
// Option FS_FULLPATH will make the function return full paths
//

function FilesInDirectory( $d, $options = FS_NO_OPTIONS )
{
    if( ! DirectoryExists( $d ) )
    {
        echo "filesystem: FilesInDirectory: directory not found: $d\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }

    $list = scandir( $d );

    $files = array();

    PathAppendSlash( $d );

    foreach( $list as $item )
    {
        if( is_file( "$d$item" ) && substr( $item, 0, 1 ) != '.' && ! is_link( "$d$item" ) )
        {
            if( $options === FS_FULLPATH )
            {
                $files[] = realpath( "$d$item" );
            }
            else
            {
                $files[] = "$item";
            }
        }
    }

    return $files;
}


//
// Given a full path return a list with the names (not full paths) of the directories
// in that directory. The directory must exists otherwise an error is raised.
//
// NOTE:
// Items that begins with dot `.` are excluded
// Symbolic links are excluded
// Optional `$fullpath` will make the function return full paths
//

function DirectoriesInDirectory( $d, $fullpath = false )
{
    if( ! DirectoryExists( $d ) )
    {
        echo "filesystem: DirectoriesInDirectory: directory not found: $d\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }

    $list = scandir( $d );

    $dirs = array();

    PathAppendSlash( $d );

    foreach( $list as $item )
    {
        if( is_dir( "$d$item" ) && substr( $item, 0, 1 ) != '.' && ! is_link( "$d$item" ) )
        {
            if( $fullpath === FS_FULLPATH )
            {
                $dirs[] = realpath( "$d$item" );
            }
            else
            {
                $dirs[] = "$item";
            }
        }
    }

    return $dirs;
}



//
// Remove the file at the path provided
//

function RemoveFile( $path )
{
    if( ! FileExists( $path ) )
    {
        return;
    }

    $result = unlink( $path );

    if( $result === false )
    {
        Error( "cannot remove: $path\n" );
        /*--- QUIT POINT ---*/
    }
}



//
// Remove the directory at the path provided and everything it contains
// For safety checks related document id is passed
//

function RemoveDirectory( $path )
{
    if( ! DirectoryExists( $path ) )
    {
        return;
    }

    $output = Execute( array( 'rm -rf', $path ), $exitStatus );

    if( $exitStatus !== 0 )
    {
        echo "filesystem: RemoveDirectory: failed: $path\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }
}



//
// Returns the size of a file
//

function GetFileSize( $f )
{
    clearstatcache( true );

    $size = filesize( $f );

    if( $size === false )
    {
        echo "filesystem: GetFileSize: failed: $path\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }

    return $size;
}



//
// Returns the size of the whole contents of a directory
//

function GetDirectorySize( $d )
{
    PathAppendSlash( $d );

    $files = FilesInDirectory( $d );
    $size = 0;

    foreach( $files as $f )
    {
        $sz = filesize( "$d$f" );
        if( $sz === false )
        {
            echo "filesystem: GetDirectorySize: failed: $d$f\n";
            exit(0);
            /*--- QUIT POINT ---*/
        }

        $size += $sz;
    }

    $dirs = DirectoriesInDirectory( $d );

    foreach( $dirs as $dir )
    {
        $size += GetDirectorySize( $d . $dir );
    }

    return $size;
}



//
// Append a trailing slash to a path if missing
//

function PathAppendSlash( &$path )
{
    if( substr( $path, -1, 1 ) !== '/' )
    {
        $path .= '/';
    }
}



//
// Remove a trailing slash to a path if present
//

function PathRemoveSlash( &$path )
{
    while( substr( $path, -1, 1 ) === '/' )
    {
        $path = substr( $path, 0, -1 );
    }
}



//
// Get extension for file path
// Extension is returned lowercase
//

function PathGetExtension( $path )
{
    return strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
}


//
// edit the path appending and/or prepending  text
// to the filename: if  `extension`  is  true  the
// existing extension (if any)  is  preserved;  if
// `false` the extension is removed; if  a  string
// is  passed  the  extension  is  changed;  if  a
// trailing slash is present it  is  preserved  in
// the returned path
//                                              \x

function PathEditFilename( $path, $prepend = '', $append = '', $extension = true )
{
    // preserve trailing slash

    $slash = substr( $path, -1, 1 ) === '/' ? '/' : '';

    // split path in parts

    $pi = pathinfo( $path );

    // remove leading ./ for relative paths to items in the current directory

    if( $pi['dirname'] === '.' )
    {
        $dir = '';
    }
    else
    {
        $dir = $pi['dirname'] . "/";
    }

    // manage the extension

    $dotext = "";

    if( $extension === false )
    {
        //
    }
    elseif( $extension === true )
    {
        if( isset( $pi['extension'] ) )
        {
            $dotext = "." . $pi['extension'];
        }
    }
    elseif( is_string( $extension ) )
    {
        if( $extension !== '' )
        {
            $dotext = ".$extension";
        }
    }
    else
    {
        Error( "`extension` must be true, false or string" );
    }

    // assemble parts

    $path = "$dir$prepend{$pi['filename']}$append$dotext$slash";

    return $path;
}



//
// ZipDirectory
//
// zip  a  directory  contents;  an   archive   is
// producted with `.zip`  extension  in  the  same
// directory  of  the  source  directory;  if  the
// target file already exists an error is produced
//
// option FS_ZIP_DELETE will delete the source
// directory after compression
//                                              \x

function ZipDirectory( $path, $options = FS_NO_OPTIONS )
{
    if( ! DirectoryExists( $path ) )
    {
        Error( "directory does not exist: $path" );
    }

    PathRemoveSlash( $path );

    if( FileExists( "$path.zip" ) )
    {
        Error( "zip would overwrite existing archive: $path.zip" );
    }

    $parent = dirname( $path );

    $cwd = getcwd();
    if( $cwd === false )
    {
        Error( "failed getcwd()" );
    }

    $result = chdir( $parent );
    if( ! $result )
    {
        Error( "failed chdir()" );
    }

    $name = basename( $path );

    $exitStatus = 0;
    $toolcall = [ "zip -rq", "$name.zip", $name ];
    $output = Execute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed zip: $toolcall");
    }

    if( $options === FS_ZIP_DELETE )
    {
        RemoveDirectory( $path );
    }

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
}



//
// Unzip
//
// unzip an archive
//
// option FS_ZIP_DELETE will  delete  the  archive
// after unzip
//                                              \x

function Unzip( $path, $options = FS_NO_OPTIONS  )
{
    if( ! FileExists( $path ) )
    {
        Error( "directory does not exists: $path" );
    }

    if( PathGetExtension( $path ) !== 'zip' )
    {
        Error( "not a zip file" );
    }

    $parent = dirname( $path );

    $cwd = getcwd();
    if( $cwd === false )
    {
        Error( "failed getcwd()" );
    }

    $result = chdir( $parent );
    if( ! $result )
    {
        Error( "failed chdir()" );
    }

    $exitStatus = 0;
    $toolcall = [ "unzip -qq", $path ];
    $output = Execute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed unzip: $toolcall");
    }

    if( $options === FS_ZIP_DELETE )
    {
        RemoveFile( $path );
    }

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
}