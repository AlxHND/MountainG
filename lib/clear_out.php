<?php
	require_once ("config/config.php");
	require_once (LIB_DIR."/classes/class.users.php");
	require_once (LIB_DIR."/classes/class.logger.php");
	require_once (LIB_DIR."/classes/class.db_access.php");
	$userAuth = new Users($db->_db);
	$userAuth->clearUserWorkingTable($_POST['user_id']);

?>