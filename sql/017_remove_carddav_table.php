<?php

$roundcubeDbName = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME . '_roundcube');

return array(
    'down' => "
	SET FOREIGN_KEY_CHECKS=0;
        DROP TABLE IF EXISTS $roundcubeDbName.carddav_addressbooks;
        DROP TABLE IF EXISTS $roundcubeDbName.carddav_contacts;
        DROP TABLE IF EXISTS $roundcubeDbName.carddav_groups;
        DROP TABLE IF EXISTS $roundcubeDbName.carddav_group_user;
        DROP TABLE IF EXISTS $roundcubeDbName.carddav_migrations;
        DROP TABLE IF EXISTS $roundcubeDbName.carddav_xsubtypes;
	SET FOREIGN_KEY_CHECKS=1;
    "
);
