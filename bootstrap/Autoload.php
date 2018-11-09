<?php
namespace bootstrap;

class Autoload
{
    public function run($className)
    {
        spl_autoload_register( function( $class_name ) {
            /**
             * Note that actual usage may require some string operations to specify the filename
             */
            $file_name = $class_name . '.php';
            if( file_exists( $file_name ) ) {
                require $file_name;
            }
        } );
    }
}