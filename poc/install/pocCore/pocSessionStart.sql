/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore
-- pocVersion: 0.1.0

*******************************************************************************/

CREATE PROCEDURE pocSessionStart (
    IN session VARCHAR(64),
    IN logout INT,
    IN inMode INT,
    IN expiretime BIGINT,
    IN lifetime BIGINT,
    IN login VARCHAR(64),
    IN passw VARCHAR(64))
BEGIN
  DECLARE n, t BIGINT DEFAULT 0;
  DECLARE expired, currentMode INT DEFAULT 0;
  bodyOfProc: BEGIN
    IF @pocSessionId THEN
      SELECT 400 AS id, 'Bad Request' AS name, 'Session already started' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- init
    SET @pocSessionId = 0, @pocUserId = 0, @pocAdmin = 0, @pocCoder = 0, t = UNIX_TIMESTAMP();
    -- expire
    DELETE FROM pocSession WHERE (modified + lifetime) < t;
    -- check
    SELECT COUNT(ts.id), ts.id, ts.userId, ts.mode + 0, (NOT ((FIND_IN_SET('stay', ts.mode) > 0) OR (ts.modified + expiretime) > t)) + 0
      FROM pocSession AS ts
      WHERE ts.name = session
      INTO n, @pocSessionId, @pocUserId, currentMode, expired;
    IF @pocSessionId > 0 THEN
      IF expired > 0 THEN
        SET @pocUserId = 0, currentMode = 0;
        SELECT 'pocDefine' AS className, 'Session expired' AS _POC_SESSION_EXPIRED_;
      END IF;
    ELSE
      INSERT INTO pocSession (name, created) VALUES (session, t);
      SET @pocSessionId = LAST_INSERT_ID(), @pocUserId = 0, currentMode = 0;
      SELECT 'pocDefine' AS className, 'New session' AS _POC_NEW_SESSION_;
    END IF;
    -- login/out
    IF logout > 0 THEN
      SET @pocUserId = 0, inMode = 0;
    ELSEIF login != '' THEN
      SELECT COUNT(t.id), t.id FROM pocUser AS t WHERE t.name = login AND t.pw = SHA1(passw) INTO n, @pocUserId;
      IF NOT n THEN
        SET @pocUserId = 0, inMode = 0;
        SELECT 'pocDefine' AS className, 'Login Failed' AS _POC_LOGIN_FAILED_;
        SELECT 403 AS id, 'Forbidden' AS name, 'Login Failed' AS content;
      END IF;
    ELSE
      SET inMode = currentMode;
    END IF;
    -- temporary tables
    CREATE TEMPORARY TABLE pocTempSelect (id BIGINT, sel INT, hit INT, root INT, path TEXT);
    CREATE TEMPORARY TABLE pocTempIds (tempId BIGINT, root BIGINT, path TEXT);
    CREATE TEMPORARY TABLE pocTempWhere (whereClause TEXT, whereMode VARCHAR(16));
    -- finally
    SET t = UNIX_TIMESTAMP();
    UPDATE pocSession SET userId = @pocUserId, mode = inMode, modified = t WHERE id = @pocSessionId;
    -- setup
    SET @pocAdmin = (@pocUserId = 1 OR (SELECT COUNT(*) FROM pocUser2Group WHERE userId = @pocUserId AND groupId = 1)) + 0,
      @pocCoder = (@pocAdmin OR (SELECT COUNT(*) FROM pocUser2Group WHERE userId = @pocUserId AND groupId = 2)) + 0;
    -- output
    SELECT 'pocSessionInit' AS className, ts.content AS sessionData, tu.content AS userData,
        t AS _POC_SESSION_TIME_, ts.id AS _POC_SESSION_ID_, ts.name AS _POC_SESSION_NAME_,
        tu.id AS _POC_USER_ID_, tu.name AS _POC_USER_NAME_, @pocAdmin AS _POC_USER_IS_ADMIN_, @pocCoder AS _POC_USER_IS_CODER_
      FROM pocSession AS ts
      LEFT JOIN pocUser AS tu ON tu.id = ts.userId
      WHERE ts.id = @pocSessionId;
  END bodyOfProc;
END;