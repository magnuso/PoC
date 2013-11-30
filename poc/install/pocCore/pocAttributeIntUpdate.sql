/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocAttributeIntUpdate (
    IN inId BIGINT,
    IN inDebitId BIGINT,
    IN inVoucherId BIGINT,
    IN inName VARCHAR(64),
    IN inTitle VARCHAR(64),
    IN inContent BIGINT,
    IN inValue DOUBLE)
BEGIN
  DECLARE n, creditId BIGINT DEFAULT 0;
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
      BEGIN
        SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocInsert' AS content;
      END;
    -- get poc id
    SELECT COUNT(ta.id), ta.creditId FROM pocAttributeInt as ta WHERE id = inId INTO n, creditId;
    -- check
    IF n < 0 THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeIntUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF pocPocPathFromId(creditId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeIntUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(creditId, 'update') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocAttributeIntUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF inDebitId > 0 AND pocPocPathFromId(inDebitId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeIntUpdate debitId' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF inVoucherId > 0 AND pocPocPathFromId(inVoucherId) IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocAttributeIntUpdate voucherId' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- finally
    UPDATE pocAttributeInt SET debitId = inDebitId, voucherId = inVoucherId,
      modified = UNIX_TIMESTAMP(), modifiedById = @pocUserId, name = inName, title = inTitle, content = inContent, value = inValue
      WHERE id = inId;
    -- output
    SELECT 1 AS updateFlag, ta.*, tuc.name AS createdByName, tum.name AS modifiedByName, tp.userPrivs + 0, tp.groupPrivs + 0, tp.otherPrivs + 0,
        (@pocAdmin OR FIND_IN_SET('run', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('run', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('run', tp.groupPrivs))) + 0 AS runPriv,
        1 AS openPriv,
        (@pocAdmin OR FIND_IN_SET('select', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('select', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('select', tp.groupPrivs))) + 0 AS selectPriv,
        (@pocAdmin OR FIND_IN_SET('insert', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('insert', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('insert', tp.groupPrivs))) + 0 AS insertPriv,
        (@pocAdmin OR FIND_IN_SET('update', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('update', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('update', tp.groupPrivs))) + 0 AS updatePriv,
        (@pocAdmin OR FIND_IN_SET('delete', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('delete', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('delete', tp.groupPrivs))) + 0 AS deletePriv
      FROM pocAttributeInt AS ta
      LEFT JOIN pocPoc AS tp ON tp.id = ta.creditId
      LEFT JOIN pocUser AS tuc ON tuc.id = ta.createdById
      LEFT JOIN pocUser AS tum ON tum.id = ta.modifiedById
      LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId
      WHERE ta.id = inId;
  END bodyOfProc;
END;
