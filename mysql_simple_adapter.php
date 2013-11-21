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
	// The global connection var will get a hashed timestamp to help guard against accidental overwriting
	define('MYSQL_SIMPLE_ADAPTER_TS_HASH', md5(time()));
	// Will hold the last-used global link
	$GLOBALS['mysql_simple_adapter_global_link_' . MYSQL_SIMPLE_ADAPTER_TS_HASH] = null;
	// Will hold the params used by the last connection
	$GLOBALS['mysql_simple_adapter_global_link_params_' . MYSQL_SIMPLE_ADAPTER_TS_HASH] = array();

	// Const values for ext/mysql
	// MySQL fetch constants
	define('MYSQL_ASSOC', MYSQLI_ASSOC);
	define('MYSQL_NUM', MYSQLI_NUM);
	define('MYSQL_BOTH', MYSQLI_BOTH);
	// MySQL client constants
	define('MYSQL_CLIENT_COMPRESS', MYSQLI_CLIENT_COMPRESS);
	define('MYSQL_CLIENT_IGNORE_SPACE', MYSQLI_CLIENT_IGNORE_SPACE);
	define('MYSQL_CLIENT_INTERACTIVE', MYSQLI_CLIENT_INTERACTIVE);
	define('MYSQL_CLIENT_SSL', MYSQLI_CLIENT_SSL);

	/**
	 * Returns the MySQL connection resource specified in $link
	 * or the last resource created if $link is null
	 * 
	 * @param MySQLi $link 
	 * @return bool
	 */
	function mysql_adapter_get_conneection($link = null) {
		if ($link instanceof mysqli) {
			return $link;
		}
		return isset($GLOBALS['mysql_simple_adapter_global_link_' . MYSQL_SIMPLE_ADAPTER_TS_HASH]) ? $GLOBALS['mysql_simple_adapter_global_link_' . MYSQL_SIMPLE_ADAPTER_TS_HASH] : null;
	}
	/**
	 * Wraps mysqli_connect()
	 * 
	 * @param string $host 
	 * @param string $user 
	 * @param string $passwd 
	 * @param bool $new_link Re-use existing connection or establish a new link?
	 * @param int $client_flags NoOp For compatibility - MySQLi 
	 * @return bool
	 */
	function mysql_connect($host = null, $user = null, $passwd = null, $new_link = FALSE, $client_flags = null) {
		// Return existing global link if defined and input params are the same
		$link_key = 'mysql_simple_adapter_global_link_' . MYSQL_SIMPLE_ADAPTER_TS_HASH;
		$params_key = 'mysql_simple_adapter_global_link_params_' . MYSQL_SIMPLE_ADAPTER_TS_HASH;

		// New link not requested and the link exists...
		if (!$new_link && $GLOBALS[$link_key] instanceof mysqli) {
			// And the connection params all match...
			if ($GLOBALS[$params_key]['host'] == $host 
			    && $GLOBALS[$params_key]['user'] == $user
			    && $GLOBALS[$params_key]['passwd'] == $passwd) {
				return $GLOBALS[$link_key];
			}
		}

		// Otherwise create a new one and store it as the new global default
		$mysqli = mysqli_init();
		if (mysqli_real_connect($mysqli, $host, $user, $passwd, NULL, NULL, NULL, $client_flags)) {
			if (!isset($GLOBALS[$link_key])) {
				$GLOBALS[$link_key] = $mysqli;
				// Set the array of last-used params
				$GLOBALS[$params_key] = array(
					'host' => $host,
					'user' => $user,
					'passwd' => $passwd
				);
			}
			return $mysqli;
		}
		else return false;
	}
	function mysql_select_db($dbname, $link = null) {
		return mysqli_select_db(mysql_adapter_get_conneection($link), $dbname);
	}
	function mysql_query($sql, $link = null) {
		return mysqli_query(mysql_adapter_get_conneection($link), $sql);
	}
	function mysql_insert_id($link = null) {
		return mysqli_insert_id(mysql_adapter_get_conneection($link));
	}
	function mysql_num_rows($result) {
		return mysqli_num_rows($result);
	}
	function mysql_affected_rows($link = null) {
		return mysqli_affected_rows(mysql_adapter_get_conneection($link));
	}
	function mysql_data_seek($result, $offset = 0) {
		return mysqli_data_seek($result, (int)$offset);
	}
	function mysql_real_escape_string($string, $link = null) {
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
	 * Returns the specified field by offset, column name, or table.column
	 * In the case of table.column, mysqli_fetch_field() must be called
	 * to loop over fields until a matching tablename and columnname are found.
	 * 
	 * @param mysqli_result $result 
	 * @param int $row 
	 * @param int $field 
	 * @return string|false
	 */
	function mysql_result($result, $row, $field = 0) {
		// First advance to the correct row
		if (mysqli_data_seek($result, $row)) {
			// Loop over fields until the match is found

		}
		else return false;

		// Presence of a . in $field indicates tablename.fieldname
		// If it contains a dot and isn't backtick-quoted
		// since table names cannot contain dots...
		$field = trim($field);
		if (preg_match('/^[^.]+\..+$/', $field) && !preg_match('/^`[^`]+\.[^`]+`$/', $field)) {
			// Everything up to the first dot _must_ be the table, since it can't have a dot
			list($table, $column) = explode('.', $field, 2);
			// Remove backticks
			$table = trim($table, '`');
			$column = trim($column, '`');

			$numfields = mysqli_num_fields($result);
			$i = 0;
			while ($i < $numfields) {
				mysqli_field_seek($result, $i);
				$finfo = mysqli_fetch_field($result);
				if ($table == $finfo->table && $column == $finfo->name) {
					$row = mysqli_fetch_assoc($result);
					return $row[$finfo->name];
				}
				$i++;
			}
			// End of loop, return false
			return false;
		}
		// No dot, so it's safe to just retrieve the column by trimming backticks
		// and doing a MYSQLI_BOTH fetch
		else {
			$field = trim($field, '`');
			$row = mysqli_fetch_array($result, MYSQLI_BOTH);
			return isset($row[$field]) ? $row[$field] : false;
		}
		return false;
	}

	/**
	 * Returns most recent error - if it was issued by mysqli_connect()
	 * the connection error will be returned, otherwise the most recent
	 * error for $link will be returned
	 * 
	 * @param MySQLi $link 
	 * @return string
	 */
	function mysql_error($link = null) {
		$err = null;
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
	function mysql_errno($link = null) {
		$err = null;
		$err = mysqli_connect_errno();
		if (!empty($err)) {
			$err = mysqli_connect_errno();
		}
		else {
			$err = mysqli_errno(mysql_adapter_get_conneection($link));
		}
		return $err;
	}

	function mysql_close($link = null) {
		return mysqli_close(mysql_adapter_get_conneection($link));
	}

	function mysql_free_result($result) {
		return mysqli_free_result($result);
	}

	function mysql_set_charset($charset, $link = null) {
		return mysqli_set_charset(mysql_adapter_get_conneection($link), $charset);
	}

	function mysql_client_encoding($link = null) {
		return mysqli_character_set_name(mysql_adapter_get_conneection($link));
	}
	
	function mysql_get_server_info($link = null) {
		return mysqli_get_server_info(mysql_adapter_get_conneection($link));
	}
	function mysql_num_fields($result) {
		return mysqli_num_fields($result);
	}
	function mysql_field_name($result, $field_offset) {
		$col = mysqli_fetch_field_direct($result, $field_offset);
		if (is_object($col)) {
			return $col->name;
		}
		else return false;
	}
	function mysql_field_len($result, $field_offset) {
		$col = mysqli_fetch_field_direct($result, $field_offset);
		if (is_object($col)) {
			return $col->length;
		}
		else return false;
	}
}
// vim: set ft=php ts=2 sw=2 sts=2 noexpandtab:
?>
