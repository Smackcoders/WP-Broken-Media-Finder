<?php
spl_autoload_register( function ( $class ) {
	$prefix   = 'Smackcoders\\BrokenMediaFinder\\';
	$base_dir = __DIR__ . '/../includes/';
	$len      = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}
	$file = $base_dir . str_replace( '\\', '/', substr( $class, $len ) ) . '.php';
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
