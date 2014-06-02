/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocAttributeIntDelete (
    IN inId BIGINT)
BEGIN
  DECLARE n INT DEFAULT 0;
  DECLARE pocId BIGINT DEFAULT 0;
  bodyOfProc: BEGIN
    -- check
    SELECT COUNT(id), creditId FROM pocAttributeInt WHERE id = inId INTO n, pocId;
    IF n = 0 THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeIntDelete' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(pocId) IS NULL THEN
      SELECT 403 AS id, 'Forbidden' AS name, 'pocAttributeIntDelete' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(pocId, 'delete') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocAttributeIntDelete' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- finally
    DELETE FROM pocAttributeInt WHERE id = inId;
  END bodyOfProc;
END;
