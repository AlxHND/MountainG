<?php
	if (is_file(CRON_FLAG_FILE)) {
		if ((time()-filemtime(CRON_FLAG_FILE)) > 600) $flag_cronError = true;
	}
	$flag_Galleries = new Galleries($db->_db);
	if ($skeepedGals = $flag_Galleries->getSkeepedGalleries()) {
		$flag_skeepedGallery = count($skeepedGals);
	}
?>