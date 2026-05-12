<?php
	header('Content-type: application/json');


  if (isset($_GET['action'], $_GET['type']) 
  	&& preg_match("#^(check|initialize|reset|cache|get)$#", $_GET['action'])
  	&& preg_match("#^(models|tags|sources|sites|site|gallery|galleries|connect)$#", $_GET['type'])) {

  	if ($_GET['action'] == 'cache' && !isset($_GET['item_id'])) {
  		$string = json_encode(
				array(
					'error' => 'Ошибка кеширования: не указан ID кешируемого объекта "'.$_GET['type'].'"'
					)
				);
  		echo $string;
  		exit;
  	}

	require_once ("_auth.php");
	require_once ("../classes/class.galleries.php");
	require_once ("../classes/class.models.php");
	require_once ("../classes/class.sites.php");
	require_once ("../classes/class.tags.php");
	require_once ("../classes/class.sources.php");
	require_once ("../classes/class.new-cache.php");
	require_once ("../lib/functions.php");

	$auth->requireAdminJson('Ошибка аутентификации при работе с кэшем. Нужны права администратора.');

	$string = array('error' => 'Unknown error');

	$server = isset($_GET['server']) ? (int)$_GET['server'] : 0;
	$cache_worker = new CacheRebuilder($db->_db, $server);
	if ($_GET['action'] == 'check') {
		if ($_GET['type'] == 'connect') {
			if ($cache_worker->checkConnect()) $string = array('success' => 'Redis connect OK');
			else $string = array('error' => 'No REDIS connect');
		}
		if ($_GET['type'] == 'models') {
			if ($cache_worker->checkModels()) $string = array('success' => 'Models ok');
			else $string = array('error' => 'Models initialization failed');
		} elseif ($_GET['type'] == 'tags') {
			if ($cache_worker->checkTags()) {
				$tags_cached = $cache_worker->tagsCount();
				$string = array('success' => $tags_cached);
			}
			else $string = array('error' => 'Tags initialization failed');
		} elseif ($_GET['type'] == 'sources') {
			if ($cache_worker->checkSources()) $string = array('success' => 'Sources ok');
			else $string = array('error' => 'Sources initialization failed');
		} elseif ($_GET['type'] == 'galleries') {
			if ($cache_worker->checkGalleries()) $string = array('success' => 'Sources ok');
			else $string = array('error' => 'Galleries initialization failed');
		}
	} elseif ($_GET['action'] == 'initialize') {
		if ($_GET['type'] == 'galleries') {
			if ($cache_worker->initializeGalleries()) {
				$galleries_cached = $cache_worker->galleriesCount();
				$string = array('success' => $galleries_cached);
			} else $string = array('error' => 'Galleries initialization failed');
		} elseif ($_GET['type'] == 'models') {
			if (isset($_GET['server'])) {
				if ($cache_worker->server_initializeModels($_GET['server'])) {
					$models_cached = $cache_worker->server_modelsCount($_GET['server']);
					$string = array('success' => $models_cached);
				} else $string = array('error' => 'Models initialization failed');
			} else {
				if ($cache_worker->initializeModels()) {
					$models_cached = $cache_worker->modelsCount();
					$string = array('success' => $models_cached);
				} else $string = array('error' => 'Models initialization failed');
			}
		} elseif ($_GET['type'] == 'tags') {
			if(isset($_GET['site_id'])) {
				$site_id = (int)$_GET['site_id'];
				if ($cache_worker->initializeSiteTags($site_id)) {
					$string = array('success' => 'Tags array cached for Site: '.$site_id);
				} else $string = array('error' => 'Tags initialization for site #'.$site_id.' failed');
			} elseif (isset($_GET['server'])) {
				if ($cache_worker->server_initializeTags($_GET['server'])) {
					$tags_cached = $cache_worker->server_tagsCount($_GET['server']);
					$string = array('success' => $tags_cached);
				} else $string = array('error' => 'Tags initialization failed');
			} else {
				if ($cache_worker->initializeTags()) {
					$tags_cached = $cache_worker->tagsCount();
					$string = array('success' => $tags_cached);
				} else $string = array('error' => 'Tags initialization failed');
			}
			
		} elseif ($_GET['type'] == 'sources') {
			if (isset($_GET['server'])) {
				if ($cache_worker->server_initializeSources($_GET['server'])) {
					$tags_cached = $cache_worker->server_sourcesCount($_GET['server']);
					$string = array('success' => $tags_cached);
				} else $string = array('error' => 'Tags initialization failed');
			} else {
				if ($cache_worker->initializeSources()) {
					$sources_cached = $cache_worker->sourcesCount();
					$string = array('success' => $sources_cached);	
				} else $string = array('error' => 'Sources initialization failed');
			}
		} elseif ($_GET['type'] == 'site' && isset($_GET['site_id'])) {
			if ($cache_worker->initializeSiteGalleries($_GET['site_id'])) {
				$galleries_cached = $cache_worker->siteGalleriesCount($_GET['site_id']);
				$string = array('success' => $galleries_cached);
			} else $string = array('error' => 'Site '.intval($_GET['site_id']).' galleries initialization failed');
		}
	} else $string = array('error' => 'Wrong POST');
  } else {
	$string = array('error' => 'Ошибка запроса к кэшу');
  }
  echo json_encode($string, JSON_UNESCAPED_UNICODE);
?>
