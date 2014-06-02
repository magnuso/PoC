/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE FUNCTION pocPocCheckPriv (id BIGINT, inPriv VARCHAR(16))
RETURNS int
BEGIN
  DECLARE n, priv INT DEFAULT 0;
  IF @pocAdmin THEN
    RETURN 1;
  END IF;
  SELECT COUNT(tp.id),
      FIND_IN_SET(inPriv, tp.otherPrivs) OR
        (tp.userId = @pocUserId AND FIND_IN_SET(inPriv, tp.userPrivs)) OR
        (tp.groupId = tu2g.groupId AND FIND_IN_SET(inPriv, tp.userPrivs))
    FROM pocPoc AS tp
    LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId
    WHERE tp.id = id
    INTO n, priv;
  RETURN priv + 0;
END;
