/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectInsertColumn (
    IN inColumn TEXT)
BEGIN
  DECLARE spacer VARCHAR(32) DEFAULT '';
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectInsertColumn' AS content;
  --
  IF LENGTH(@pocSelectColumns) > 0 THEN
    SET spacer = ',\n    ';
  END IF;
  SELECT CONCAT(@pocSelectColumns, spacer, inColumn) INTO @pocSelectColumns;
END;
