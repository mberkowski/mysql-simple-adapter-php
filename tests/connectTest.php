<?php
// require_once($appdir . 'mysql_simple_adapter.php');

class Test extends \PHPUnit_Framework_TestCase
{
	/**
	 * Create databases, tables
	 * 
	 * @access protected
	 * @return void
	 */
	protected function setUp() {
		$creds = $GLOBALS['mysql_simple_adapter_phpunit_db'];
		$conn = mysqli_connect($creds['dbhost'], $creds['dbuser'], $creds['dbpass']);
		if (!empty(mysqli_connect_errno())) {
			throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		}
		$db1_create_sql = "CREATE DATABASE " . $creds['dbname_db1'];
		$db2_create_sql = "CREATE DATABASE " . $creds['dbname_db2'];
		$db1_table_sql = "CREATE TABLE db_t1 (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, val VARCHAR(4) NOT NULL)";
		$db2_table_sql = "CREATE TABLE db_t2 (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, val VARCHAR(4) NOT NULL)";
		$db1_insert_sql = "INSERT INTO db_t1 (val) VALUES ('v1'),('v2'),('v3'),('v4')";
		$db2_insert_sql = "INSERT INTO db_t2 (val) VALUES ('1v'),('2v'),('3v'),('4v')";

		foreach (array($db1_create_sql, $db2_create_sql) as $query) {
			$res = mysqli_query($conn, $query);
			if (!$res) {
				throw new Exception('Failed to execute setup query: ' . mysqli_error($conn) . "($query)");
			}
		}
		mysqli_select_db($conn, $creds['dbname_db1']);
		foreach (array($db1_table_sql, $db1_insert_sql) as $query) {
			$res = mysqli_query($conn, $query);
			if (!$res) {
				throw new Exception('Failed to execute setup query: ' . mysqli_error($conn) . "($query)");
			}
		}
		mysqli_select_db($conn, $creds['dbname_db2']);
		foreach (array($db2_table_sql, $db2_insert_sql) as $query) {
			$res = mysqli_query($conn, $query);
			if (!$res) {
				throw new Exception('Failed to execute setup query: ' . mysqli_error($conn) . "($query)");
			}
		}

		// And ditch the init connection
		mysqli_close($conn);
	}
	protected function tearDown() {
		$creds = $GLOBALS['mysql_simple_adapter_phpunit_db'];
		$conn = mysqli_connect($creds['dbhost'], $creds['dbuser'], $creds['dbpass']);
		if (!empty(mysqli_connect_errno())) {
			throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		}
		foreach (array($creds['dbname_db1'], $creds['dbname_db2']) as $dbname) {
			$res = mysqli_query($conn, "DROP DATABASE " . $dbname);
		}
	}

	public function testConnectDefaultConnection() {
	
	}
	public function testConnectOtherConnection() {
	
	}
	public function testQueryDefaultConnection() {
	
	}
	public function testQueryOtherConnection() {
		// code...
	}
}
// vim: set ts=2 sw=2 sts=2 noexpandtab:
?>
