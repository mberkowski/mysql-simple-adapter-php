<?php
require_once(appdir . 'mysql_simple_adapter.php');

class MySQLSimpleAdapterTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Create databases, tables
	 * 
	 * 
	 * @access protected
	 * @return void
	 */
	static public function setUpBeforeClass()
	{
		// Why not real fixtures???
		// Well, eventually, sorry.
		mysql_simple_adapter_db_cleanup();

		$conn = mysqli_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		$connerr = mysqli_connect_errno();
		if (!empty($connerr)) {
			throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		}
		$db1_create_sql = "CREATE DATABASE " . $GLOBALS['DBNAME_DB1'] . " DEFAULT CHARACTER SET latin1";
		$db2_create_sql = "CREATE DATABASE " . $GLOBALS['DBNAME_DB2'] . " DEFAULT CHARACTER SET latin1";
		$db1_table_sql = "CREATE TABLE db_t1 (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, val VARCHAR(4) NOT NULL)";
		$db2_table_sql = "CREATE TABLE db_t2 (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, val VARCHAR(4) NOT NULL)";
		$db1_insert_sql = "INSERT INTO db_t1 (val) VALUES ('v1'),('v2'),('v3'),('v4')";
		$db2_insert_sql = "INSERT INTO db_t2 (val) VALUES ('1v'),('2v'),('3v'),('4v'),('5v')";

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
	static public function tearDownAfterClass()
	{
		$conn = mysqli_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		$connerr = mysqli_connect_errno();
		if (!empty($connerr)) {
			throw new Exception('Failed to setup MySQL Simple Adapter databases: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		}
		foreach (array($GLOBALS['DBNAME_DB1'], $GLOBALS['DBNAME_DB2']) as $dbname) {
			$res = mysqli_query($conn, "DROP DATABASE " . $dbname);
		}
	}
	public function tearDown()
	{
		// Nothing here...
	}
	public function globalLink()
	{
		return $GLOBALS['mysql_simple_adapter_global_link_' . MYSQL_SIMPLE_ADAPTER_TS_HASH];
	}

	/**
	 * Establish a connection, verify it has written to $this->globalLink()
	 * and that it is an instance of mysqli
	 */
	public function testConnectDefaultConnection()
	{
		// Verify the hash constant was defined
		$this->assertNotEmpty(MYSQL_SIMPLE_ADAPTER_TS_HASH);

		$conn = mysql_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		$this->assertInstanceOf('mysqli', $conn, "mysql_connect() should return an instance of class Mysqli");
		$this->assertEmpty($conn->error, "On successful connection, error property should be empty");
		$this->assertInstanceOf('mysqli', $this->globalLink(), "Global Mysqli instance should have been initialized");

		// Select a database
		$ret = mysql_select_db($GLOBALS['DBNAME_DB1']);
		$this->assertTrue($ret);
		// Verify the current database is what's expected
		$res = $conn->query('SELECT DATABASE()');
		$row = $res->fetch_array(MYSQLI_NUM);
		$this->assertEquals($GLOBALS['DBNAME_DB1'], $row[0], "Database name reported by MySQL should match the supplied string");
	}
	
	/**
	 * @depends testConnectDefaultConnection
	 */
	public function testConnectOtherReuse()
	{
		$conn = mysql_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS']);
		
		// Verify it is a mysqli object and is the same as the global one
		$this->assertInstanceOf('mysqli', $conn, "A valid Mysqli connection should be returned");
		$this->assertTrue($conn === $this->globalLink(), "The returned connection should be the same object reference as the existing global Mysqli");
	}
	/**
	 * @depends testConnectDefaultConnection
	 */
	public function testConnectOtherConnectionNew()
	{
		$conn = mysql_connect($GLOBALS['DBHOST'], $GLOBALS['DBUSER'], $GLOBALS['DBPASS'], TRUE);
		
		// Verify it is a mysqli object and isn't the same as the global one
		$this->assertInstanceOf('mysqli', $conn, "A valid Mysqli connection should be returned");
		$this->assertFalse($conn === $this->globalLink(), "The returned connection should not be the same object reference as the existing global Mysqli");	

		// Will need this later...(
		$GLOBALS['mysql_simple_adapter_other_link'] = $conn;
	}
	public function testConnectErrors()
	{
		$badconn = @mysql_connect($GLOBALS['DBHOST'], 'baduser', 'badpass', TRUE);
		$this->assertNotEmpty(mysql_errno($badconn), "An error code should be returned on a faulty connection");
		$this->assertNotEmpty(mysql_error($badconn), "An error string should be returned on a faulty connection");
	}
	/**
	 * Verify query without specifying link
	 * 
	 * @depends testConnectDefaultConnection
	 */
	public function testQueryDefaultConnection()
	{
		mysql_select_db($GLOBALS['DBNAME_DB1']);

		// Enforce latin1 for testing - mysql_field_len() will give 
		// 3x results for utf8 and we need consistent testing.
		mysql_query("SET NAMES latin1");

		$res = mysql_query("SELECT id, val FROM db_t1 ORDER BY id ASC");

		// db_t1 has 4 fixture rows
		$this->assertEquals(4, mysql_num_rows($res), "Number of rows returned should match the number of fixture rows");

		// Advance to second row, auto-increment is 2, value is v2
		$row = mysql_fetch_assoc($res);
		$row = mysql_fetch_assoc($res);
		$this->assertEquals('v2', $row['val'], "Value fetched should match the expected fixture value");

		// Rewind to first row
		$rew = mysql_data_seek($res, 0);
		$this->assertTrue($rew);
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(1, $row['id'], "Value fetched should match the expected fixture value");

		// Fetch assoc already tested
		// Test numeric & both fetch
		// Default MYSQL_BOTH - advance to id=2
		$row = mysql_fetch_array($res);
		$this->assertEquals(2, $row[0], "Value fetched should match the expected fixture value");
		$this->assertEquals(2, $row['id'], "Value fetched should match the expected fixture value");

		// Specify MYSQL_BOTH - advance to id=2
		mysql_data_seek($res, 1);
		$row = mysql_fetch_array($res, MYSQL_BOTH);
		$this->assertEquals(2, $row[0], "Value fetched should match the expected fixture value");
		$this->assertEquals(2, $row['id'], "Value fetched should match the expected fixture value");

		// Advance to id=3, test MYSQL_NUM
		// assoc key should be empty
		$row = mysql_fetch_array($res, MYSQL_NUM);
		$this->assertEquals(3, $row[0], "Value fetched should match the expected fixture value");
		$this->assertArrayNotHasKey('id', $row, "Fetched array should not have string keys");

		// As MYSQL_ASSOC, should not have numeric key
		mysql_data_seek($res, 2);
		$row = mysql_fetch_array($res, MYSQL_ASSOC);
		$this->assertEquals(3, $row['id'], "Value fetched should match the expected fixture value");
		$this->assertArrayNotHasKey(0, $row, "Fetched array should not have numeric keys");

		// The awful mysql_result() returning the second row, first field
		// No column specified, first one returned
		$field = mysql_result($res, 1);
		$this->assertEquals(2, $field);
		// By offset
		$field = mysql_result($res, 1, 0);
		$this->assertEquals(2, $field);
		// By column
		$field = mysql_result($res, 2, 'val');
		$this->assertEquals('v3', $field);
		// By table.column
		$field = mysql_result($res, 3, 'db_t1.val');
		$this->assertEquals('v4', $field);
		// A non-existent row is false
		$field = mysql_result($res, 999);
		$this->assertFalse($field);

		// Test field counts and field names
		$count = mysql_num_fields($res);
		$this->assertEquals(2, $count, "Field count should match expected value");

		// Second field should be named 'val'
		$fieldname = mysql_field_name($res, 1);
		$this->assertEquals('val', $fieldname, "Field name should match expected value");

		// Second field should have length 4
		$fieldlen = mysql_field_len($res, 1);
		$this->assertEquals(4, $fieldlen, "Field data length should match expected value");

		// Free the result, verify its num_rows property is now null
		$this->assertEquals(4, $res->num_rows);
		mysql_free_result($res);
		$this->assertNull(@$res->num_rows, "Result resource should have been freed, having no rows remaining");
	}
	/**
	 * Verify query by specifying link (other connection)
	 *
	 * @depends testConnectDefaultConnection
	 */
	public function testQueryOtherConnection()
	{
		$conn = $GLOBALS['mysql_simple_adapter_other_link'];
		mysql_select_db($GLOBALS['DBNAME_DB2'], $conn);
		$res = mysql_query("SELECT id, val FROM db_t2 ORDER BY id ASC", $conn);

		// db_t2 has 5 fixture rows
		$this->assertEquals(5, mysql_num_rows($res), "Number of rows returned should match the number of fixture rows");

		// Advance to second row, auto-increment is 2, value is 2v
		$row = mysql_fetch_assoc($res);
		$row = mysql_fetch_assoc($res);
		$this->assertEquals('2v', $row['val'], "Value fetched should match the expected fixture value");
	}
	/**
	 * Test mysql_real_escape_string() and mysql_escape_string()
	 * 
	 * @depends testConnectDefaultConnection
	 */
	public function testEscaping()
	{
		$bad_string = "This string has ' some single quotes ' to escape";
		$escaped_string = mysql_real_escape_string($bad_string);
		$this->assertEquals("This string has \' some single quotes \' to escape", $escaped_string, "Single quotes should be escaped in the test string");

		// mysql_escape_string() should return the same as mysql_real_escape_string()
		// since it just wraps it
		$escaped_string2 = mysql_escape_string($bad_string);
		$this->assertEquals($escaped_string, $escaped_string2, "mysql_escape_string() should return the same value as mysql_escape_string()");
	}
	/**
	 * Test mysql_insert_id()
	 * 
	 * @depends testConnectDefaultConnection
	 */
	public function testInsertId()
	{
		// Set auto_increment ahead to 1000 on first db table
		mysql_query("ALTER TABLE db_t1 AUTO_INCREMENT=1000", $this->globalLink());
		// Insert with default connection (row 1000)
		$res = mysql_query("INSERT INTO db_t1 (id, val) VALUES (NULL, 'v5')");
		$this->assertEquals(1000, mysql_insert_id(), "Inserted row's AUTO_INCREMENT id should match expected value'");

		// And verify 1 affected row
		$affected = mysql_affected_rows($this->globalLink());
		$this->assertEquals(1, $affected, "One affected row should be reported");

		// Set second link to 2000 and verify with link specified
		mysql_query("ALTER TABLE db_t2 AUTO_INCREMENT=2000", $GLOBALS['mysql_simple_adapter_other_link']);
		$res = mysql_query("INSERT INTO db_t2 (id, val) VALUES (NULL, 'v5')", $GLOBALS['mysql_simple_adapter_other_link']);
		$this->assertEquals(2000, mysql_insert_id($GLOBALS['mysql_simple_adapter_other_link']), "Inserted row's AUTO_INCREMENT id should match expected value'");
	}
	/**
	 * @depends testConnectDefaultConnection
	 */
	public function testCharacterSets()
	{
		$charset = mysql_client_encoding($this->globalLink());
		$this->assertNotEmpty($charset, "Connection should report its default charset, non-empty");

		// Set an exotic new character set
		$set = 'koi8r';
		$res = mysql_set_charset($set, $this->globalLink());
		$this->assertTrue($res, "Successful setting of charset should return true");

		// And verify we can get the same value back
		$charset = mysql_client_encoding($this->globalLink());
		$this->assertEquals($set, $charset, "Charset reported by connection should match the expected value");
	}
	/**
	 * @depend testConnectDefaultConnection
	 */
	public function testServerInfo()
	{
		$info = mysql_get_server_info($this->globalLink());
		$this->assertNotEmpty($info, "Server info string should be non-empty");
	}
	/**
	 * Close default connection and specified connection
	 * 
	 * @depends testConnectDefaultConnection
	 */
	public function testCloseConnections()
	{
		// Both connections are still open
		$this->assertInstanceOf('mysqli', $this->globalLink(), "Object should still be a valid Mysqli instance");
		$this->assertInstanceOf('mysqli', $GLOBALS['mysql_simple_adapter_other_link'],  "Object should still be a valid Mysqli instance");
		// Close both of them
		$this->assertTrue(mysql_close(), "Successful connection close should return true");
		$this->assertNull(@$this->globalLink()->server_info, "server_info property should be null after closing connection");

		$this->assertTrue(mysql_close($GLOBALS['mysql_simple_adapter_other_link']), "Successful connection close should return true");
		$this->assertNull(@$GLOBALS['mysql_simple_adapter_other_link']->server_info,  "server_info property should be null after closing connection");
	}
}
// vim: set ts=2 sw=2 sts=2 noexpandtab:
?>
