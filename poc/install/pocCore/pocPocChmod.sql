/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocChmod (
    IN inId BIGINT,
    IN inUserPrivs INT,
    IN inGroupPrivs INT,
    IN inOtherPrivs INT,
    IN deep INT)
BEGIN
  DECLARE n, priv, mode, done INT DEFAULT 0;
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocChmod' AS content;
    --
    CALL pocPocCreateTempTables;
    SELECT 'pocCountSelect' AS className, 0 AS count;
    -- check
    IF inId = 0 THEN
      SELECT 406 AS id, 'Not Acceptable' AS name, 'pocPocChmod' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(inId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocChmod' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- check privs
    SELECT COUNT(tp.id), (@pocAdmin OR tp.userId = @pocUserId) + (2 * COUNT(tug.groupId))
      FROM pocPoc AS tp
      LEFT JOIN pocUser2Group AS tug ON tug.groupId = tp.id AND tug.userId = @pocUserId
      WHERE tp.id = inId
      INTO n, mode;
    IF n = 0 THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocChmod userId' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- tree
    INSERT INTO pocTempSelect (id, sel, hit) VALUES (inId, n, mode);
    IF deep THEN
      INSERT INTO pocTempIds (tempId) VALUES (inId);
      WHILE ROW_COUNT() > 0 DO
        SET n = n + 1;
        INSERT INTO pocTempSelect (id, sel, hit)
          SELECT tp.id, n, (@pocAdmin OR tp.userId = @pocUserId) + (2 * (tug.groupId IS NOT NULL))
            FROM pocTempIds AS tti
            JOIN pocPoc AS tp ON tp.parentId = tti.tempId
            LEFT JOIN pocUser2Group AS tug ON tug.groupId = tp.groupId AND tug.userId = @pocUserId;
        DELETE FROM pocTempIds;
        INSERT INTO pocTempIds (tempId) SELECT id FROM pocTempSelect WHERE sel = n;
      END WHILE;
      DELETE FROM pocTempIds;
    END IF;
    -- check tree
    IF NOT @pocAdmin THEN
      SELECT COUNT(*) FROM pocTempSelect WHERE hit = 0 INTO n;
      IF n > 0 THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocChmod' AS content;
        LEAVE bodyOfProc;
      END IF;
    END IF;
    -- finally
    bodyOfLoop: BEGIN
      DECLARE cur CURSOR FOR SELECT id, hit FROM pocTempSelect;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN cur;
      theLoop: LOOP
        FETCH cur INTO inId, mode;
        IF done THEN
          LEAVE theLoop;
        END IF;
        IF mode = 2 THEN
          UPDATE pocPoc SET otherPrivs = inOtherPrivs
            WHERE id = inId;
        ELSE
          UPDATE pocPoc SET userPrivs = inUserPrivs, groupPrivs = inGroupPrivs, otherPrivs = inOtherPrivs
            WHERE id = inId;
        END IF;
      END LOOP;
      CLOSE cur;
    END bodyOfLoop;
  END bodyOfProc;
  DELETE FROM pocTempSelect;
END;
