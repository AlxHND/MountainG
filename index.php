<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function getWidget($widget_name = false)
{

	$template_path = 'templates/';
	$widgets_array = array('users', 'menu', 'sites_stats', 'caches_stats');

	if ($widget_name && in_array($widget_name, $widgets_array)) {
		include($template_path . 'widgets/w.' . $widget_name . '.php');
	}
}

function ShowIndexTitle()
{
	if (isset($_GET['act'])) {
		switch ($_GET['act']) {
			case 'galleries':
				print "База галер";
				if (isset($_GET['tags'])) print ", проставление тегов";
				if (isset($_GET['search'])) print ", поиск по:" . $_GET['search'];
				break;
			case 'paysites':
				print "Платники";
				break;
			case 'zip':
				print "Галеры из ZIPа";
				break;
			case 'cropper':
				print "Кроппер тумб";
				break;
			case 'import':
				print "Импорт галер";
				break;
			case 'sites':
				print "Сайты";
				break;
			case 'galleries':
				print "База галлерей";
				break;
			case 'tags':
				print "База тегов";
				break;
			case 'make':
				print "Сборка галлерей";
				break;
			default:
				print "Some page";
		}
	} else {
		print "Home page";
	}
}

function renderLoginPage($error = '', $loginName = '')
{
	$error = trim((string)$error);
	$loginName = trim((string)$loginName);
?>
	<!DOCTYPE HTML>
	<html lang="ru">

	<head>
		<title>Вход</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="css/all-index.css" type="text/css" />
		<style type="text/css">
			body {
				margin: 0;
				background: #f2f3f7;
				font-family: Arial, sans-serif;
			}

			.login-wrap {
				max-width: 420px;
				margin: 80px auto;
				padding: 32px;
				background: #fff;
				border: 1px solid #d7dbe4;
				box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
			}

			.login-title {
				margin: 0 0 8px;
				font-size: 28px;
				color: #222;
			}

			.login-subtitle {
				margin: 0 0 24px;
				color: #666;
			}

			.login-error {
				margin: 0 0 18px;
				padding: 10px 12px;
				background: #fff1f1;
				border: 1px solid #e8b8b8;
				color: #a10000;
			}

			.login-label {
				display: block;
				margin-bottom: 6px;
				color: #444;
				font-size: 14px;
			}

			.login-input {
				width: 100%;
				box-sizing: border-box;
				margin-bottom: 16px;
				padding: 10px 12px;
				border: 1px solid #bfc7d6;
				font-size: 16px;
			}

			.login-button {
				padding: 10px 18px;
				border: 0;
				background: #2d5bd1;
				color: #fff;
				font-size: 16px;
				cursor: pointer;
			}
		</style>
	</head>

	<body>
		<div class="login-wrap">
			<h1 class="login-title">MountainG</h1>
			<?php if ($error !== '') { ?>
				<div class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
			<?php } ?>
			<form method="post" action="index.php">
				<input type="hidden" name="auth_action" value="login" />
				<input class="login-input" id="login_name" type="text" name="login_name"
					value="<?= htmlspecialchars($loginName, ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" />
				<input class="login-input" id="login_pass" type="password" name="login_pass"
					autocomplete="current-password" />
				<button class="login-button" type="submit">Login</button>
			</form>
		</div>
	</body>

	</html>
<?php
}

include("config/config.php");
include("classes/class.logger.php");
include("classes/class.db_access.php");
include("classes/Users.php");
include("classes/Auth.php");

$user_name = false;
$user_pass = false;
$user_ip = false;
$userAuth = false;
$user_type = false;
$userId = false;

$flag_cronError = false;
$flag_skeepedGallery = false;

$auth = new Auth($db->_db);
$userAuth = $auth->user();
$loginName = '';

if (isset($_GET['logout'])) {
	$auth->logout();
	header('Location: index.php');
	exit;
}

