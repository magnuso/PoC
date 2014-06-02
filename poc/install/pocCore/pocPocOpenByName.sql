/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocOpenByName (
    IN inId BIGINT,
    IN inName VARCHAR(64))
BEGIN
  DECLARE n INT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocOpenByName' AS content;
    --
    CALL pocTempTablesReset;
    --
    SELECT COUNT(id), id FROM pocPoc WHERE parentId = inId AND name = inName INTO n, inId;
    IF n < 1 THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocOpenByName' AS content;
      LEAVE bodyOfProc;
    END IF;
    SET path = pocPocPathFromId(inId);
    IF path IS NULL THEN
      SELECT 403 AS id, 'Forbidden' AS name, 'pocPocOpenByName' AS content;
      LEAVE bodyOfProc;
    END IF;
    INSERT INTO pocTempSelect (id, sel, hit, path) SELECT inId, 1, 1, path;
    CALL pocPocCreatePocs();
    DELETE FROM pocTempSelect;
  END bodyOfProc;
END;