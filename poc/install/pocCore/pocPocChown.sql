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
  DECLARE n, priv, mode INT DEFAULT 0;
  bodyOfProc: BEGIN
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempSelect (id BIGINT, sel INT, hit INT, path TEXT);
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempIds (tempId BIGINT, path TEXT);
    DELETE FROM pocTempSelect;
    DELETE FROM pocTempIds;
    SELECT 'pocCountSelect' AS className, 0 AS count;
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
    IF inUser > 0 THEN
      SELECT COUNT(*), (@pocAdmin OR id = @pocUserId) + 0 FROM pocUser WHERE id = inUser INTO n, priv;
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
