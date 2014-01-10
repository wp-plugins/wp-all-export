<?php
	
	if ( ! function_exists('url_title')){

		function url_title($str, $separator = 'dash', $lowercase = FALSE)
		{
			if ($separator == 'dash')
			{
				$search		= '_';
				$replace	= '-';
			}
			else
			{
				$search		= '-';
				$replace	= '_';
			}

			$trans = array(
				'&\#\d+?;'				=> '',
				'&\S+?;'				=> '',
				'\s+'					=> $replace,
				'[^a-z0-9\-\._]'		=> '',
				$replace.'+'			=> $replace,
				$replace.'$'			=> $replace,
				'^'.$replace			=> $replace,
				'\.+$'					=> ''
			);

			$str = strip_tags($str);

			foreach ($trans as $key => $val)
			{
				$str = preg_replace("#".$key."#i", $val, $str);
			}

			if ($lowercase === TRUE)
			{
				$str = strtolower($str);
			}

			return trim(stripslashes($str));
		}
	}	

	/* Session */

	/**
	 * Return the current session status.
	 *
	 * @return int
	 */
	function pmxe_session_status() {
		
		PMXE_Plugin::$session = PMXE_Session::get_instance();

		if ( PMXE_Plugin::$session->session_started() ) {
			return PHP_SESSION_ACTIVE;
		}

		return PHP_SESSION_NONE;
	}

	/**
	 * Unset all session variables.
	 */
	function pmxe_session_unset() {
		PMXE_Plugin::$session = PMXE_Session::get_instance();

		PMXE_Plugin::$session->reset();
	}

	/**
	 * Alias of wp_session_write_close()
	 */
	function pmxe_session_commit() {		
		PMXE_Plugin::$session = PMXE_Session::get_instance();
		PMXE_Plugin::$session->write_data();	
		do_action( 'pmxe_session_commit' );
	}	

