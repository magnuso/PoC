/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocPoc (
  id BIGINT NOT NULL auto_increment,
  parentId BIGINT NOT NULL,
  userId BIGINT NOT NULL,
  groupId BIGINT NOT NULL,
  created BIGINT NOT NULL,
  createdById BIGINT NOT NULL,
  modified BIGINT NOT NULL,
  modifiedById BIGINT NOT NULL,
  userPrivs SET('run', 'open', 'select', 'insert', 'update', 'delete'),
  groupPrivs SET('run', 'open', 'select', 'insert', 'update', 'delete'),
  otherPrivs SET('run', 'open', 'select', 'insert', 'update', 'delete'),
  mode SET('navi', 'search', 'cache'),
  name varchar(64) NOT NULL,
  title varchar(255) NOT NULL,
  content text NOT NULL,
  PRIMARY KEY (id) USING BTREE,
  UNIQUE KEY name (parentId, name)
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
--
INSERT INTO pocPoc VALUES (1, 0, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'bin', '', '');
INSERT INTO pocPoc VALUES (2, 0, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'etc', '', '');
INSERT INTO pocPoc VALUES (3, 0, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'home', '', '');
INSERT INTO pocPoc VALUES (4, 0, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'lib', '', '');
INSERT INTO pocPoc VALUES (5, 0, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'usr', '', '');
INSERT INTO pocPoc VALUES (6, 0, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'www', '', '');
--
INSERT INTO pocPoc VALUES (7, 1, 1, 1, 0, 1, 0, 1, 'run,open,select,insert,update,delete', 'run,open,select,insert,update,delete', 'run,open,select', '', 'postprocurl', '', '<?php return \$params[0]; ?>');
INSERT INTO pocPoc VALUES (8, 2, 1, 1, 0, 1, 0, 1, 'run,open,select,insert,update,delete', 'run,open,select,insert,update,delete', 'run,open,select', '', 'init', '', '');
INSERT INTO pocPoc VALUES (9, 3, 1, 1, 0, 1, 0, 1, 'run,open,select,insert,update,delete', 'run,open,select,insert,update,delete', '', '', 'user', 'User''s Home', '<h1>User''s Home</h1>');
INSERT INTO pocPoc VALUES (10, 5, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'lib', '', '');
INSERT INTO pocPoc VALUES (11, 5, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'bin', '', '');
--
INSERT INTO pocPoc VALUES (12, 10, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'classes', '', '');
INSERT INTO pocPoc VALUES (13, 12, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', '', 'poc', '', '');
INSERT INTO pocPoc VALUES (14, 12, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', 'navi', 'pocAttributeChar', 'Word', '');
INSERT INTO pocPoc VALUES (15, 12, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', 'navi', 'pocAttributeDouble', 'Float', '');
INSERT INTO pocPoc VALUES (16, 12, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', 'navi', 'pocAttributeInt', 'Integer', '');
INSERT INTO pocPoc VALUES (17, 12, 1, 1, 0, 1, 0, 1, 'open,select,insert,update,delete', 'open,select,insert,update,delete', 'open,select', 'navi', 'pocAttributeText', 'Text', '');
