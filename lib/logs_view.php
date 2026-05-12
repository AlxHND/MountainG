<?php
	function getLogText($use_log) {
		$result = false;
		$fp = fopen($use_log, 'r');

		$pos = -2; // Skip final new line character (Set to -1 if not present)

		$result = array();
		$currentLine = '';

		while (-1 !== fseek($fp, $pos, SEEK_END)) {
		    $char = fgetc($fp);
		    if (PHP_EOL == $char) {
		            $result[] = $currentLine;
		            $currentLine = '';
		    } else {
		            $currentLine = $char . $currentLine;
		    }
		    $pos--;
		}

		$result[] = $currentLine;
		$result = implode("\n", $result);
		return $result;
	}

	$date = date("Y-m-d");

	$log_type = false;

	$common_log = LOG_FOLDER."/".$date.".log";
	$crons_log = LOG_FOLDER."/crons-".$date.".log";
	$errors_log = LOG_FOLDER."/error-".$date.".log";
	$cron_errors_log = LOG_FOLDER."/crons-error-".$date.".log";
	$php_errors_log = LOG_FOLDER."/PHP_errors.log";

	$use_log = false;
	$log_text = false;

	if(isset($_GET['type'])) {
		if(preg_match("#^(errors|crons|crons_errors|php_errors)$#", $_GET['type'])) {
			$log_type = $_GET['type'];
		}
	} else { $log_type = true; }

	if($log_type) {
		if($log_type === true) {
			$use_log = $common_log;
			$log_text = "Общие логи, инфа о запусках/обработках";
		} elseif($log_type == 'errors') {
			$use_log = $errors_log;
			$log_text = "Логи ошибок";
		} elseif($log_type == 'crons') {
			$use_log = $crons_log;
			$log_text = "Логи запуска/останова кронов";
		} elseif($log_type == 'crons_errors') {
			$use_log = $cron_errors_log;
			$log_text = "Ошибки кронов";
		} elseif($log_type == 'php_errors') {
			$use_log = $php_errors_log;
			$log_text = "Ошибки PHP";
		}

		if($use_log && $log_text) {
			if(is_file($use_log)) {
?>
				<h2><?=$log_text?></h2>
				<br>
				<hr>
<?php
				$file_text = getLogText($use_log);
?>
				<textarea style="width: 90%; height: 500px;" name="comment" form="usrform"><?=$file_text?></textarea>
<?php				

			} else {
?>
				<h3>Нет файла <?=$use_log?></h3>
<?php			
			}
		} else {
?>
			<h3>Огбика выбора тип логов</h3>
<?php			
		}

	} else {
?>
		<h3>Выбери тип логов</h3>
<?php		
	}
?>
