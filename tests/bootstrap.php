<?php
define('appdir', __DIR__ . '/../');

// Drop register_shutdown_function(function() {
function mysql_simple_adapter_db_cleanup() {
	$conn = mysqli_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
	if (!empty(mysqli_connect_errno())) {
		throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
	}
	foreach (array($GLOBALS['DBNAME_DB1'], $GLOBALS['DBNAME_DB2']) as $dbname) {
		$res = mysqli_query($conn, "DROP DATABASE IF EXISTS " . $dbname);
	}
}

register_shutdown_function('mysql_simple_adapter_db_cleanup');
