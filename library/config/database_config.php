<?php
# ****** MASTER DATABASE SERVER NAME ******
# This is the hostname or IP address of the MySQL Database Server.
# If you are unsure of what to put here, leave the default value.
define('MYSQL_HOST', 'localhost');

# ****** MASTER DATABASE SERVER PORT ******
# Specifies the port number to attempt to connect to the MySQL server.
# If you are unsure of what to put here, leave the default value.
define('MYSQL_PORT', '3306');

# ****** MASTER DATABASE USERNAME & PASSWORD ******
# This is the username and password you use to access MySQL.
# These must be obtained through your webhost.
define('MYSQL_USER', 'bttrackers');
define('MYSQL_PASS', 'AQ@f0Ht9ie6ogbpM');

#	****** DATABASE NAME ******
#	This is the name of the MySQL database where your TSUE Script will be located.
#	This must be created by your webhost.
define('MYSQL_DB', 'bttrackers');

#	****** DATABASE SOCKET ******
# Specifies the socket or named pipe that should be used.
# If you are unsure of what to put here, leave this empty.
define('MYSQL_SOCKET', '');

#	****** DATABASE CHARSET ******
# If you need to set the default connection charset because your database
# is using a charset other than latin1, you can set the charset here.
# If you don't set the charset to be the same as your database, you
# may receive collation errors.  Ignore this setting unless you
# are sure you need to use it.
# Example: define('MYSQL_CHARSET', 'utf8');
define('MYSQL_CHARSET', '');