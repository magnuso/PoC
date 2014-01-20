/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectInsertOrderBy (
    IN inOrderBy TEXT)
BEGIN
  DECLARE spacer VARCHAR(32) DEFAULT '';
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectInsertOrderBy' AS content;
  --
  IF LENGTH(@pocSelectOrderBy) > 0 THEN
    SET spacer = ',\n    ';
  END IF;
  SELECT CONCAT(@pocSelectOrderBy, spacer, inOrderBy) INTO @pocSelectOrderBy;
END;
