/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocOpenById (
    IN inId BIGINT)
BEGIN
  DECLARE n BIGINT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocOpenByName' AS content;
    --
    CALL pocTempTablesReset;
    --
    IF inId > 0 THEN
      SET path = pocPocPathFromId(inId);
      IF path IS NULL THEN
        SELECT 403 AS id, 'Forbidden' AS name, 'pocPocOpenById' AS content;
        LEAVE bodyOfProc;
      END IF;
      INSERT INTO pocTempSelect (id, sel, hit, path) VALUES (inId, 1, 1, path);
      CALL pocPocCreatePocs;
      DELETE FROM pocTempSelect;
    END IF;
  END bodyOfProc;
END;