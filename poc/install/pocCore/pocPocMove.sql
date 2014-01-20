/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocMove (
    IN inId BIGINT,
    IN toId BIGINT)
BEGIN
  DECLARE n INT DEFAULT 0;
  DECLARE path TEXT;
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocMove' AS content;
    --
    CALL pocTempTablesReset;
    -- check
    IF inId = 0 THEN
      SELECT 406 AS id, 'Not Acceptable' AS name, 'pocPocMove' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(inId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocMove from' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(inId, 'update') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocMove from' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(toId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocMove to' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(toId, 'insert') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocMove to' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- tree
    INSERT INTO pocTempSelect (id, sel, hit) VALUES (inId, n, 1);
    INSERT INTO pocTempIds (tempId) VALUES (inId);
    WHILE ROW_COUNT() > 0 DO
      SET n = n + 1;
      INSERT INTO pocTempSelect (id, sel, hit)
        SELECT tp.id, n,
            (@pocAdmin OR ((FIND_IN_SET('open', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('open', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('open', tp.groupPrivs)))
              AND (FIND_IN_SET('update', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('update', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('update', tp.groupPrivs))))) + 0
          FROM pocTempIds AS tid
          JOIN pocPoc AS tp ON tp.parentId = tid.tempId
          LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId;
      DELETE FROM pocTempIds;
      INSERT INTO pocTempIds (tempId) SELECT id FROM pocTempSelect WHERE sel = n;
    END WHILE;
    DELETE FROM pocTempIds;
    -- check tree
    IF NOT @pocAdmin THEN
      SELECT COUNT(*) FROM pocTempSelect WHERE hit = 0 INTO n;
      IF n > 0 THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocMove' AS content;
        LEAVE bodyOfProc;
      END IF;
    END IF;
    -- finally
    UPDATE pocPoc SET parentId = toId, modified = UNIX_TIMESTAMP(), modifiedById = @PocUserId WHERE id = inId;
    -- output
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempPath (id BIGINT, path TEXT);
    DELETE FROM pocTempPath WHERE id IN (SELECT id FROM pocTempSelect);
    SET path = pocPocPathFromId(inId);
    DELETE FROM pocTempSelect;
    INSERT INTO pocTempSelect (id, hit) VALUES (inId, 1);
    CALL pocPocCreatePocs;
  END bodyOfProc;
  DELETE FROM pocTempSelect;
END;
