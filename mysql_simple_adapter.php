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

	function mysql_connect($host = NULL, $user = NULL, $passwd = NULL, $dbname = NULL) {
		$conn = mysqli_connect($host, $user, $passwd, $dbname);
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

	function mysql_real_escape_string($string, $link = NULL) {
		return mysqli_real_escape_string(mysql_adapter_get_conneection($link), $string);
	}

	function mysql_escape_string($string) {
		return mysqli_real_escape_string(mysql_adapter_get_conneection(), $string);
	}

	function mysql_fetch_array($resource, $resulttype = MYSQL_BOTH) {
		switch ($resulttype) {
			case MYSQL_ASSOC: $mysqli_type = MYSQLI_ASSOC; break;
			case MYSQL_NUM: $mysqli_type = MYSQLI_NUM; break;
			case MYSQL_BOTH:
			default: $mysqli_type = MYSQLI_BOTH; break;
		}
		return mysqli_fetch_array($resource, $mysqli_type);
	}
	function mysql_fetch_assoc($resource) {
		return mysql_fetch_array($resource, MYSQL_ASSOC);
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
		if (!empty(mysqli_connect_error())) {
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
		if (!empty(mysqli_connect_errno())) {
			$err = mysqli_connect_errno();
		}
		else {
			$err = mysqli_errno(mysql_adapter_get_conneection($link));
		}
		return $err;
	}

	function mysql_errno($link = NULL) {
		return mysqli_errno(mysql_adapter_get_conneection($link));
	}
}
// vim: set ft=php ts=2 sw=2 sts=2 noexpandtab:
?>
