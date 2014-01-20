/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE FUNCTION pocSelectBuildWhereClause ()
RETURNS TEXT
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE spacer VARCHAR(16) DEFAULT '';
  DECLARE clause, theWhere TEXT DEFAULT '';
  DECLARE andCursor CURSOR FOR SELECT whereClause FROM pocTempWhere WHERE whereMode = 'AND';
  DECLARE orCursor CURSOR FOR SELECT whereClause FROM pocTempWhere WHERE whereMode = 'OR';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  OPEN andCursor;
  andLoop: LOOP
    FETCH andCursor INTO theWhere;
    IF done THEN
      LEAVE andLoop;
    END IF;
    SET clause = CONCAT(clause, spacer, '(', theWhere, ')');
    SET spacer = '\n      AND ';
  END LOOP;
  CLOSE andCursor;
  IF (SELECT COUNT(*) FROM pocTempWhere WHERE whereMode = 'OR') > 0 THEN
    SET done = 0, spacer = '';
    IF LENGTH(clause > 0) THEN
      SET clause = CONCAT(clause, '\n    OR (');
    ELSE
      SET clause = CONCAT(clause, '(');
    END IF;
    OPEN orCursor;
    orLoop: LOOP
      FETCH orCursor INTO theWhere;
      IF done THEN
        LEAVE orLoop;
      END IF;
      SET clause = CONCAT(clause, spacer, '(', theWhere, ')');
      SET spacer = '\n      OR ';
    END LOOP;
    CLOSE orCursor;
    SET clause = CONCAT(clause, ')');
  END IF;
  RETURN clause;
END;
