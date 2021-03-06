<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

$roundcubeDbName = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME . '_roundcube');

return array(
    'up'   => "
        ALTER TABLE $roundcubeDbName.tasks ADD status ENUM('','NEEDS-ACTION','IN-PROCESS','COMPLETED','CANCELLED') NOT NULL DEFAULT '' AFTER complete;
        UPDATE $roundcubeDbName.tasks SET status='COMPLETED' WHERE complete=1.0 AND status='';
        REPLACE INTO $roundcubeDbName.system (name, value) VALUES ('tasklist-database-version', '2014051900');
    ",
    'down' => "
	SET FOREIGN_KEY_CHECKS=0;
        ALTER TABLE $roundcubeDbName.tasks DROP status;
        DELETE FROM $roundcubeDbName.system WHERE name = 'tasklist-database-version';
	SET FOREIGN_KEY_CHECKS=1;
    "
);
