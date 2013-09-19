<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

/*******************************************************************************

Modify this file to meet your configuration, but actually it does it's job
automatically.

Configure poc in:    poc/pocConfig.php

You may want to leave this file away and call    poc.php    directly or
even rename    poc.php    to    index.php    Do it. No problem. But
keep in mind it's a good idea to have a session started, before visiting poc.

*******************************************************************************/

ini_set("variables_order", "EGPCS");

define("_POC_SCRIPT_", "poc.php");

session_start();

$path = explode("/", $_SERVER["SCRIPT_NAME"]);
array_pop($path);
array_push($path, _POC_SCRIPT_);

header("Location: " . strtolower(array_shift(explode("/", $_SERVER["SERVER_PROTOCOL"])))
      . "://" . $_SERVER["HTTP_HOST"] . implode("/", $path)); # auto redirect

#header("Location: http://www.yourdomain.com/poc.php/home/public")); # manu redirect

exit();

?>