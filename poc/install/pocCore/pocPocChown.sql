/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocChown (
    IN inId BIGINT,
    IN inUser VARCHAR(64),
    IN inGroup VARCHAR(64),
    IN deep INT)
BEGIN
  DECLARE inUserId, inGroupId BIGINT DEFAULT 0;
  DECLARE n, priv, mode INT DEFAULT 0;
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocChown' AS content;
    --
    CALL pocTempTablesReset;
    -- check
    IF inId = 0 THEN
      SELECT 406 AS id, 'Not Acceptable' AS name, 'pocPocChown' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(inId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocChown from' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- check inUser
    IF STRCMP(inUser, '') != 0 THEN
      SELECT COUNT(*), (@pocAdmin OR id = @pocUserId) + 0 FROM pocUser AS tu WHERE tu.name = inUser INTO n, priv;
      IF n = 0 THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocChown userId' AS content;
        LEAVE bodyOfProc;
      END IF;
      IF priv = 0 THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocChown userId' AS content;
        LEAVE bodyOfProc;
      END IF;
      SET mode = mode + 1;
    END IF;
    -- check inGroup
    IF inGroup > 0 THEN
      SELECT COUNT(tg.id), (@pocAdmin OR tug.userId) + 0
        FROM pocGroup AS tg
        LEFT JOIN pocUser2Group AS tug ON tug.groupId = tg.id AND tug.userId = @pocUserId
        WHERE tg.id = inGroup INTO n, priv;
      IF n = 0 THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocChown groupId' AS content;
        LEAVE bodyOfProc;
      END IF;
      IF priv = 0 THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocChown groupId' AS content;
        LEAVE bodyOfProc;
      END IF;
      SET mode = mode + 2;
    END IF;
    -- tree
    INSERT INTO pocTempSelect (id, sel, hit) VALUES (inId, n, 1);
    IF deep THEN
      INSERT INTO pocTempIds (tempId) VALUES (inId);
      WHILE ROW_COUNT() > 0 DO
        SET n = n + 1;
        INSERT INTO pocTempSelect (id, sel, hit)
          SELECT tp.id, n, (@pocAdmin OR tp.userId = @pocUserId) + 0
            FROM pocTempIds AS tid
            JOIN pocPoc AS tp ON tp.parentId = tid.tempId;
        DELETE FROM pocTempIds;
        INSERT INTO pocTempIds (tempId) SELECT id FROM pocTempSelect WHERE sel = n;
      END WHILE;
      DELETE FROM pocTempIds;
    END IF;
    -- check tree
    IF NOT @pocAdmin THEN
      SELECT COUNT(*) FROM pocTempSelect WHERE hit = 0 INTO n;
      IF n > 0 THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocChown' AS content;
        LEAVE bodyOfProc;
      END IF;
    END IF;
    -- finally
    IF mode = 1 THEN
      UPDATE pocPoc SET userId = inUser WHERE id IN (SELECT id FROM pocTempSelect);
    ELSEIF mode = 2 THEN
      UPDATE pocPoc SET groupId = inGroup WHERE id IN (SELECT id FROM pocTempSelect);
    ELSEIF mode = 3 THEN
      UPDATE pocPoc SET userId = inUser, groupId = inGroup WHERE id IN (SELECT id FROM pocTempSelect);
    END IF;
  END bodyOfProc;
  DELETE FROM pocTempSelect;
END;