$loginError = '';
if (isset($_POST['auth_action']) && $_POST['auth_action'] === 'login') {
	$loginName = isset($_POST['login_name']) ? $_POST['login_name'] : '';

	if ($auth->attemptLogin($loginName, isset($_POST['login_pass']) ? $_POST['login_pass'] : '')) {
		header('Location: index.php');
		exit;
	}

	$loginError = 'Wrong credentials';
	$userAuth = $auth->user();
}

if (!$auth->isAuthorized()) {
	renderLoginPage($loginError, $loginName);
	exit;
}

// if($_SERVER['REMOTE_ADDR'] == '78.140.141.80') {
// 	var_dump($userAuth);
// 	var_dump($_SERVER);
// 	die;
// }

// var_dump($_SERVER);

if ($userAuth->isAuthorized()) {

	$user_name = $userAuth->getName();
	$user_ip = $userAuth->getIP();

	$user_type = $userAuth->getOperations();
	$user_id = $userAuth->getId();
	$userId = $user_id;

	$user_add_models = $userAuth->allowedToModel();


	include("classes/ftp.php");
	include("classes/parser.php");
	include("classes/grabber.php");
	include("classes/cropper.php");
	include("classes/informator.php");
	include("classes/make.php");
	include("classes/rss.new.php");
	include("classes/class.video.php");
	include("classes/class.models.php");
	include("classes/class.stemming.php");
	include("classes/class.cache.php");
	include("classes/class.templates.php");
	include("classes/class.sites.php");
	include("classes/class.banners.php");
	include("classes/class.banner_spot.php");
	include("classes/class.galleries.php");
	include("classes/class.grabber.php");
	include("classes/class.resizer.php");
	include("classes/class.tags.php");
	include("classes/class.sources.php");
	include("classes/class.updates_parser.php");
	include("classes/class.writers.php");
	include("classes/class.searches.php");
	include("classes/class.images.php");
	include("classes/GifFrameExtractor.php");
	include("classes/class.sitesgalleries.php");

	include("lib/functions.php");

	$FTP = new FTPtools(FTP, FTPUSER, FTPPW);
	$default = new DBTools(HOSTING, $rssThumbSizes);

	if (isset($_SESSION['counter'])) {
		$_SESSION['counter']++;
	} else {
		$_SESSION['counter'] = 0;
		if ($userAuth->isAdmin()) include "lib/script.utils.php"; // вываливает что есть пропущеные галеры, если первый заход
	}
}
?>
<!DOCTYPE HTML>
<html lang="ru">
<header>
	<title><?php ShowIndexTitle(); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="css/jquery.Jcrop.css" type="text/css" />
	<link rel="stylesheet" href="css/demos.css" type="text/css" />
	<link rel="stylesheet" href="css/all-index.css" type="text/css" />
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery.shortcuts.js"></script>
	<script type="text/javascript" src="js/jquery.form.min.js"></script>
	<script type="text/javascript" src="js/categories.js"></script>
	<script type="text/javascript" src="js/all-index-js.js"> </script>

	<script type="text/javascript">
		function get_hosting_url() {
			return "<?= HOSTING ?>";
		}
		// заглавные буквы для каждого слова в Input
		jQuery(function($) {
			$(document).ready(function() {
				$.Shortcuts.add({
					type: 'down',
					mask: 'Ctrl+M',
					enableInInput: true,
					handler: function() {
						SelectedTextFirstLetterUppercase();
					}
				});
				$.Shortcuts.start();
			});
		});
	</script>
	<script type="text/javascript">
		function escapeAuthTestHtml(value) {
			return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
		}

		function toggleAuthTestBox() {
			$('#auth-test-body').toggle();
			return false;
		}

		function submitAuthTestLogin() {
			var login = $('#auth-test-login').val();
			var password = $('#auth-test-password').val();
			var resultNode = $('#auth-test-result');

			resultNode.html('Проверяем...');

			$.ajax({
				url: 'util/auth.test_login.php',
				type: 'POST',
				dataType: 'json',
				data: {
					login: login,
					password: password
				},
				success: function(response) {
					if (response.error) {
						resultNode.html('<div style="color:#a10000;">' + escapeAuthTestHtml(response.error) +
							'</div>');
						return;
					}

					var lines = [];
					lines.push('<strong>Результат:</strong> ' + escapeAuthTestHtml(response.message));
					lines.push('Пользователь в БД: ' + (response.user_found ? 'да' : 'нет'));
					lines.push('Пароль совпал: ' + (response.password_ok ? 'да' : 'нет'));
					lines.push('IP разрешен: ' + (response.ip_allowed ? 'да' : 'нет'));
					lines.push('Вход через форму: ' + (response.login_would_succeed ? 'да' : 'нет'));
					lines.push('Тип хэша: ' + escapeAuthTestHtml(response.hash_type || ''));
					lines.push('Нужен апгрейд хэша: ' + (response.hash_needs_upgrade ? 'да' : 'нет'));
					lines.push('Операции: ' + escapeAuthTestHtml(response.operations || ''));
					lines.push('Текущий IP: ' + escapeAuthTestHtml(response.current_ip || ''));
					lines.push('Разрешенные IP: ' + escapeAuthTestHtml((response.allowed_ips || []).join(
						', ')));

					resultNode.html(lines.join('<br>'));
				},
				error: function(xhr) {
					resultNode.html('<div style="color:#a10000;">Ошибка запроса: ' + escapeAuthTestHtml(xhr
						.status + ' ' + xhr.statusText) + '</div>');
				}
			});

			return false;
		}
	</script>
