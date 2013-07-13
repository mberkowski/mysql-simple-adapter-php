#MySQL Simple Adapter

##Description
The MySQL Simple Adapter is a function definition shim intended as a basic,
probably incomplete adapter between the old deprecated `ext/mysql` extension and
`ext/mysqli`.

Use of the adapter **is not recommended**. Instead, it is recommended to update
code to make use of PDO or MySQLi via prepared statements.

I can't stress it enough - this should only be used as a stopgap or last resort.
It depends on setting up a global state to mimic `ext/mysql` behavior that I
don't care for :( It is no more secure than using `ext/mysql`, since it is only
capable of handling queries constructed via string manipulation. It offers
_none_ of the benefits of [MySQLi prepared
statements](http://php.net/manual/en/mysqli.prepare.php). All it does is
sidestep the deprecation of `ext/mysql`.

##What is implemented

This adapter only implements a subset of `mysql_*()` functions. Basically, if I
have needed it, it's been implemented so the more exotic functions like
`mysql_get_proto_info()` are probably not present.

###Currently implemented functions

    mysql_connect()
    mysql_select_db()
    mysql_query()
    mysql_insert_id()
    mysql_num_rows()
    mysql_data_seek()
    mysql_real_escape_string()
    mysql_escape_string()
    mysql_fetch_array()
    mysql_fetch_assoc()
    mysql_error()
    mysql_errno()
    mysql_close()
    mysql_free_result()
    mysql_set_charset()
    mysql_client_encoding()
    mysql_get_server_info()


###Is this sufficient for your application?

To find out what your application is currently using, you may run the following
from a Unix command line:

    find /path/to/your/app -name "*.php" -exec egrep -o "mysql_\w+" {} \; | sort | uniq

If you have PHP files with extensions other than `.php`, add them with `-o -name
"*.ext"` as many times as necessary, as in:

    find . -name "*.php" -o -name "*.inc" -o -name "*.phwhatever"

    # Prints a list of functions like:
    mysql_connect
    mysql_data_seek
    mysql_errno
    mysql_error
    mysql_fetch_array
    mysql_insert_id
    mysql_num_rows
    mysql_query
    mysql_select_db
    mysql_set_charset()
    mysql_client_encoding()


##Usage
Include `mysql_simple_adapter.php` into your code. It expects that `ext/mysql`
is not present or enabled, and that `ext/mysqli` _is_ present and enabled. It
will exit with a fatal error if either of those conditions isn't met.

    require_once('mysql_simple_adapter.php');

Since MySQLi requires a link resource object to be passed as the first parameter
to most functions, whereas `mysql_*()` would use the most recently opened link
resource if none was supplied, the MySQL Simple Adapter expects by convention
that the default connection established is stored into a global scope variable
`$mysql_simple_adapter_global_link`.  If it is not yet defined when the first
connection is established by the frist call of `mysql_connect()`, it will be
assigned then. It is recommended to save the connection into that variable:

    $mysql_simple_adapter_global_link = mysql_connect('host', 'user', 'pass');
    // Or more explicitly:
    $GLOBALS['mysql_simple_adapter_global_link'] = mysql_connect('host', 'user', 'pass');

Then many common `mysql_*()` functions can be called as normal:

    $mysql_simple_adapter_global_link = mysql_connect('host', 'user', 'pass');
    if ($mysql_simple_adapter) {
      $result = mysql_query('SELECT * FROM yourtable');
      if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
          print_r($row);
        }
      }
    }
    else {
      echo mysql_connect_error();
    }

Note: the `mysql_connect()` wrapper is not smart enough to know or care if it is
called inside function scope. It always assumes global scope and will set
that global variable if not already set.


##Alternatives
###MySQLi Converter
Oracle provides [this MySQLi converter
script](https://wikis.oracle.com/display/mysql/Converting+to+MySQLi) to modify
code

###Others?
