/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocUser (
  id BIGINT NOT NULL auto_increment,
  name varchar(64) NOT NULL,
  pw varchar(64) NOT NULL,
  content text NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
--
INSERT INTO pocUser VALUES (1, 'admin', SHA1('admin'), '');
INSERT INTO pocUser VALUES (2, 'user', SHA1('user'), '');
