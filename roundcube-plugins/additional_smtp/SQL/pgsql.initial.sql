CREATE TABLE additional_smtp (
  id serial PRIMARY KEY,
  user_id integer NOT NULL REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  iid integer NOT NULL REFERENCES identities(identity_id) ON DELETE CASCADE ON UPDATE CASCADE,
  username varchar(256) DEFAULT NULL,
  password text,
  server varchar(256) DEFAULT NULL,
  enabled smallint NOT NULL DEFAULT 0
);

ALTER TABLE additional_smtp ADD nosavesent SMALLINT NOT NULL DEFAULT '0'; 

CREATE TABLE additional_smtp_hosts (
  id serial PRIMARY KEY,
  domain varchar(255) NOT NULL,
  host varchar(255) DEFAULT NULL,
  ts timestamp NOT NULL
);

CREATE INDEX ix_additional_smtp_user_id ON additional_smtp(user_id);
CREATE INDEX ix_additional_smtp_iid ON additional_smtp(iid);