</header>

<body>
	<?php if ($userAuth->isAuthorized()) { ?>
		<div style="position: fixed; top: 18px; right: 18px; z-index: 10000;">
			<a href="index.php?logout=1"
				style="display: inline-block; padding: 8px 14px; background: #b33636; color: #fff; text-decoration: none; border: 1px solid #8d2525; font-family: Arial, sans-serif; font-size: 13px;">Выход</a>
		</div>
	<?php } ?>
	<?php if ($userAuth->isAdmin() && isset($_GET['act']) && $_GET['act'] === 'users') { ?>
		<div id="auth-test-box"
			style="position: fixed; top: 76px; right: 18px; width: 310px; z-index: 9999; font-family: Arial, sans-serif;">
			<div style="background: #2f4c8f; color: #fff; padding: 9px 12px; cursor: pointer; border: 1px solid #1f335f;"
				onclick="return toggleAuthTestBox();">Тест формы входа</div>
			<div id="auth-test-body"
				style="display: none; background: #fff; border: 1px solid #1f335f; border-top: 0; padding: 12px;">
				<div style="font-size: 12px; color: #666; margin-bottom: 8px;">Текущий IP:
					<?= htmlspecialchars($userAuth->getIP() ? $userAuth->getIP() : '', ENT_QUOTES, 'UTF-8') ?></div>
				<input id="auth-test-login" type="text"
					value="<?= htmlspecialchars($userAuth->getName() ? $userAuth->getName() : '', ENT_QUOTES, 'UTF-8') ?>"
					placeholder="Логин" style="width: 100%; box-sizing: border-box; margin-bottom: 8px; padding: 8px;">
				<input id="auth-test-password" type="password" value="" placeholder="Пароль"
					style="width: 100%; box-sizing: border-box; margin-bottom: 8px; padding: 8px;">
				<button type="button" onclick="return submitAuthTestLogin();" style="padding: 8px 12px;">Проверить</button>
				<div id="auth-test-result" style="margin-top: 10px; font-size: 12px; line-height: 1.45; color: #222;"></div>
			</div>
		</div>
	<?php } ?>
	<?php
	if ($userAuth->isAdmin()) {
		// $reslt = $db->_db->query('SHOW CREATE TABLE galleries_models')->fetchAll(\PDO::FETCH_ASSOC);
		// print_r($reslt); die;

		$sites = new Sites($db->_db);
		$sites_galleries = new SitesGalleries;

		$sites_galleries->fixSitesCacheQueryGalleriesType(); // исправение кривых типов галер
		// $sites->fixSitesAllSources(1);
		// for($i = 0; $i < 150; $i++) {
		// $sites_galleries->processChangesQuery();
		// }

		// $sites_galleries->setSiteId(1);
		// $sites_galleries->fixErrorGalleries();
		// var_dump($sites_galleries->noGalTypeSiteGalleries(1));
		// $sites_galleries->fixSiteGalleriesType(1);

		if (!isset($_GET['utils'])) {
			if ($flag_cronError) echo '<script language="JavaScript">alert("Залип крон");</script>';
			if ($flag_skeepedGallery) echo '<script language="JavaScript">alert("В очереди скипнутые из кропа галеры");</script>';
			// удалить
		}
	}
	?>

	<?php
	if ($userAuth->allowedToTag()) {
		if (preg_match("#^(admin)$#", $user_type)) {
			include("classes/class.new-cache.php");
			$cache_worker = new CacheRebuilder($db->_db);
			//$cache_worker->cacheSiteInfo(1);
			// $cache_worker = new CacheRebuilder($db->_db);
			// echo "<br>Cache query proc:<br>";
			processCacheQuery();
			// echo "<br>FIN Cache query proc</br>"
	?>

			<?php getWidget('menu'); ?>
			<div style="clear: both;"></div>
			<div id="wrapper" style="text-align: center; padding-top:15px; padding-bottom:15px;">
				<?php
				if (isset($_GET['act'])) {

					switch ($_GET["act"]) {

						case 'import':
							include "lib/import-galleries.php";
							break;

						case 'paysites':
							include "lib/paysites.php";
							break;

						case 'make':
							include "lib/make.php";
							break;

						case 'tags':
							include "lib/tags.php";
							break;

						case 'sites':
							include "lib/sites.php";
							break;

						case 'templates':
							include "lib/templates.php";
							break;

						case 'spots':
							include "lib/spots.php";
							break;

						case 'models':
							include "lib/models.php";
							break;

						case 'models_import':
							include "lib/models.import.php";
							break;

						case 'models_pics':
							include "lib/models.add_pics.php";
							break;

						case 'banners':
							include "lib/banners.php";
							break;

						case 'queries':
							include "lib/working_queries_logs.php";
							break;

						case 'trash':
							include "lib/trash.php";
							break;

						case 'zip':
							include "lib/zip.php";
							break;

						case 'users':
							include "lib/users.php";
							break;

						case 'grabber':
							include "lib/grab-galleries.php";
							break;

						case 'galleries':
							include "lib/galleries.php";
							break;

						case 'queries':
							include "lib/working_queries_logs.php";
							break;

						case 'stats':
							include "lib/stats.php";
							break;

						case 'cache':
							include "lib/cache.php";
							break;

						case 'listing':
							include "lib/util.php";
							break;

						case 'logs':
							include "lib/logs_view.php";
							break;

						case 'update_to_rated':
							if (isset($_GET['site_id'])) {
								$site_id = (int)$_GET['site_id'];
								$default->sites_updateToRatingSystem($site_id);
								$cache_worker->siteLikesToDb($site_id);
								$cache_worker->initializeSiteGalleriesRating($site_id);
							}
							break;

						case 'server_state':

							$server_la = sys_getloadavg();

							$galleries_worker = new Galleries($db->_db);

							echo "Main server LA:" . $server_la[0] . ", " . $server_la[1] . ", " . $server_la[2] . "<br>";
							echo "All videos size: " . $galleries_worker->getAllVideosSize('tb') . "Tb<br>";
							echo "Zero size videos: " . $galleries_worker->getEmptyFilesizeVideosCount() . "<br>";
							// echo "Fixing videos: ".$galleries_worker->fixVideoEmptyFilesize()."<br>";
							// echo "Zero size videos: ".Galleries::getEmptyFilesizeVideosCount()."<br>";

							echo "Caching servers state:<br><br>";

							CachingServers::reset();

							while (CachingServers::next()) {

								$cache_server_id = CachingServers::currentID();
								$cache_server_name = CachingServers::currentName();

								$cache_state = $cache_worker->getCacheInfo($cache_server_id);
								if ($cache_state) {
									echo "Server ID #" . $cache_server_id . ", " . $cache_server_name . " | Uptime: " . $cache_state['uptime'] . " days, Memory used: " . $cache_state['memory_used'] . "/" . $cache_state['memory_used_max'] . ", Fragmentation: " . $cache_state['memory_fragmentation'] . "<br><hr>";
								} else {
									echo "<b color='red'>Server ID #" . $cache_server_id . ", " . $cache_server_name . " is not available at the time!</b><br><hr>";
								}
							}
							break;

						case 'cronjobs':
							include "lib/cronjobs.php";
							break;

						case 'restore_cache':
							if (isset($_GET['site_id'])) {
								ini_set('memory_limit', '-1');
								$site_id = (int)$_GET['site_id'];
								$cache_worker->server_cacheSiteInfo($site_id);
								echo "Info OK\n";
								$cache_worker->initializeSiteSources($site_id);
								echo "Sources OK\n";
								$cache_worker->initializeSiteModels($site_id);
								echo "Models OK\n";
								$cache_worker->initializeSiteTags($site_id);
								echo "Tags OK\n";
								$cache_worker->initializeSiteGalleries($site_id);
								echo "Gals OK\n";
							}
							break;

						case 'delete_gallery_cache':
							if (isset($_GET['galid'])) {
								$cache_worker->server_deleteGalleryCache($_GET['galid']);
							}
							break;

						case 'clicks_counter':
							$today = date("ymd", time());

							$db_key_clicks_total 			= "HOUNDXADS:Clicks:Count:Total";
							$db_key_days_list 				= "HOUNDXADS:Clicks:Days:zList";

							$db_key_clicks_list_today    	= "HOUNDXADS:Clicks:List:" . $today;
							$db_key_clicks_count_today   	= "HOUNDXADS:Clicks:Count:" . $today;
							$db_key_clicks_uniques_today 	= "HOUNDXADS:Clicks:Count:Unique:" . $today;

							$redis = new Redis();

							$redis_connected = $redis->connect(REDIS_IP, REDIS_PORT, 0.5);
							if ($redis_connected) {
								$redis->select(REDIS_SERVER);

								$pipeline = $redis->multi(Redis::PIPELINE);

								$pipeline->zRange($db_key_days_list, 0, -1, true);
								$pipeline->get($db_key_clicks_total);

								$pipeline->lRange($db_key_clicks_list_today, 0, -1);
								$pipeline->get($db_key_clicks_count_today);

								$pipeline_result = $pipeline->exec();

								if ($pipeline_result) {
									echo "<div style='text-align: left; margin-left:200px;'>Days tracked:<br>";
									foreach ($pipeline_result[0] as $days) {
										echo "20" . $days[0] . $days[1] .
											"." . $days[2] . $days[3] .
											"." . $days[4] . $days[5];
										echo "<br>";
									}
									echo "<br>";
									echo "All time clicks: <b>" . $pipeline_result[1] . "</b><br><br>";

									echo "Today clicks: <b>" . $pipeline_result[3] . "</b><br><br>";

									echo "Today UAs tracked:<br>";
									if ($pipeline_result[2]) {
										foreach ($pipeline_result[2] as $info) {
											echo "<p>" . $info . "</p>";
										}
									}

									echo "</div>";
								} else {
									echo "Click stats failed";
								}
							} else {
								echo "redis connect failed";
							}
							break;

						case 'cdn_query_check':
							$gal_id = isset($_GET['gal_id']) ? $_GET['gal_id'] : 0;
							$galleries = new Galleries($db->_db);
							$galleries->syncCdnVideo($gal_id);
							break;

						case 'debug':
							// $tag = new Tags($db->_db);
							// $tag->findAllZeroTags();
							$grab = new Grabber_new();
							echo "fetchFile<br>";
							var_dump($grab->fetchFile("https://join.wetandpuffy.com/gallery/MzAwMTg1NC43LjEuMS4wLjIwMzkzLjAuMC4w"));
							$grab = new Grabber();
							echo "FindImages<br>";
							var_dump($grab->FindImages('https://join.wetandpuffy.com/gallery/MzAwMTg1NC43LjEuMS4wLjIwMzkzLjAuMC4w'));
							echo "GetPictures<br>";
							var_dump($grab->GetPictures());
							// echo "END<br>";
							// file_get_contents("http://68hp.com");

							// $img = new Galleries($db->_db);
							// var_dump($img->grabGalleryFile(88481));
							break;

						case 'debug_cache':
							$gal = new Galleries($db->_db);
							$gals = $gal->getOKGalleries();
							$count = 0;
							foreach ($gals as $gal) {
								if (!$cache_worker->gallery_cached($gal)) {
									$cache_worker->cacheGallery($gal);
									echo "<a href='index.php?act=galleries&amp;galid=" . $gal . "' target=_blank'>" . $gal . "</a><br>";
									$count++;
								}
							}
							echo "<br><br>" . $count;
							break;

						case 'searches_approve':
							if (isset($_GET['site_id'])) $cache_worker->getUnapprovedSearches($_GET['site_id']);
							else echo "Site ID not set";

							$search_worker = new Searches();
							$searches = $search_worker->getSearches(50, 0, $_GET['site_id']);

							if ($searches) { ?>
								<script type="text/javascript" src="https://www.google.com/jsapi"></script>
								<script type="text/javascript">
									function OpenInNewTab(url) {
										var win = window.open(url, '_blank');
										win.focus();
									}

									google.load("visualization", "1", {
										packages: ["table"]
									});
									google.setOnLoadCallback(drawTable);

									function drawTable() {
										var data = new google.visualization.DataTable();
										data.addColumn('string', 'id');
										data.addColumn('string', 'key');
										data.addColumn('string', 'сайт');
										data.addColumn('string', 'аппрувлено');
										data.addColumn('string', 'дата');
										//data.addColumn('number', 'Salary');
										//data.addColumn('boolean', 'Full Time Employee');
										data.addRows([
												<?php
												$sites_count = count($searches);
												$counter = 0;
												foreach ($searches as $sites_value) {
													$counter++; ?>["<?= $sites_value['id'] ?>",
														"<?= $sites_value['search_key'] ?>",
														"<?= $sites_value['site_id'] ?>",
														"<?= $sites_value['approved'] ?>",
														"<?php if ($sites_value['added_on'] == 0) echo 'Не известно';
															elseif ((time() - $sites_value['added_on']) >  (24 * 60 * 60)) echo date('d-m-Y', $sites_value['added_on']);
															elseif ((time() - $sites_value['added_on']) >  (2 * 24 * 60 * 60)) echo date('d-m-Y', $sites_value['added_on']);
															elseif ((time() - $sites_value['added_on']) >  (4 * 24 * 60 * 60)) echo date('d-m-Y', $sites_value['added_on']);
															else echo date('d-m-Y', $sites_value['added_on']); ?>"
													] <?php if ($counter < $sites_count) echo ",\n";
													} ?>
											);

											var table = new google.visualization.Table(document.getElementById('table_div'));

											table.draw(data, {
												showRowNumber: true
											});
										}
								</script>
								<div id="table_div" style="float: right; width: 50%;"></div>
							<?php						}
							break;

						case 'test_json_rss':
							$rss = new SelectTools();
							// $site_id, $count, $niche, $type, $excludeNiche, $sort, $offset, $smart_thumbs
							$rss->setThumbSelectMode('random');
							$rss->setSelectOriginalImageMode();

							$x2 = $rss->selectSiteGalleries(1, 10, 0, 0, 0, false, 0, true);
							var_dump($x2);
							break;


						case 'clear_descs':
							/*						
						$replace_array = [
							'% - MenOver30',
							'% - ActiveDuty',
							'% - NextDoorTwink',
							'Family Dick - %',
							'Randyblue - %',
							'Boyfun - %',
							'% - YesFather',
							'MormonBoyz - %',
							'Men over 30 - %',
							'% - FistingInferno',
							'ActiveDuty - %',
							'% - RagingStallion',
							'YOSHIKAWASAKIXXX - %',
							'Club Inferno Dungeon - %',
							'MormonBoyz- %',
							'FistingInferno - %',
							'% - NextDoorRaw',
							'Missionary Boys - %',
							'Mencom - %',
							'FamilyCreep - %',
							'GAYWIRE - %dessc%',
							'Men.com - %',
							'MissionaryBoys-%',
							'RawFuckBoys - %',
							'HotHouse - %',
							'Latin Leche - %',
							'BAITBUS - %',
							'% - FalconStudios',
							'Sean Cody - %',
							'ExtraBigDicks - %',
							'FalconStudios - %',
							'GUY SELECTOR - %',
							'ClubInfernoDungeon - %',
							'% - ExtraBigDicks',
							'% - FamilyCreep',
							'TwinkTop - %',
							'LatinLeche - %',
							'MenOver30 - %',
							'HAIRYANDRAW - %',
							'NextDoorRaw - %',
							'BEARFILMS - %',
							'RagingStallion - %',
							'NASTYDADDY - %',
							'TRAILERTRASHBOYS - %',
							'PETERFEVER - %',
							'BREEDMERAW - %',
							'Hot House - %',
							'MENATPLAY - %',
							'RAWHOLE - %',
							'HAIRYANDRAW %',
							'NextDoorRaw %',
							'BEARFILMS %',
							'FalconStudios %',
							'RagingStallion %',
							'Club Inferno Dungeon %',
							'NASTYDADDY %',
							'TRAILERTRASHBOYS %',
							'PETERFEVER %',
							'BREEDMERAW %',
							'Hot House %',
							'MENATPLAY %',
							'RAWHOLE %',
							'HotHouse %',
							'MenOver30 %',
							'ExtraBigDicks %',
							'Randyblue %',
							'YesFather - %',
							'RANDYBLUECOM - %',
							'ClubInferno - %',
							'ClubInferno %',
							'JasonSparksLive - %',
							'GAYWIRE - %',
							'% - RagingStallio',
							'NextDoorBuddies - %',
							'NextDoorBuddies %',
							'Men - %',
							'ROCKSBOYS - %',
							'SOUTHERNSTROKES - %',
							'SOUTHERNSTROKES %',
							'Maskurbate - %',
							'Maskurbate %',
							'Extra Big Dicks - %',
							'Extra Big Dicks %',
							'ActiveDuty %',
							'NextDoorTaboo - %',
							'NextDoorTaboo %',
							'GrowlBoys - %',
							'GrowlBoys %'
						];
*/
							$replace_array = [
								/*	'% - Missionary Boys',
							'% - NextDoorBuddies',
							'% - NextDoorTaboo',
							'TwinkLoads - %',
							'TwinkLoads %',
							'MissionaryBoyz - %',
							'Falcon Studios - %',
							'REALMENFUCK - %',
							'REALMENFUCK ',
							'StagCollective - %',
							'StagCollective %',
							'BAREBACKTHATHOLE - %',
							'BAREBACKTHATHOLE %',
							'MormonBoyz-%',
							'% - WilliamHiggins',
							'Tushy - %',
							'TUSHY %',
							'BABES - %',
							'PropertySex - %',
							'PropertySex %',
							'% - more at JAVHD Net',
							'HardX - %',
							'EroticaX - %',
							'EroticaX %',
							'NubileFilms - %',
							'Nubile Films - %',
							'NubileFilms %',
							'Nubile Films %',
							'FTV Girls - %',
							'FTV Girls %',
							'Brazzers - %',
							'EvilAngel - %',
							'EvilAngel %',
							'JOYMII - %',
							'NewSensations - %',
							'BLACKED - %',
							'BLACKED %',
							'TEENFIDELITY - %',
							'TEENFIDELITY %',
							'FreeUse Fantasy - %',
							'21 NATURALS - %',
							'VIXEN - %',
							'VIXEN %',
							'FamilyXXX - %',
							'BANGBROS - %',
							'BANGBROS %',
							'New Sensations - %',
							'Described Video - %',
							'21Naturals - %',
							'21Naturals %',
							'NF Busty - %',
							'PORNFIDELITY - %',
							'PORNFIDELITY %',
							'Twistys - %',
							'Naughty America - %',
							'PURE TABOO - %',
							'PURE TABOO %',
							'% - EvilAngel',
							'DEVILS FILM - %',
							'Hijab Hookup - %',
							'FantasyMassage - %',
							'FantasyMassage %',
							'% - FantasyMassage',
							'% FantasyMassage',
							'FUN SIZE BOYS - %',
							'AXELABYSSE - %',
							'AXELABYSSE %',
							'REALMENFUCK - %',
							'REALMENFUCK %',
							'RagingStallion - %',
							'RagingStallion  %',
							'FunSizeBoys - %',
							'FunSizeBoys %',
							'Icon Male - %',
							'MissionaryBoys - %',
							'% - HotHouse',
							'Randyblue.com - %',
							'Raging Stallion - %',
							'FistingCentral - %', */
								'RawFuckBoys - %',
								'RawFuckBoys %',
								'BAIT BUS - %',
								'BRINGMEABOY - %',
								'BRINGMEABOY %',
								'% - MEN.COM',
								'NextDoorTwink - %',
								'NextDoorTwink %',
								'BigStr - %',
								'TRANSEROTICA - %',
								'TRANSEROTICA %',
								'TSPlayground - %',
								'TS Playground %',
								'Trans Angels - %',
								'FANTASY MASSAGE - %',
								'% - DaughterSwap',
								'DaughterSwap - %',
								'JOYMII- %',
								'DEVILSFILM - %',
								'BLACKED.com %',
								'21 SEXTREME - %'
							];

							foreach ($replace_array as $replace_string) {

								// $replace_string = "% - MenOver30";
								$cut_right = substr($replace_string, 0, 1) == '%';
								$replace_string_length = strlen($replace_string) - 1;
								$q_result = $db->_db->query("SELECT gal_id, gal_title FROM galleries WHERE gal_title LIKE '{$replace_string}'");
								$rows = $q_result->fetchAll(\PDO::FETCH_ASSOC);
								foreach ($rows as $gallery) {
									$updated_title = $cut_right ? substr($gallery['gal_title'], 0, strlen($gallery['gal_title']) - $replace_string_length) : ucfirst(substr($gallery['gal_title'], $replace_string_length, strlen($gallery['gal_title'])));
									$updated_title = trim($updated_title, ',.- ');
									echo "ID {$gallery['gal_id']}, Title: '{$gallery['gal_title']}' -> '{$updated_title}'<br>";
									$sql = "UPDATE galleries SET gal_title = :title WHERE gal_id = :gal_id";
									try {
										$stmt = $db->_db->prepare($sql);
										$stmt->execute([
											'title' => $updated_title,
											'gal_id' => $gallery['gal_id']
										]);
									} catch (PDOException $e) {
										var_dump($e);
										return false;
									}
									// break;
								}
								// var_dump($rows);
							}

							break;

						default:
							include "lib/users.php";  ?>
							<div style="width: 1200px; display: block;">
								<?php getWidget('caches_stats'); ?>
								<?php getWidget('sites_stats'); ?>
							</div>
							<div style="clear: both;"></div>
					<?php
							break;
					}
				} else {
					?>
					<div id="wrapper" style="text-align: center; padding:5px;">
			<?php
					getWidget('caches_stats');
					if ($userAuth->isAdmin()) {
						include "lib/home.dashboard.php";
					} else {
						include "lib/galleries.php"; // для работника
					}
				}
			}
		}



			?>
					</div>
					<div style="clear: both;"></div>
</body>

</html>
