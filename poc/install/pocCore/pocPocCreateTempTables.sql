/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocCreateTempTables ()
BEGIN
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocCreateTempTables' AS content;
    --
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempSelect (id BIGINT, sel INT, hit INT, path TEXT);
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempIds (tempId BIGINT, path TEXT);
    DELETE FROM pocTempSelect;
    DELETE FROM pocTempIds;
  END bodyOfProc;
END;
