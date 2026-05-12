<?php

require_once ("../config/config.php");
require_once ("../classes/class.logger.php");
require_once ("../classes/class.db_access.php");
require_once ("../classes/class.users.php");
require_once ("../classes/Auth.php");

$auth = new Auth($db->_db);
$user = $auth->user();
