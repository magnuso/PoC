/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectInsertGroupBy (
    IN inGroupBy TEXT)
BEGIN
  DECLARE spacer VARCHAR(32) DEFAULT '';
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectInsertGroupBy' AS content;
  --
  IF LENGTH(@pocSelectGroupBy) > 0 THEN
    SET spacer = ',\n    ';
  END IF;
  SELECT CONCAT(@pocSelectGroupBy, spacer, inGroupBy) INTO @pocSelectGroupBy;
END;
