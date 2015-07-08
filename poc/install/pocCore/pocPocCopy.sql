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
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocCopy' AS content;
    --
    CALL pocTempTablesReset;
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
    INSERT INTO pocTempOldIds (oldId, newId) VALUES ((SELECT parentId FROM pocPoc WHERE id = inId), toId);
    walkBlock: BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theId, theParentId BIGINT DEFAULT 0;
      DECLARE theUserPrivs, theGroupPrivs, theOtherPrivs, theMode INT DEFAULT 0;
      DECLARE theName VARCHAR(64) DEFAULT '';
      DECLARE theTitle VARCHAR(255) DEFAULT '';
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
        INSERT INTO pocTempOldIds (newId, oldId) VALUES (LAST_INSERT_ID(), theId);
      END LOOP;
      CLOSE walkCursor;
    END walkBlock;
    DELETE FROM pocTempOldIds WHERE newId = toId;

    -- walk attribute char
    charBlock: BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theCreditId, theDebitId, theReceiptId BIGINT DEFAULT 0;
      DECLARE theClassName, theName VARCHAR(64) DEFAULT '';
      DECLARE theTitle VARCHAR(255) DEFAULT '';
      DECLARE theContent VARCHAR(256) DEFAULT '';
      DECLARE theValue DOUBLE DEFAULT 0.0;
      DECLARE attributeCursor CURSOR FOR SELECT toc.newId, ta.debitId, ta.receiptId, ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeChar AS ta ON ta.creditId = ts.id
        JOIN pocTempOldIds AS toc ON toc.oldId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN attributeCursor;
      attributeLoop: LOOP
        FETCH attributeCursor INTO theCreditId, theDebitId, theReceiptId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE attributeLoop;
        END IF;
        SELECT IF(COUNT(*) > 0, newId, theDebitId) FROM pocTempOldIds WHERE oldId <=> theDebitId INTO theDebitId;
        SELECT IF(COUNT(*) > 0, newId, theReceiptId) FROM pocTempOldIds WHERE oldId <=> theReceiptId INTO theReceiptId;
        INSERT INTO pocAttributeChar (creditId, debitId, receiptId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES (theCreditId, theDebitId, theReceiptId, t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE attributeCursor;
    END charBlock;

    -- walk attribute double
    doubleBlock: BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theCreditId, theDebitId, theReceiptId BIGINT DEFAULT 0;
      DECLARE theClassName, theName VARCHAR(64) DEFAULT '';
      DECLARE theTitle VARCHAR(255) DEFAULT '';
      DECLARE theContent, theValue DOUBLE DEFAULT 0.0;
      DECLARE attributeCursor CURSOR FOR SELECT toc.newId, ta.debitId, ta.receiptId, ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeDouble AS ta ON ta.creditId = ts.id
        JOIN pocTempOldIds AS toc ON toc.oldId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN attributeCursor;
      attributeLoop: LOOP
        FETCH attributeCursor INTO theCreditId, theDebitId, theReceiptId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE attributeLoop;
        END IF;
        SELECT IF(COUNT(*) > 0, newId, theDebitId) FROM pocTempOldIds WHERE oldId <=> theDebitId INTO theDebitId;
        SELECT IF(COUNT(*) > 0, newId, theReceiptId) FROM pocTempOldIds WHERE oldId <=> theReceiptId INTO theReceiptId;
        INSERT INTO pocAttributeDouble (creditId, debitId, receiptId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES (theCreditId, theDebitId, theReceiptId, t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE attributeCursor;
    END doubleBlock;

    -- walk attribute int
    intBlock: BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theContent, theCreditId, theDebitId, theReceiptId BIGINT DEFAULT 0;
      DECLARE theClassName, theName VARCHAR(64) DEFAULT '';
      DECLARE theTitle VARCHAR(255) DEFAULT '';
      DECLARE theValue DOUBLE DEFAULT 0.0;
      DECLARE attributeCursor CURSOR FOR SELECT toc.newId, ta.debitId, ta.receiptId, ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeInt AS ta ON ta.creditId = ts.id
        JOIN pocTempOldIds AS toc ON toc.oldId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN attributeCursor;
      attributeLoop: LOOP
        FETCH attributeCursor INTO theCreditId, theDebitId, theReceiptId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE attributeLoop;
        END IF;
        SELECT IF(COUNT(*) > 0, newId, theDebitId) FROM pocTempOldIds WHERE oldId <=> theDebitId INTO theDebitId;
        SELECT IF(COUNT(*) > 0, newId, theReceiptId) FROM pocTempOldIds WHERE oldId <=> theReceiptId INTO theReceiptId;
        INSERT INTO pocAttributeInt (creditId, debitId, receiptId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES (theCreditId, theDebitId, theReceiptId, t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE attributeCursor;
    END intBlock;

    -- walk attribute text
    textBlock: BEGIN
      DECLARE done INT DEFAULT FALSE;
      DECLARE theCreditId, theDebitId, theReceiptId BIGINT DEFAULT 0;
      DECLARE theClassName, theName VARCHAR(64) DEFAULT '';
      DECLARE theTitle VARCHAR(255) DEFAULT '';
      DECLARE theContent TEXT DEFAULT '';
      DECLARE theValue DOUBLE DEFAULT 0.0;
      DECLARE attributeCursor CURSOR FOR SELECT toc.newId, ta.debitId, ta.receiptId, ta.className, ta.name, ta.title, ta.content, ta.value
        FROM pocTempSelect AS ts
        JOIN pocAttributeText AS ta ON ta.creditId = ts.id
        JOIN pocTempOldIds AS toc ON toc.oldId = ts.id;
      DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
      OPEN attributeCursor;
      attributeLoop: LOOP
        FETCH attributeCursor INTO theCreditId, theDebitId, theReceiptId, theClassName, theName, theTitle, theContent, theValue;
        IF done THEN
          LEAVE attributeLoop;
        END IF;
        SELECT IF(COUNT(*) > 0, newId, theDebitId) FROM pocTempOldIds WHERE oldId <=> theDebitId INTO theDebitId;
        SELECT IF(COUNT(*) > 0, newId, theReceiptId) FROM pocTempOldIds WHERE oldId <=> theReceiptId INTO theReceiptId;
        INSERT INTO pocAttributeText (creditId, debitId, receiptId, created, createdById, modified, modifiedById,
            className, name, title, content, value)
          VALUES (theCreditId, theDebitId, theReceiptId, t, @pocUserId, t, @pocUserId, theClassName, theName, theTitle, theContent, theValue);
      END LOOP;
      CLOSE attributeCursor;
    END textBlock;

  END bodyOfProc;
  DROP TEMPORARY TABLE IF EXISTS pocTempOldIds;
  DELETE FROM pocTempSelect;
END;
