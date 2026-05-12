<?php
	$processing_cron = TMPDIR."/.crop";
	$sites_query_cron = TMPDIR."/.cron.sites_query";
	$pageviews_to_db_cron = TMPDIR."/.cron.pageviews-likes";
	$grab_cron = TMPDIR."/.cron-grab";
	$cron_resizer = TMPDIR."/.cron-resizer";

	$force = false;

	if(isset($_GET['force'])) {
		$force = 1;
	}

	if(isset($_GET['kill_job_file'])) {
		switch ($_GET['kill_job_file']) {
			case 'sites_query_cron':
				if (file_exists($sites_query_cron)) {
					$f_time = filemtime($sites_query_cron);
					if (time() > $f_time+ 600 || $force) {
						unlink($sites_query_cron);
					} else {
						echo 'Крон запущем менее часа назад, точно удалить? <a href="index.php?act=cronjobs&amp;kill_job_file=sites_query_cron&amp;force=1">Да</a>';
					}
				} else {
					echo "Семафор крона не удален: отсутствет файл<br>";
				}
				break;
			case 'pageviews_to_db_cron':
				if (file_exists($pageviews_to_db_cron)) {
					$f_time = filemtime($pageviews_to_db_cron);
					if (time() > $f_time+ 600 || $force) {
						unlink($pageviews_to_db_cron);
					} else {
						echo 'Крон запущем менее часа назад, точно удалить? <a href="index.php?act=cronjobs&amp;kill_job_file=pageviews_to_db_cron&amp;force=1">Да</a>';
					}
				} else {
					echo "Семафор крона не удален: отсутствет файл<br>";
				}
				break;
			case 'grab_cron':
				if (file_exists($grab_cron)) {
					$f_time = filemtime($grab_cron);
					if (time() > $f_time+ 600 || $force) {
						unlink($grab_cron);
					} else {
						echo 'Крон запущем менее часа назад, точно удалить? <a href="index.php?act=cronjobs&amp;kill_job_file=grab_cron&amp;force=1">Да</a>';
					}
				} else {
					echo "Семафор крона не удален: отсутствет файл<br>";
				}
				break;
			case 'cron_resizer':
				if (file_exists($cron_resizer)) {
					$f_time = filemtime($cron_resizer);
					if (time() > $f_time+ 600 || $force) {
						unlink($cron_resizer);
					} else {
						echo 'Крон запущем менее часа назад, точно удалить? <a href="index.php?act=cronjobs&amp;kill_job_file=cron_resizer&amp;force=1">Да</a>';
					}
				} else {
					echo "Семафор крона не удален: отсутствет файл<br>";
				}
				break;
			case 'processing_cron':
				if (file_exists($processing_cron)) {
					$f_time = filemtime($processing_cron);
					if (time() > $f_time+ 600 || $force) {
						unlink($processing_cron);
					} else {
						echo 'Крон запущем менее часа назад, точно удалить? <a href="index.php?act=cronjobs&amp;kill_job_file=processing_cron&amp;force=1">Да</a>';
					}
				} else {
					echo "Семафор крона не удален: отсутствет файл<br>";
				}
				break;
			
			default:
				# code...
				break;
		}

	}
	
	$cronjobs_flag = false;
	
	if (file_exists($processing_cron)) {
		$f_time = filemtime($processing_cron);
		echo "Крон обработки галер запущен: ";
		echo "<b>".date("Y-m-d H:i:s", $f_time)."</b>";
		if (time() > $f_time+ 300) {
			echo '<a href="index.php?act=cronjobs&amp;kill_job_file=processing_cron">Удалить семафор крона</a>';
		}
		echo "<br>";
		$cronjobs_flag = true;
	}
	if (file_exists($sites_query_cron)) {
		$f_time = filemtime($sites_query_cron);
		echo "Крон обработки очереди галер на сайты: ";
		echo "<b>".date("Y-m-d H:i:s", $f_time)."</b>";
		if (time() > $f_time+ 300) {
			echo '<a href="index.php?act=cronjobs&amp;kill_job_file=sites_query_cron">Удалить семафор крона</a>';
		}
		echo "<br>";
		$cronjobs_flag = true;
	}
	if (file_exists($pageviews_to_db_cron)) {
		$f_time = filemtime($pageviews_to_db_cron);
		echo "Крон обработки пейджвью и лайков: ";
		echo "<b>".date("Y-m-d H:i:s", $f_time)."</b>";
		if (time() > $f_time+ 300) {
			echo '<a href="index.php?act=cronjobs&amp;kill_job_file=pageviews_to_db_cron">Удалить семафор крона</a>';
		}
		echo "<br>";
		$cronjobs_flag = true;
	}
	if (file_exists($grab_cron)) {
		$f_time = filemtime($grab_cron);
		echo "Крон граба галер: ";
		echo "<b>".date("Y-m-d H:i:s", $f_time)."</b>";
		if (time() > $f_time+ 300) {
			echo '<a href="index.php?act=cronjobs&amp;kill_job_file=grab_cron">Удалить семафор крона</a>';
		}
		echo "<br>";
		$cronjobs_flag = true;
	}

	if (file_exists($cron_resizer)) {
		$f_time = filemtime($cron_resizer);
		echo "Крон ресайза галер к вертикальным тумбам: ";
		echo "<b>".date("Y-m-d H:i:s", $f_time)."</b>";
		if (time() > $f_time+ 300) {
			echo '<a href="index.php?act=cronjobs&amp;kill_job_file=cron_resizer">Удалить семафор крона</a>';
		}
		echo "<br>";
		$cronjobs_flag = true;
	}

	
?>
