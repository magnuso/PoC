/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocCopy (
    IN inId BIGINT,
    IN toId BIGINT)
BEGIN
  DECLARE n INT DEFAULT 0;
  DECLARE t, theGroupId BIGINT DEFAULT 0;
  bodyOfProc: BEGIN
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempSelect (id BIGINT, sel INT, hit INT, path TEXT);
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempIds (tempId BIGINT, path TEXT);
    DELETE FROM pocTempSelect;
    DELETE FROM pocTempIds;
    SELECT 'pocCountSelect' AS className, 0 AS count;
    -- check
    IF inId = 0 THEN
      SELECT 406 AS id, 'Not Acceptable' AS name, 'pocPocCopy' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(inId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocCopy from' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(toId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocCopy to' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(toId, 'insert') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocCopy' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- tree
    INSERT INTO pocTempSelect (id, sel, hit) VALUES (inId, n, 1);
    INSERT INTO pocTempIds (tempId) VALUES (inId);
    WHILE ROW_COUNT() > 0 DO
      SET n = n + 1;
      INSERT INTO pocTempSelect (id, sel, hit)
        SELECT tp.id, n,
            (@pocAdmin OR ((FIND_IN_SET('open', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('open', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('open', tp.groupPrivs))))) + 0
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
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocCopy' AS content;
        LEAVE bodyOfProc;
      END IF;
    END IF;
    -- groupId
    SELECT COUNT(*), groupId FROM pocPoc WHERE id = toId INTO n, theGroupId;
    SET t = UNIX_TIMESTAMP();
    -- walk poc
    CREATE TEMPORARY TABLE IF NOT EXISTS pocTempOldIds (oldId BIGINT, newId BIGINT);
    DELETE FROM pocTempOldIds;
    walkBlock: BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theId, theParentId BIGINT DEFAULT 0;
      DECLARE theUserPrivs, theGroupPrivs, theOtherPrivs, theMode INT DEFAULT 0;
      DECLARE theName, theTitle VARCHAR(64) DEFAULT '';
      DECLARE theContent TEXT DEFAULT '';
      DECLARE walkCursor CURSOR FOR SELECT tp.id, tp.parentId,
          tp.userPrivs + 0, tp.groupPrivs + 0, tp.otherPrivs + 0, tp.mode + 0,
          tp.name, tp.title, tp.content
        FROM pocTempSelect AS ts
        JOIN pocPoc AS tp ON tp.id = ts.id
        ORDER BY ts.sel ASC;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN walkCursor;
      walkLoop: LOOP
        FETCH walkCursor INTO theId, theParentId, theUserPrivs, theGroupPrivs, theOtherPrivs, theMode, theName, theTitle, theContent;
        IF done THEN
          LEAVE walkLoop;
        END IF;
        INSERT INTO pocPoc (parentId, userId, groupId, created, createdById, modified, modifiedById, userPrivs, groupPrivs, otherPrivs, mode,
            name, title, content)
          VALUES ((SELECT newId FROM pocTempOldIds WHERE oldId = theParentId),
            @pocUserId, theGroupId, t, @pocUserId, t, @pocUserId, theUserPrivs, theGroupPrivs, theOtherPrivs, theMode,
            theName, theTitle, theContent);
        INSERT INTO tempPocNew (newId, oldId) VALUES (LAST_INSERT_ID(), theId);
      END LOOP;
      CLOSE walkCursor;
    END walkBlock;
    -- walk attribute char
    BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theId, theCreditId, theDebitId, theVoucherId BIGINT DEFAULT 0;
      DECLARE theClassName, theName, theTitle VARCHAR(64) DEFAULT '';
      DECLARE theContent VARCHAR(256) DEFAULT '';
      DECLARE theValue DOUBLE DEFAULT 0.0;
      DECLARE charCursor CURSOR FOR SELECT ta.id, ta.creditId, ta.debitId, ta.voucherId,
          ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeChar AS ta ON ta.creditId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN charCursor;
      charLoop: LOOP
        FETCH charCursor INTO theId, theCreditId, theDebitId, theVoucherId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE charLoop;
        END IF;
        INSERT INTO pocAttributeChar (creditId, debitId, voucherId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES ((SELECT IF(COUNT(*), newId, theCreditId) FROM pocTempOldIds WHERE oldId = theCreditId),
            (SELECT IF(COUNT(*), newId, theDebitId) FROM pocTempOldIds WHERE oldId = theDebitId),
            (SELECT IF(COUNT(*), newId, theVoucherId) FROM pocTempOldIds WHERE oldId = theVoucherId),
            t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE charCursor;
    END;
    -- walk attribute double
    BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theId, theCreditId, theDebitId, theVoucherId BIGINT DEFAULT 0;
      DECLARE theClassName, theName, theTitle VARCHAR(64) DEFAULT '';
      DECLARE theContent, theValue DOUBLE DEFAULT 0.0;
      DECLARE doubleCursor CURSOR FOR SELECT ta.id, ta.creditId, ta.debitId, ta.voucherId,
          ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeDouble AS ta ON ta.creditId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN doubleCursor;
      charLoop: LOOP
        FETCH doubleCursor INTO theId, theCreditId, theDebitId, theVoucherId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE charLoop;
        END IF;
        INSERT INTO pocAttributeDouble (creditId, debitId, voucherId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES ((SELECT IF(COUNT(*), newId, theCreditId) FROM pocTempOldIds WHERE oldId = theCreditId),
            (SELECT IF(COUNT(*), newId, theDebitId) FROM pocTempOldIds WHERE oldId = theDebitId),
            (SELECT IF(COUNT(*), newId, theVoucherId) FROM pocTempOldIds WHERE oldId = theVoucherId),
            t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE doubleCursor;
    END;
    -- walk attribute int
    BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theId, theCreditId, theDebitId, theVoucherId, theContent BIGINT DEFAULT 0;
      DECLARE theClassName, theName, theTitle VARCHAR(64) DEFAULT '';
      DECLARE theValue DOUBLE DEFAULT 0.0;
      DECLARE intCursor CURSOR FOR SELECT ta.id, ta.creditId, ta.debitId, ta.voucherId,
          ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeInt AS ta ON ta.creditId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN intCursor;
      intLoop: LOOP
        FETCH intCursor INTO theId, theCreditId, theDebitId, theVoucherId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE intLoop;
        END IF;
        INSERT INTO pocAttributeInt (creditId, debitId, voucherId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES ((SELECT IF(COUNT(*), newId, theCreditId) FROM pocTempOldIds WHERE oldId = theCreditId),
            (SELECT IF(COUNT(*), newId, theDebitId) FROM pocTempOldIds WHERE oldId = theDebitId),
            (SELECT IF(COUNT(*), newId, theVoucherId) FROM pocTempOldIds WHERE oldId = theVoucherId),
            t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE intCursor;
    END;
    -- walk attribute text
    BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theId, theCreditId, theDebitId, theVoucherId BIGINT DEFAULT 0;
      DECLARE theClassName, theName, theTitle VARCHAR(64) DEFAULT '';
      DECLARE theContent TEXT DEFAULT '';
      DECLARE theValue DOUBLE DEFAULT 0.0;
      DECLARE textCursor CURSOR FOR SELECT ta.id, ta.creditId, ta.debitId, ta.voucherId,
          ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeText AS ta ON ta.creditId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN textCursor;
      textLoop: LOOP
        FETCH textCursor INTO theId, theCreditId, theDebitId, theVoucherId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE textLoop;
        END IF;
        INSERT INTO pocAttributeText (creditId, debitId, voucherId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES ((SELECT newId FROM pocTempOldIds WHERE oldId = theCreditId),
            (SELECT IF(COUNT(pocTempOldIds.oldId), newId, theDebitId) FROM pocTempOldIds WHERE oldId = theDebitId),
            (SELECT IF(COUNT(pocTempOldIds.oldId), newId, theVoucherId) FROM pocTempOldIds WHERE oldId = theVoucherId),
            t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE textCursor;
    END;
  END bodyOfProc;
  DROP TEMPORARY TABLE IF EXISTS pocTempOldIds;
  DELETE FROM pocTempSelect;
END;
