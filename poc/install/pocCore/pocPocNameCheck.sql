/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE FUNCTION pocPocNameCheck (name TEXT)
RETURNS INT
BEGIN
  RETURN name NOT REGEXP '[^-_a-zA-Z0-9]';
END;