/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocGroup (
  id BIGINT NOT NULL auto_increment,
  name varchar(64) NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
--
INSERT INTO pocGroup VALUES (1, 'admin');
INSERT INTO pocGroup VALUES (2, 'coder');
INSERT INTO pocGroup VALUES (3, 'user');
INSERT INTO pocGroup VALUES (4, 'member');
