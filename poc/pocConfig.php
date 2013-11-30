<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

define("_POC_HTML_HEADER_", "Content-Type: text/html; charset=utf-8");
define("_POC_MB_ENCODING_", "UTF-8");

if (!pocEnv::started()) {

  pocEnv::$env = $_SERVER; # or $_ENV...
#  pocEnv::$env = $_ENV; # ... check phpinfo();
  pocEnv::$request = $_REQUEST;

#  pocEnv::$fixPath = TRUE; # in case $_ENV["PATH_INFO"] doesn't work ... check phpinfo();

  pocEnv::$session["dbName"] = "record";
  pocEnv::$session["dbHost"] = "localhost";
  pocEnv::$session["dbPort"] = "";
  pocEnv::$session["dbUser"] = "record";
  pocEnv::$session["dbPassword"] = "UVn4r3YJV6zhzvvV";
  pocEnv::$session["dbOptions"] = array(); # NO, NO, NEVER EVER USE A PERSISTANT CONNECTION!!!!!!!!

  pocEnv::$session["sessionExpires"] = 1200; # 1200 sec = 20 min
  pocEnv::$session["cookieExpires"] = 259200; # 86400 sec = 24 h, 604800 sec = 1 week

  pocEnv::$session["pocPDOClass"] = "pocMySqlPDO";

  pocEnv::$env["pocErrorPage"] = "www/error";
  pocEnv::$env["pocDateTimeFormat"] = "d.m.Y  H:i";
  pocEnv::$env["pocDateFormat"] = "d.m.Y";
  pocEnv::$env["pocTimeFormat"] = "H:i:s";

}

?>