# MySQL Simple Adapter

## Description
The MySQL Simple Adapter is a function definition shim intended as a basic,
but incomplete adapter between the old deprecated `ext/mysql` extension and
`ext/mysqli`. It supplies many commonly used `mysql_*()` functions and aims to
emulate their behavior exactly or as closely as possible with equivalent
features of MySQLi.

Use of the adapter **is not recommended**. Instead, it is recommended to update
code to make use of PDO or MySQLi via prepared statements.

I can't stress it enough - this should only be used as a stopgap or last resort.
It depends on setting up a global state to mimic `ext/mysql` behavior that I
don't care for :( It is no more secure than using `ext/mysql`, since it is only
capable of handling queries constructed via string manipulation. It offers
_none_ of the benefits of [MySQLi prepared
statements](http://php.net/manual/en/mysqli.prepare.php). All it does is
sidestep the deprecation of `ext/mysql`.

## What is implemented

This adapter only implements a subset of `mysql_*()` functions. Basically, if I
have needed it, it's been implemented so the more exotic functions like
`mysql_get_proto_info()` are probably not present.

### Currently implemented functions

    mysql_connect()
    mysql_select_db()
    mysql_query()
    mysql_unbuffered_query()
    mysql_insert_id()
    mysql_num_rows()
    mysql_affected_rows()
    mysql_data_seek()
    mysql_real_escape_string()
    mysql_escape_string()
    mysql_fetch_array()
    mysql_fetch_assoc()
    mysql_result()
    mysql_error()
    mysql_errno()
    mysql_close()
    mysql_free_result()
    mysql_set_charset()
    mysql_client_encoding()
    mysql_get_server_info()
    mysql_stat()
    mysql_field_count()
    mysql_field_name()
    mysql_field_len()


### Is this sufficient for your application?

To find out what your application is currently using, you may run the following
from a Unix command line:

    find /path/to/your/app -name "*.php" -exec egrep -o "mysql_\w+" {} \; | sort | uniq

If you have PHP files with extensions other than `.php`, add them with `-o -name
"*.ext"` as many times as necessary, as in:

    find . -name "*.php" -o -name "*.inc" -o -name "*.phwhatever" -exec egrep -o "mysql_\w+" {} \; | sort | uniq

Called from the root directory of your old `ext/mysql` project, it will return a list of functions.

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
    mysql_set_charset
    mysql_client_encoding


## Usage
### By simple file inclusion:
Include `mysql_simple_adapter.php` into your code. It expects that `ext/mysql`
is not present or enabled, and that `ext/mysqli` _is_ present and enabled. It
will exit with a fatal error if either of those conditions isn't met.

    require_once('mysql_simple_adapter.php');

### Composer
Add a `repositories` key to point to the GitHub repo. Composer will load this
file via its `autoload:files` facility and you don't have to do anything else.

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:mberkowski/mysql-simple-adapter"
    }
],
"require": {
    "mjb/mysql-simple-adapter": "*"
}
```

Since MySQLi requires a link resource object to be passed as the first parameter
to most functions, whereas `mysql_*()` would use the most recently opened link
resource if none was supplied, the MySQL Simple Adapter expects by convention
that the default connection established is stored into a global scope variable
whose name begins with `mysql_simple_adapter_global_link_` followed by a hash
value for uniqueness and obfuscation (you should not mess with it).
If it is not yet defined when the first connection is established by the
first call of `mysql_connect()`, it will be assigned then. Subsequent new
connections opened will return that global link if the connection parameters are
identical, and if not a new connection will be established and the global link
replaced with the new connection so subsequent connections use the last created
link.


Then many common `mysql_*()` functions can be called as normal:

```php
$link = mysql_connect('host', 'user', 'pass');
if ($link) {
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
```

## Known Issues
- The `mysql_connect()` wrapper is not smart enough to know or care if it is
called inside function scope. It always assumes global scope and will set
that global variable if not already set.

- `mysql_connect()` is supposed to ignore its `$new_link` and `$client_flags`
parameters while SQL Safe Mode is enabled. This is currently not supported

- Presently the `$client_flags` parameter to `mysql_connect()` is implemented
  but
not yet well tested.

- Reliance on PHP functions like `extension_loaded()` will not work. If your
  application expects to dynamically determine if ext/mysql was present via
  `extension_loaded()`, you should switch to something like
  `function_exists('mysql_query')` instead.


## Alternatives
### MySQLi Converter
Oracle provides [this MySQLi converter
script](https://wikis.oracle.com/display/mysql/Converting+to+MySQLi) to modify
code

### Others?
