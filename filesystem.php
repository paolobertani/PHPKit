<?php

//
//
// Filesystem
//
//



//
// Includes
//

require_once ROOT . "/include/error.php";


//
// Constants
//

define( 'FS_FULLPATH', 1 );



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

    $result = mkdir( $d, $mode, true );

    if( ! $result )
    {
        Error( "Cannot make directory tree: $d\n" );
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
// Optional `$fullpath` will make the function return full paths
//

function FilesInDirectory( $d, $fullpath = false )
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
            if( $fullpath === FS_FULLPATH )
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
// Zip a directory
//

function ZipDirectory( $path )
{
    if( ! DirectoryExists( $path ) )
    {
        Error( "directory does not exists: $path" );
    }

    PathRemoveSlash( $path );

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

    RemoveDirectory( $path );

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
}



//
// Unzip a zip file and delete it after extraction
//

function Unzip( $path )
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

    RemoveFile( $path );

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
}