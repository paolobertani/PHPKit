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
define( 'FS_FULL_PATH',         1 );
define( 'FS_ZIP_DELETE',        2 );
define( 'FS_WITH_EXTENSION',    4 );


//
// GLOBALS
//

$g_LastCommand = '';




//
// Execute a command line tool
// Accepts a string or array of strings
// When an array is passed arguments are escaped except first
//
// Note: stderr is redirected into stdout;
//       both are catched into `$output`
//       none go directly on the terminal
//

function FSExecute( $cmd, &$exitStatus )
{
    global $g_LastCommand;
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

    $g_LastCommand = $cmd;

    return $output;
}



function FSGetLastCommand()
{
    global $g_LastCommand;
    return $g_LastCommand;
}

//
// The given path points to an existing file
//

function FSFileExists( $f )
{
    clearstatcache( true );
    return is_file( $f );
}



//
// The given path points to an existing directory
//

function FSDirectoryExists( $d )
{
    clearstatcache( true );
    return is_dir( $d );
}



//
// Make all the directories to build up the path provided
//

function FSMakeDirectoryTree( $d, $mode = 0755 )
{
    if( FSDirectoryExists( $d ) )
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

function FSMakeDir( $d, $mode = 0755 )
{
    return FSMakeDirectoryTree( $d );
}



// Rename

function FSRenameItem( $old, $new )
{
    $result = rename( $old, $new );
    if( $result === false )
    {
        Error( "Cannot rename/move $old to $new" );
        /*--- QUIT POINT ---*/
    }
    clearstatcache( true );
}



//
// Copy a file, overwite the destination
//

function FSCopyFile( $s, $d )
{
    if( ! copy( $s, $d ) )
    {
        Error( "Cannot copy $s to $d\n" );
        /*--- QUIT POINT ---*/
    }
}



//
// Copy a directory, overwite the destination
//

function FSCopyDirectory( $s, $d )
{
    if( ! FSDirectoryExists( $s ) )
    {
        Error( "CopyDirectory: $s not found" );
        /*--- QUIT POINT ---*/
    }

    FSRemoveDirectory( $d );

    $output = FSExecute( array( 'cp -R', $s, $d ), $exitStatus );

    if( $exitStatus !== 0 )
    {
        echo "filesystem: CopyDirectory: failed: $s -> $d\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }
}



//
// Attempt to produce a relative path
//

function FSPathRelative( $path, $root = false )
{
    if( $root === false )
    {
        $root = getcwd();
    }

    if( $root === false )
    {
        return $path;
    }

    FSPathAppendSlash( $root );

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
// Option FS_FULLPATH will make the function FSreturn full paths
//

function FSFilesInDirectory( $d, $options = FS_NO_OPTIONS )
{
    if( ! FSDirectoryExists( $d ) )
    {
        echo "filesystem: FilesInDirectory: directory not found: $d\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }

    $list = scandir( $d );

    $files = array();

    FSPathAppendSlash( $d );

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
// Optional `$fullpath` will make the function FSreturn full paths
//

function FSDirectoriesInDirectory( $d, $fullpath = false )
{
    if( ! FSDirectoryExists( $d ) )
    {
        echo "filesystem: DirectoriesInDirectory: directory not found: $d\n";
        exit(0);
        /*--- QUIT POINT ---*/
    }

    $list = scandir( $d );

    $dirs = array();

    FSPathAppendSlash( $d );

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

function FSRemoveFile( $path )
{
    if( ! FSFileExists( $path ) )
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
//

function FSRemoveDirectory( $path )
{
    if( ! FSDirectoryExists( $path ) )
    {
        return;
    }

    $output = FSExecute( array( 'rm -rf', $path ), $exitStatus );

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

function FSGetFileSize( $f )
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

function FSGetDirectorySize( $d )
{
    FSPathAppendSlash( $d );

    $files = FSFilesInDirectory( $d );
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

    $dirs = FSDirectoriesInDirectory( $d );

    foreach( $dirs as $dir )
    {
        $size += FSGetDirectorySize( $d . $dir );
    }

    return $size;
}



//
// Append a trailing slash to a path if missing
//

function FSPathAppendSlash( &$path )
{
    if( substr( $path, -1, 1 ) !== '/' )
    {
        $path .= '/';
    }
}



//
// Remove a trailing slash to a path if present
//

function FSPathRemoveSlash( &$path )
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

function FSPathGetExtension( $path )
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

function FSPathEditFilename( $path, $prepend = '', $append = '', $extension = true )
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
// PathGetFilename
//
// returns the filename for a given path
//

function FSPathGetFilename( $path, $options = FS_NO_OPTIONS )
{
    $pi = pathinfo( $path );
    if( $options === FS_WITH_EXTENSION )
    {
        return $pi[ 'basename' ];
    }
    else
    {
        return $pi[ 'filename' ];
    }
}


//
// TarGzDirectory
//
// archive and compress a  directory  using  `tar`
// and `pigz (parallelized gzip) producing a file
// with extension `.tar.gz`.
// Usage is the same as `ZipDirectory` below.
//

function FSTarGzDirectory( $path, $options = FS_NO_OPTIONS )
{
    if( ! FSDirectoryExists( $path ) )
    {
        Error( "directory does not exist: $path" );
    }

    FSPathRemoveSlash( $path );

    if( FSFileExists( "$path.tar.gz" ) )
    {
        Error( "pigz would overwrite existing archive: $path.tar.gz" );
    }

    if( FSFileExists( "$path.tar" ) )
    {
        Error( "tar would overwrite existing archive: $path.tar" );
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
    $toolcall = [ "/usr/bin/tar -cf", "$name.tar", $name ];
    $output = FSExecute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed tar: $toolcall");
    }

    if( $options === FS_ZIP_DELETE )
    {
        FSRemoveDirectory( $path );
    }

    $exitStatus = 0;
    $toolcall = [ "/usr/local/bin/pigz -9", "$name.tar" ];
    $output = FSExecute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed pigz: $toolcall");
    }

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
}



//
// UnTarGz
//
// decompress and expand a `.tar.gz` archive
//
// option FS_ZIP_DELETE  will  delete  the  source
// archive
//                                              \x

function FSUnTarGz( $path, $options = FS_NO_OPTIONS  )
{
    if( ! FSFileExists( $path ) )
    {
        Error( "directory does not exists: $path" );
    }

    if( FSPathGetExtension( $path ) !== 'gz' )
    {
        Error( "not a gzip `.gz` file: $path" );
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
    $keep = $options === FS_ZIP_DELETE ? '' : ' -k';
    $toolcall = [ "/usr/local/bin/unpigz$keep", $path ];
    $output = FSExecute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed unpigz: $toolcall");
    }

    $path = substr( $path, 0, -3 );

    if( FSPathGetExtension( $path ) !== 'tar' )
    {
        Error( "uncompressed file is not a tar `.tar` archive: $path" );
    }

    if( ! FSFileExists( $path ) )
    {
        Error( "cannot find tar archive: $path" );
    }

    $exitStatus = 0;
    $toolcall = [ "/usr/bin/tar -xf", $path ];
    $output = FSExecute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed tar: $toolcall");
    }

    FSRemoveFile( $path );

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
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

function FSZipDirectory( $path, $options = FS_NO_OPTIONS )
{
    if( ! FSDirectoryExists( $path ) )
    {
        Error( "directory does not exist: $path" );
    }

    FSPathRemoveSlash( $path );

    if( FSFileExists( "$path.zip" ) )
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
    $output = FSExecute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed zip: $toolcall");
    }

    if( $options === FS_ZIP_DELETE )
    {
        FSRemoveDirectory( $path );
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

function FSUnzip( $path, $options = FS_NO_OPTIONS  )
{
    if( ! FSFileExists( $path ) )
    {
        Error( "directory does not exists: $path" );
    }

    if( FSPathGetExtension( $path ) !== 'zip' )
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
    $output = FSExecute( $toolcall, $exitStatus );
    if( $exitStatus != 0 )
    {
        $toolcall = implode( ' ', $toolcall );
        Error( "failed unzip: $toolcall");
    }

    if( $options === FS_ZIP_DELETE )
    {
        FSRemoveFile( $path );
    }

    $result = chdir( $cwd );
    if( ! $result )
    {
        Error( "failed chdir() when restoring cwd" );
    }
}



//
// DirectoryOfItem
//
// Get the parent directory of an item (file or directory)
// without trailing slash
//

function FSDirectoryOfItem( $path )
{
    if( substr( $path, -1, 1 ) === '/' )
    {
        $path = substr( $path, 0, -1 );
    }

    $path = explode( "/", $path );

    array_pop( $path );

    $path = implode( "/", $path );

    return $path;
}



//
// Get MD5 of file
//

function FSmd5( $path )
{
    $md5 = FSExecute( [ '/sbin/md5', '-q', $path ], $exitStatus );
    if( $exitStatus != 0 )
    {
        Error( "Failed FSmd5 of $path" );
    }
    return trim( $md5 );
}



//
// Path from ROOT
//

function FSRoot( $path = '' )
{
    if( $path === '' ) return ROOT;
    if( substr( $path, 0, 1 ) !== '/' ) $path = "/$path";
    return ROOT . $path;
}