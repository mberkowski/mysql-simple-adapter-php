<?php
require_once(appdir . 'mysql_simple_adapter.php');

class Test extends \PHPUnit_Framework_TestCase
{
	/**
	 * Create databases, tables
	 * 
	 * 
	 * @access protected
	 * @return void
	 */
	static public function setUpBeforeClass() {
		// Why not real fixtures???
		// Well, eventually, sorry.
		mysql_simple_adapter_db_cleanup();

		$conn = mysqli_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		if (!empty(mysqli_connect_errno())) {
			throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		}
		$db1_create_sql = "CREATE DATABASE " . $GLOBALS['DBNAME_DB1'];
		$db2_create_sql = "CREATE DATABASE " . $GLOBALS['DBNAME_DB2'];
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
		mysqli_select_db($conn, $GLOBALS['DBNAME_DB1']);
		foreach (array($db1_table_sql, $db1_insert_sql) as $query) {
			$res = mysqli_query($conn, $query);
			if (!$res) {
				throw new Exception('Failed to execute setup query: ' . mysqli_error($conn) . "($query)");
			}
		}
		mysqli_select_db($conn, $GLOBALS['DBNAME_DB2']);
		foreach (array($db2_table_sql, $db2_insert_sql) as $query) {
			$res = mysqli_query($conn, $query);
			if (!$res) {
				throw new Exception('Failed to execute setup query: ' . mysqli_error($conn) . "($query)");
			}
		}

		// And ditch the init connection
		mysqli_close($conn);
	}
	static public function tearDownAfterClass() {
		$conn = mysqli_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		if (!empty(mysqli_connect_errno())) {
			throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		}
		foreach (array($GLOBALS['DBNAME_DB1'], $GLOBALS['DBNAME_DB2']) as $dbname) {
			$res = mysqli_query($conn, "DROP DATABASE " . $dbname);
		}
	}
	public function tearDown() {
	}

	/**
	 * Establish a connection, verify it has written to $GLOBALS['mysql_simple_adapter_global_link']
	 * and that it is an instance of mysqli
	 */
	public function testConnectDefaultConnection() {
		$conn = mysql_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		$this->assertInstanceOf('mysqli', $conn);
		$this->assertEmpty($conn->error);
		$this->assertInstanceOf('mysqli', $GLOBALS['mysql_simple_adapter_global_link']);

		// Select a database
		$ret = mysql_select_db($GLOBALS['DBNAME_DB1']);
		$this->assertTrue($ret);
		// Verify the current database is what's expected
		$res = $conn->query('SELECT DATABASE()');
		$row = $res->fetch_array(MYSQLI_NUM);
		$this->assertEquals($GLOBALS['DBNAME_DB1'], $row[0]);
	}
	
	/**
	 * @depends testConnectDefaultConnection
	 */
	public function testConnectOtherReuse() {
		$conn = mysql_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		
		// Verify it is a mysqli object and is the same as the global one
		$this->assertInstanceOf('mysqli', $conn);
		$this->assertTrue($conn === $GLOBALS['mysql_simple_adapter_global_link']);
	}
	/**
	 * @depends testConnectDefaultConnection
	 */
	public function testConnectOtherConnectionNew() {
		$conn = mysql_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS'], TRUE);
		
		// Verify it is a mysqli object and isn't the same as the global one
		$this->assertInstanceOf('mysqli', $conn);
		$this->assertFalse($conn === $GLOBALS['mysql_simple_adapter_global_link']);	

		// Will need this later...(
		$GLOBALS['mysql_simple_adapter_other_link'] = $conn;
	}
	public function testConnectErrors() {
		$badconn = @mysql_connect($GLOBALS['DBHOST'], 'baduser', 'badpass', TRUE);
		$this->assertNotEmpty(mysql_errno($badconn));
		$this->assertNotEmpty(mysql_error($badconn));
	}
	/**
	 * Verify query without specifying link
	 * 
	 * @depends testConnectDefaultConnection
	 */
	public function testQueryDefaultConnection() {
		mysql_select_db($GLOBALS['DBNAME_DB1']);
		$res = mysql_query("SELECT id, val FROM db_t1 ORDER BY id ASC");
		// Advance to second row, auto-increment is 2, value is v2
		$row = mysql_fetch_assoc($res);
		$row = mysql_fetch_assoc($res);
		$this->assertEquals('v2', $row['val']);
	}
	/**
	 * Verify query by specifying link (other connection)
	 * 
	 * @access public
	 * @return bool
	 */
	public function testQueryOtherConnection() {
		$conn = $GLOBALS['mysql_simple_adapter_other_link'];
		mysql_select_db($GLOBALS['DBNAME_DB2'], $conn);
		$res = mysql_query("SELECT id, val FROM db_t2 ORDER BY id ASC", $conn);
		// Advance to second row, auto-increment is 2, value is 2v
		$row = mysql_fetch_assoc($res);
		$row = mysql_fetch_assoc($res);
		$this->assertEquals('2v', $row['val']);
	}
}
// vim: set ts=2 sw=2 sts=2 noexpandtab:
?>
