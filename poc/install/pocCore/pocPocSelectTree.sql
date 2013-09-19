/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocSelectTree (
    IN inId BIGINT,
    IN resultMode INT,
    IN pocMode INT,
    IN likeName VARCHAR(64),
    IN likeContent TEXT)
BEGIN
  DECLARE n INT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  bodyOfProc: BEGIN
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempSelect (id BIGINT, sel INT, hit INT, path TEXT);
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempIds (tempId BIGINT, path TEXT);
    --
    IF inId > 0 THEN
      SET path = pocPocPathFromId(inId);
      IF path IS NULL THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocSelect' AS content;
        LEAVE bodyOfProc;
      END IF;
      IF NOT pocPocCheckPriv(inId, 'select') THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocSelect' AS content;
        LEAVE bodyOfProc;
      END IF;
      SET path = CONCAT(path, '/');
    END IF;
    --
    DELETE FROM pocTempSelect;
    DELETE FROM pocTempIds;
    INSERT INTO pocTempIds (tempId, path) VALUES (inId, path);
    WHILE ROW_COUNT() > 0 DO
      SET n = n + 1;
      INSERT INTO pocTempSelect (id, sel, hit, path)
        SELECT tp.id, n, (tp.name LIKE(likeName) OR tp.content LIKE(likeContent)) + 0, CONCAT(tid.path, tp.name)
          FROM pocTempIds AS tid
          JOIN pocPoc AS tp ON tp.parentId = tid.tempId
          WHERE pocPocCheckPriv(tp.id, 'open') AND (pocMode = 0 OR pocMode & tp.mode);
      DELETE FROM pocTempIds;
      INSERT INTO pocTempIds (tempId, path) SELECT tsel.id, CONCAT(tsel.path, '/')
        FROM pocTempSelect AS tsel
        JOIN pocPoc AS tp ON tp.id = tsel.id
        WHERE tsel.sel = n AND pocPocCheckPriv(tp.id, 'select');
    END WHILE;
    DELETE FROM pocTempIds;
    DELETE FROM pocTempSelect WHERE hit = 0;
    SELECT 'pocCountSelect' AS className, COUNT(*) AS count FROM pocTempSelect;
    --
    IF resultMode = 0 THEN
      CALL pocPocCreatePocs;
    ELSEIF resultMode = 1 THEN
      CALL pocPocCreateResults('');
    END IF;
  END bodyOfProc;
END;
