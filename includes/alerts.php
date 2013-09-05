<?php
if ( ! function_exists( 'log_it' ) ) {
	
	/**
	 * A custom wrapper for error_log, to provide more details about the error or debug message
	 * @param  string $message Basic debug message or description of the data
	 * @param  mixed $data     Array or string for additional info
	 */
	function log_it( $message, $data  = null ) {
		$output = $message;
		if ( $data ) {
			$output .= " // " . print_r( $data, true ) . "\r\n";
		}
		
		// Get the calling function details
		$backtrace = debug_backtrace();
		if ( ! empty( $backtrace[1]['function'] ) ) {
			$output .= ' [from ' . $backtrace[1]['function'] . '()]';
		}

		error_log( $output );
	}
}