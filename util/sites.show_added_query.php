<?php
		if (isset($_POST['site_id']) && isset($_POST['date'])) {
			require_once ("../config/config.php");
			require_once ("../classes/class.logger.php");
			require_once ("../classes/class.db_access.php");
			require_once ("../lib/functions.php");
			$query_result = getSitesQueryByDay($_POST['site_id'], $_POST['date']);
			$output = "<div style='position: relative; width: 900px; border: 1px solid #000;'>";
			if ($query_result && is_array($query_result) && count($query_result)) {
				
				foreach ($query_result as $gal_id => $query_on) {
					$output .= "<div class='site_query_date'>
									<a href='index.php?act=galleries&amp;galid=".$gal_id."'>".$gal_id.", ".date("H:i:s",$query_on)."</a>
								</div>";
				}
			} else {
				$output .= " Очередь пустая";
			}
		} else {
			$output .= "Ошибка входящих данных";
		}
		$output .= "<div style='clear: both;'></div>
		</div>";
		echo $output;
?>