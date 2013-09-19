<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

include "pocRow.php";
include "pocLog.php";
pocLog::create("start", "just included");
include "pocTag.php";
include "pocPDO.php";
include "pocEnv.php";
include "pocRecord.php";
include "poc.php";
include "pocAttribute.php";
include "pocNavi.php";
include "pocPath.php";
include "pocDir.php";
include "pocRun.php";
include "pocConfig.php";

?>