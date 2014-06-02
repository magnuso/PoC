/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocInsert (
    IN inId BIGINT,
    IN inName VARCHAR(64),
    IN inTitle VARCHAR(255),
    IN inContent TEXT,
    IN inMode INT)
BEGIN
  DECLARE n, userPrivs, groupPrivs, otherPrivs INT DEFAULT 0;
  DECLARE t, groupId BIGINT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  bodyOfProc: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocInsert' AS content;
    --
    CALL pocTempTablesReset;
    --
    IF inId > 0 THEN
      SET path = pocPocPathFromId(inId);
      IF path IS NULL THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocInsert' AS content;
        LEAVE bodyOfProc;
      END IF;
      IF NOT pocPocCheckPriv(inId, 'insert') THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocInsert' AS content;
        LEAVE bodyOfProc;
      END IF;
      IF NOT pocPocSaveCheck(inContent) THEN
        SELECT 403 AS id, 'Forbidden' AS name, 'pocPocInsert' AS content;
        LEAVE bodyOfProc;
      END IF;
    ELSE
      IF NOT @pocAdmin THEN
        SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocInsert' AS content;
        LEAVE bodyOfProc;
      END IF;
      SET path = CONCAT(path, '/');
    END IF;
    --
    SELECT COUNT(tp.id), UNIX_TIMESTAMP(), tp.groupId, tp.userPrivs, tp.groupPrivs, tp.otherPrivs
      FROM pocPoc AS tp WHERE tp.id = inId
      INTO n, t, groupId, userPrivs, groupPrivs, otherPrivs;
    INSERT INTO pocPoc (parentId, userId, groupId, created, createdById, modified, modifiedById,
          userPrivs, groupPrivs, otherPrivs, mode, name, title, content)
        VALUES (inId, @pocUserId, groupId, t, @pocUserId, t, @pocUserId, userPrivs, groupPrivs, otherPrivs,
          inMode, inName, inTitle, inContent);
    SET inId = LAST_INSERT_ID();
    -- output
    INSERT INTO pocTempSelect (id, hit, path) VALUES (inId, 1, pocPocPathFromId(inId));
    CALL pocPocCreatePocs();
  END bodyOfProc;
END;
