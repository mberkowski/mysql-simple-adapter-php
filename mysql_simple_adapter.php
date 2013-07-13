<?php
if (extension_loaded('mysql')) {
	trigger_error('MySQL Adapter determined the mysql extension is loaded, and we cannot redefine functions', E_USER_ERROR);
	exit();
}
if (!extension_loaded('mysqli')) {
	trigger_error('MySQL Adapter determined the mysqli extension is not loaded!', E_USER_ERROR);
	exit();
}
else {
	$GLOBALS['mysql_simple_adapter_global_link'] = NULL;

	// Const values for ext/mysql
	define('MYSQL_ASSOC', 1);
	define('MYSQL_NUM', 2);
	define('MYSQL_BOTH', 3);

	/**
	 * Wraps mysqli_connect()
	 * 
	 * @param mixed $host 
	 * @param mixed $user 
	 * @param mixed $passwd 
	 * @param bool $new_link Re-use existing connection or establish a new link?
	 * @param mixed $client_flags NoOp For compatibility - MySQLi 
	 * @access public
	 * @return bool
	 */
	function mysql_connect($host = NULL, $user = NULL, $passwd = NULL, $new_link = FALSE, $client_flags = NULL) {
		// Return existing global link if defined and input params are the same
		if (!$new_link && $GLOBALS['mysql_simple_adapter_global_link'] instanceof mysqli) {
			return $GLOBALS['mysql_simple_adapter_global_link'];
		}
		$conn = mysqli_connect($host, $user, $passwd);
		if (!isset($GLOBALS['mysql_simple_adapter_global_link'])) {
			$GLOBALS['mysql_simple_adapter_global_link'] = $conn;
		}
		return $conn;
	}

	function mysql_select_db($dbname, $link = NULL) {
		return mysqli_select_db(mysql_adapter_get_conneection($link), $dbname);
	}

	function mysql_adapter_get_conneection($link = NULL) {
		if ($link instanceof mysqli) {
			return $link;
		}
		return isset($GLOBALS['mysql_simple_adapter_global_link']) ? $GLOBALS['mysql_simple_adapter_global_link'] : null;
	}
	function mysql_query($sql, $link = NULL) {
		return mysqli_query(mysql_adapter_get_conneection($link), $sql);
	}
	function mysql_insert_id($link = NULL) {
		return mysqli_insert_id(mysql_adapter_get_conneection($link));
	}
	function mysql_num_rows($result) {
		return mysqli_num_rows($result);
	}
	function mysql_data_seek($result, $offset = 0) {
		return mysqli_data_seek($result, (int)$offset);
	}

	function mysql_real_escape_string($string, $link = NULL) {
		return mysqli_real_escape_string(mysql_adapter_get_conneection($link), $string);
	}

	function mysql_escape_string($string) {
		return mysqli_real_escape_string(mysql_adapter_get_conneection(), $string);
	}

	function mysql_fetch_array($result, $resulttype = MYSQL_BOTH) {
		switch ($resulttype) {
			case MYSQL_ASSOC: $mysqli_type = MYSQLI_ASSOC; break;
			case MYSQL_NUM: $mysqli_type = MYSQLI_NUM; break;
			case MYSQL_BOTH:
			default: $mysqli_type = MYSQLI_BOTH; break;
		}
		return mysqli_fetch_array($result, $mysqli_type);
	}
	function mysql_fetch_assoc($result) {
		return mysql_fetch_array($result, MYSQL_ASSOC);
	}

	/**
	 * Returns most recent error - if it was issued by mysqli_connect()
	 * the connection error will be returned, otherwise the most recent
	 * error for $link will be returned
	 * 
	 * @param MySQLi $link 
	 * @return string
	 */
	function mysql_error($link = NULL) {
		$err = NULL;
		$err = mysqli_connect_error();
		if (!empty($err)) {
			$err = mysqli_connect_error();
		}
		else {
			$err = mysqli_error(mysql_adapter_get_conneection($link));
		}
		return $err;
	}
	/**
	 * Returns most recent errno - if it was issued by mysqli_connect()
	 * the connection errno will be returned, otherwise the most recent
	 * errn
	 * 
	 * @param MySQLi $link 
	 * @return string
	 */
	function mysql_errno($link = NULL) {
		$err = NULL;
		$err = mysqli_connect_errno();
		if (!empty($err)) {
			$err = mysqli_connect_errno();
		}
		else {
			$err = mysqli_errno(mysql_adapter_get_conneection($link));
		}
		return $err;
	}

	function mysql_close($link = NULL) {
		return mysqli_close(mysql_adapter_get_conneection($link));
	}

	function mysql_free_result($result) {
		return mysqli_free_result($result);
	}

	function mysql_set_charset($charset, $link = NULL) {
		return mysqli_set_charset(mysql_adapter_get_conneection($link), $charset);
	}

	function mysql_client_encoding($link = NULL) {
		return mysqli_character_set_name(mysql_adapter_get_conneection($link));
	}
	
	function mysql_get_server_info($link = NULL) {
		return mysqli_get_server_info(mysql_adapter_get_conneection($link));
	}
}
// vim: set ft=php ts=2 sw=2 sts=2 noexpandtab:
?>
