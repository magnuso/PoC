<?PHP

/*******************************************************************************

Copyright (c) 2013, Marcus Grundschok
All rights reserved. Lizenziert unter EUPL V. 1.1

marcus@grundschok.de
http://www.poc-online.net/

*******************************************************************************/

ini_set('variables_order', 'EGPCS');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

set_include_path('poc');

include 'pocInclude.php';

pocRun::main();

?>