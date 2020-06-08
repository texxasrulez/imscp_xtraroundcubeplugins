CREATE TABLE IF NOT EXISTS 'additional_smtp' (
  'id' INTEGER NOT NULL PRIMARY KEY ASC,
  'user_id' int(10) NOT NULL,
  'iid' int(10) NOT NULL,
  'username' varchar(256) DEFAULT NULL,
  'password' text,
  'server' varchar(256) DEFAULT NULL,
  'enabled' int(1) NOT NULL DEFAULT '0',
  CONSTRAINT 'additional_smtp_ibfk_1' FOREIGN KEY ('user_id') REFERENCES 'users'
    ('user_id') ON DELETE CASCADE ON UPDATE CASCADE
);

ALTER TABLE additional_smtp ADD nosavesent SMALLINT NOT NULL DEFAULT '0'; 

CREATE TABLE IF NOT EXISTS 'additional_smtp_hosts' (
  'id' INTEGER NOT NULL PRIMARY KEY ASC,
  'domain' varchar(255) NOT NULL,
  'host' varchar(255) DEFAULT NULL,
  'ts' datetime NOT NULL
);

CREATE INDEX additional_smtp_iid ON 'additional_smtp'('iid');
