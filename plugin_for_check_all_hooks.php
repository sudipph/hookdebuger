<?php
/*
Plugin Name: Check All Current Hooks
Description: This is not just a plugin, Hook and Hook Location will store within log file.
Author: sudip
Version: 0.1
*/

add_action( 'all', 'log_hook_calls' );
function log_hook_calls() {
    // Restrict logging to requests from your IP - your IP address goes here
    $client_ip = "123.123.123.123"; 
    // The full path to your log file:
    $log_file_path = "/data/html/log-hook-calls.log"; 
   // if ($_SERVER['REMOTE_ADDR'] == $client_ip) { 
        if ( WP_DEBUG_LOG ) { 
            $arr = list_hooks( current_filter() );
            error_log(date("d-m-Y, H:i:s") . ": " . current_filter() .'--->>---'.current_action().'------------'.json_encode($arr). "\n", 3, $log_file_path); 
        }
   // } 
}

function list_hooks( $hook = '' ) {
    global $wp_filter;

    if ( isset( $wp_filter[$hook]->callbacks ) ) {      
        array_walk( $wp_filter[$hook]->callbacks, function( $callbacks, $priority ) use ( &$hooks ) {           
            foreach ( $callbacks as $id => $callback )
                $hooks[] = array_merge( [ 'id' => $id, 'priority' => $priority ], $callback );
        });         
    } else {
        return [];
    }

    foreach( $hooks as &$item ) {
        // skip if callback does not exist
        if ( !is_callable( $item['function'] ) ) continue;

        // function name as string or static class method eg. 'Foo::Bar'
        if ( is_string( $item['function'] ) ) {
            $ref = strpos( $item['function'], '::' ) ? new ReflectionClass( strstr( $item['function'], '::', true ) ) : new ReflectionFunction( $item['function'] );
            $item['file'] = $ref->getFileName();
            $item['line'] = get_class( $ref ) == 'ReflectionFunction' 
                ? $ref->getStartLine() 
                : $ref->getMethod( substr( $item['function'], strpos( $item['function'], '::' ) + 2 ) )->getStartLine();

        // array( object, method ), array( string object, method ), array( string object, string 'parent::method' )
        } elseif ( is_array( $item['function'] ) ) {

            $ref = new ReflectionClass( $item['function'][0] );

            // $item['function'][0] is a reference to existing object
            $item['function'] = array(
                is_object( $item['function'][0] ) ? get_class( $item['function'][0] ) : $item['function'][0],
                $item['function'][1]
            );
            $item['file'] = $ref->getFileName();
            $item['line'] = strpos( $item['function'][1], '::' )
                ? $ref->getParentClass()->getMethod( substr( $item['function'][1], strpos( $item['function'][1], '::' ) + 2 ) )->getStartLine()
                : $ref->getMethod( $item['function'][1] )->getStartLine();

        // closures
        } elseif ( is_callable( $item['function'] ) ) {     
            $ref = new ReflectionFunction( $item['function'] );         
            $item['function'] = get_class( $item['function'] );
            $item['file'] = $ref->getFileName();
            $item['line'] = $ref->getStartLine();

        }       
    }

    return $hooks;
}
?>
