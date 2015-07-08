/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocAttributeDoubleInsert (
    IN inClass VARCHAR(64),
    IN inDebitId BIGINT,
    IN inCreditId BIGINT,
    IN inReceiptId BIGINT,
    IN inName VARCHAR(64),
    IN inTitle VARCHAR(255),
    IN inContent DOUBLE,
    IN inValue DOUBLE)
BEGIN
  DECLARE t, newId BIGINT DEFAULT 0;
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocAttributeDoubleInsert' AS content;
    -- check
    IF pocPocPathFromId(inCreditId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeDoubleInsert' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(inCreditId, 'insert') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocAttributeDoubleInsert' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF inDebitId > 0 AND pocPocPathFromId(inDebitId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeDoubleInsert debitId' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF inReceiptId > 0 AND pocPocPathFromId(inReceiptId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeDoubleInsert receiptId' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- finally
    SET t = UNIX_TIMESTAMP();
    INSERT INTO pocAttributeDouble (creditId, debitId, receiptId, created, createdById, modified, modifiedById, className, name, title, content, value)
      VALUES (inCreditId, inDebitId, inReceiptId, t, @pocUserId, t, @pocUserId, inClass, inName, inTitle, inContent, inValue);
    SET newId = LAST_INSERT_ID();
    -- output
    SELECT 1 AS insertFlag, ta.*, tu.id AS userId, tu.name AS userName, @pocUserName AS createdByName, @pocUserName AS modifiedByName, tp.userPrivs, tp.groupPrivs, tp.otherPrivs, 
        (@pocAdmin OR FIND_IN_SET('run', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('run', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('run', tp.groupPrivs))) + 0 AS runPriv,
        1 AS openPriv,
        (@pocAdmin OR FIND_IN_SET('select', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('select', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('select', tp.groupPrivs))) + 0 AS selectPriv,
        (@pocAdmin OR FIND_IN_SET('insert', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('insert', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('insert', tp.groupPrivs))) + 0 AS insertPriv,
        (@pocAdmin OR FIND_IN_SET('update', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('update', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('update', tp.groupPrivs))) + 0 AS updatePriv,
        (@pocAdmin OR FIND_IN_SET('delete', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('delete', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('delete', tp.groupPrivs))) + 0 AS deletePriv
      FROM pocAttributeDouble AS ta
      LEFT JOIN pocPoc AS tp ON tp.id = ta.creditId
      LEFT JOIN pocUser AS tu ON tu.id = tp.userId
      LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId
      WHERE ta.id = newId;
  END bodyOfProc;
END;
