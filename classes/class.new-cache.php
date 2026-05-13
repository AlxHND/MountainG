<?php
/*
	CachingServers
	кеширующие серверы как синглтон
	Срзу за классом идет инициализация, данные берутся из CFG
	(массив)
*/
class CachingServers
{
	private static $instance; // stores the instance
	private static $error = false;

	private static $servers_array = array();

	private static $id = false;
	private static $name = false;
	private static $ip = false;
	private static $port = false;
	private static $prev_server = false;
	private static $current_server = false;
	private static $next_server = false;

	private static $first_server = false;
	private static $last_server = false;

	private static $servers_count = 0;


	private function __construct() {} // block directly instantiating
	private function __clone() {} // block cloning of the object

	// public static function get() {
	//    	if(!isset(self::$instance)) {
	// 		self::setConnection();
	// 	}
	// 	// return the instance
	// 	return self::$instance;
	//   	}

	public static function addServer($id, $name, $ip, $port)
	{

		$id = (int)$id;
		$port = (int)$port;

		if ($id < 0) $id = false;
		if ($port < 0) $port = false;

		if (!preg_match("([a-z0-9\s-]{1,64})", $name)) {
			$name = false;
		}

		if ($ip == 'localhost') $ip = "127.0.0.1";
		if (!ip2long($ip)) $ip = false;

		$result = false;

		if ($id !== false && $name && $ip && $port) {
			if (!array_key_exists($id, self::$servers_array)) {
				self::$servers_array[$id]['id'] = $id;
				self::$servers_array[$id]['name'] = $name;
				self::$servers_array[$id]['ip'] = $ip;
				self::$servers_array[$id]['port'] = $port;
				self::$servers_array[$id]['prev_server'] = false;
				self::$servers_array[$id]['next_server'] = false;

				// формирование связанного списка для возможности прохождения по списку, удаления и добавления
				// новых серверов
				if (self::$first_server === false) {
					self::$first_server = $id;
					self::$last_server = $id;
				} else {
					$current_last_server_id = self::$last_server;
					self::$last_server = $id;
					self::$servers_array[$id]['prev_server'] = $current_last_server_id;
					self::$servers_array[$current_last_server_id]['next_server'] = $id;
				}
				self::$servers_count++;
				$result = self::$servers_count;
			} else {
				self::$error = __METHOD__ . ": ID сервера #" . $id . " уже существует (пытался добавить id:" . $id . ", name:" . $name . ",ip:" . $ip . ",port:" . $port . ")";
			}
		} else {
			self::$error = __METHOD__ . ": входящие параметры неверные";
		}

		return $result;
	}

	public static function setServer($id)
	{
		$id = (int)$id;
		if ($id < 0) $id = false;

		$result = false;

		if ($id !== false && isset(self::$servers_array[$id])) {

			self::$id = self::$servers_array[$id]['id'];
			self::$name = self::$servers_array[$id]['name'];
			self::$ip = self::$servers_array[$id]['ip'];
			self::$port = self::$servers_array[$id]['port'];
			self::$prev_server = self::$servers_array[$id]['prev_server'];
			self::$current_server = self::$id;
			self::$next_server = self::$servers_array[$id]['next_server'];
			$result = true;
		}

		return $result;
	}



	public static function next()
	{
		$result = false;

		if (self::$id !== false) {
			if (self::$next_server !== false) {
				self::setServer(self::$next_server);
				$result = true;
			} else {
				self::resetToFirst();
			}
		} else {
			self::resetToFirst();
			if (self::$id !== false) $result = true;
		}

		return $result;
	}

	public static function previous() {}

	public static function resetToFirst()
	{
		if (self::$first_server !== false) {
			self::setServer(self::$first_server);
		} else {
			self::$id = false;
			self::$name = false;
			self::$ip = false;
			self::$port = false;
			self::$prev_server = false;
			self::$current_server = false;
			self::$next_server = false;
		}
	}

	public static function reset()
	{
		self::$id = false;
		self::$name = false;
		self::$ip = false;
		self::$port = false;
		self::$prev_server = false;
		self::$current_server = false;
		self::$next_server = false;
	}

	public static function serverInfo()
	{
		$result = false;
		if (self::$id !== false) {
			$result = self::$servers_array[self::$id];
		}
		return $result;
	}

	public static function currentID()
	{
		return self::$id;
	}
	public static function currentIP()
	{
		return self::$ip;
	}
	public static function currentName()
	{
		return self::$name;
	}

	public static function currentPort()
	{
		return self::$port;
	}

	public static function getServersCount()
	{
		return self::$servers_count;
	}

	public static function getLastErrorMsg()
	{
		return self::$error;
	}

	public static function listServers()
	{
		var_dump(self::$servers_array);
	}

	public static function isServerExists($caching_server_id)
	{
		if (isset(self::$servers_array[$caching_server_id]) && isset(self::$servers_array[$caching_server_id]['port']) && self::$servers_array[$caching_server_id]['port']) {
			return true;
		}
		return false;
	}
}

/*
Инициализация $caching_servers
*/

if (isset($caching_servers) && is_array($caching_servers)) {
	foreach ($caching_servers as $cache_server_id => $cache_server_data) {
		CachingServers::addServer($cache_server_id, $cache_server_data['name'], $cache_server_data['ip'], $cache_server_data['port']);
	}
} else {
	CachingServers::addServer(0, 'Main Redis Server', REDIS_IP, REDIS_PORT);
}

/*
			Список всех возможных ключей с описаниями:

			- Где хранятся галеры (для удаления):

			Локальные параметры галлереи, включая тип. : hMset, local_gal_id
				$key_prefix."gallery:local:" . $local_gal_id

				Связка локальный-глобальный ID галлереи
				hMset local_id:global_id
					$key_prefix."galleries"
					
				hMset global_id:local_id
					$key_prefix."galleries:no_local"

				Списки галлерей : zAdd, local_gal_id:date
					$key_prefix."galleries_by_date"
					$key_prefix."galleries_by_date:pics"
					$key_prefix."galleries_by_date:movies"	

				Списки галлерей : zAdd, local_gal_id: лайк/пейджвью
					$gallery_pageviews_key = $key_prefix."galleries:pageviews"
					$gallery_pageviews_key = $key_prefix."galleries:pageviews:pics"
					$gallery_pageviews_key = $key_prefix."galleries:pageviews:movies"
					$gallery_pageviews_key = $key_prefix."galleries:likes_key"
					$gallery_pageviews_key = $key_prefix."galleries:likes_key:pics"
					$gallery_pageviews_key = $key_prefix."galleries:likes_key:movies"				
					$total_likes_key = $key_prefix .":total_likes";

				Теги сайта по алфавиту, метод: zAdd, gal_id:счетчик
					$this->key_prefix . "list:tags:all"
					$this->key_prefix . "list:tags:pics"
					$this->key_prefix . "list:tags:movies"

				Галлерей тега, отсортировано по дате: zAdd, gal_id:дата добавления галлереи
					$key_prefix."tag_galleries_by_date:all:"
					$key_prefix."tag_galleries_by_date:pics:"
					$key_prefix."tag_galleries_by_date:movies:"

				Платники сайта по алфавиту, метод: zAdd, gal_id:счетчик
					$key_prefix."sources:all"
					$key_prefix."sources:pics"
					$key_prefix."sources:movies"
				
				Галлерей платника, отсортировано по дате: zAdd, gal_id:дата добавления галлереи
					$key_prefix."source_galleries_by_date:all:"
					$key_prefix."source_galleries_by_date:pics:"
					$key_prefix."source_galleries_by_date:movies:"

				Галлерей платника, отсортировано по кликам: zAdd 0, gal_id:дата добавления галлереи
					$key_prefix."source_galleries:all:"
					$key_prefix."source_galleries:pics:"
					$key_prefix."source_galleries:movies:"

				Списки моделей по алфавиту : zAdd, model_id: счетчик
					$all_site_models_key = $key_prefix."models:all" счетчик
					$pics_site_models_key = $key_prefix."models:pics" счетчик
					$movies_site_models_key = $key_prefix."models:movies"  счетчик

				Списки моделей : zAdd, model_id: лайки/пейджвью (не относится к галерам, разве что если удаляется галера, и больше нет галер с моделью)
					$site_models_likes = $key_prefix."models:likes";
					$site_models_pageviews = $key_prefix."models:pageviews";

				Галлереи модели : zAdd, local_gal_id: дата
					$model_galleries_prefix = $key_prefix."model_galleries:all:";
					$model_pics_galleries_prefix = $key_prefix."model_galleries:pics:";
					$model_movies_galleries_prefix = $key_prefix."model_galleries:movies:";

			Удаление галлереи из кеша:

				1. Собирается список сайтов где галера запощена
				2. Собирается список тегов
				3. Собирается список моделей
				4. Отдельно номер платника/источника

				5. Для каждого сайта:
					1. Ищется локальный айди
					2. ИД переименовывается в delete_gal_id
					3. Удаляется локальная информация галлереи
					4. Удаляется из источника (по дате, по кликам)
						Источник передается на пересборку
					5. Удаляется из каждого тега (по дате)
						Тег передается на пересборку
					6. Удаляется из каждогой модели (по дате)
						Модель передается на пересборку
					7. Удаляется из пейджвью 
					8. Удаляется из лайков 
					9. Удаляется из "по дате"
					10. удаляется ИД из базы локальные и не_локальные

				6. Удаляется глобальный кеш


*/

class CacheRebuilder
{

	private $connected;
	private $redis;

	private $r_pipeline = false;

	private static $used_galleries_array = array();

	function __construct($db_connect = false, $redis_server = 0)
	{
		$this->connected = false;
		$this->_db = false;
		$this->sites = false;
		$this->gallery = false;
		$this->tags = false;
		$this->models = false;
		$this->sources = false;
		$this->current_server = intval($redis_server);

		$this->_db = $db_connect;
		$this->current = intval($redis_server);

		$this->default_prefix = SCRIPT_PRE . ":";
		$this->galleries_tagged_counter_prefix = $this->default_prefix . "worker_tagged_counter:";

		// $log = new Logger ("PID #".getmypid().", ".__FUNCTION__.", Current server: ".$this->current_server." Init strat: ". $_SERVER['REMOTE_ADDR'], true);
		$this->redis_connection($redis_server);

		// if(!$db_connect) {
		// 	$db = new db_access();
		// 	$this->_db = $db->_db;
		// 	$db_connect = $this->_db;
		// }
		if ($db_connect) {
			$this->sites = new Sites($this->_db);
			$this->gallery = new Galleries($this->_db);
			$this->tags = new Tags($this->_db);
			$this->models = new CModels($this->_db);
			$this->sources = new Sources($this->_db);
		}
	}



	private function galleryGlobalKey($id)
	{
		return $this->default_prefix . "gallery:" . intval($id);
	}
	private function modelGlobalKey($id)
	{
		return $this->default_prefix . "model:" . intval($id);
	}
	private function siteGlobalKey($id)
	{
		return $this->default_prefix . "site:" . intval($id);
	}
	private function sourceGlobalKey($id)
	{
		return $this->default_prefix . "source:" . intval($id);
	}
	// база тегов есть и по имени, и по айди (дублированная, следует пересмотреть необходимость)
	private function tagGlobalKey($id)
	{
		return $this->default_prefix . "tag:" . $id;
	}

	private function modelsDBKey()
	{
		return $this->default_prefix . "models";
	}
	private function galleriesDBKey()
	{
		return $this->default_prefix . "galleries";
	}
	private function tagsDBKey()
	{
		return $this->default_prefix . "tags";
	}

	// смена с tagsDBKey siteTagsDBKey
	private function siteTagsDBKey($site_id)
	{
		return $this->default_prefix . (int)$site_id . ":unique_tags";
	}
	// tagGlobalKey
	private function siteUniqueTagKey($site_id, $tag_id)
	{
		return $this->default_prefix . (int)$site_id . ":unique_tag:" . $tag_id;
	}


	private function sourcesDBKey()
	{
		return $this->default_prefix . "sources";
	}

	private function galleryLocalInfoKey($site_id, $gal_id)
	{ // $gallery_local_key_prefix
		$site_id = intval($site_id);
		$gal_id = intval($gal_id);
		$key_prefix = $this->default_prefix . $site_id . ":";
		return 	$key_prefix . "gallery:local:" . $gal_id;
	}

	private function localGalleriesDB($site_id)
	{ // ключ списка local_id:global_id
		$key_prefix = $this->default_prefix . $site_id . ":";
		$key = $key_prefix . "galleries";
		return $key;
	}

	private function no_localGalleriesDB($site_id)
	{ // ключ списка global_id:local_id
		$key_prefix = $this->default_prefix . $site_id . ":";
		$key = $key_prefix . "galleries:no_local";
		return $key;
	}

	// list_of = galleries | tags | sources | models || model_galleries, |tag_galleries | source_galleries
	// sort_type = date | pageviews | likes | name | count
	// content_type = all | pics | movies | embed
	// site_id
	// item_id
	private function keyFor($list_of, $sort_type, $content_type, $site_id, $item_id = false)
	{
		$list_of = strtolower($list_of);
		$sort_type = strtolower($sort_type);
		$content_type = strtolower($content_type);
		$site_id = intval($site_id);
		if ($content_type == 'movie' || $content_type == 'video' || $content_type == 'videos') $content_type = 'movies';
		elseif ($content_type == 'gif') $content_type = 'gif';
		if (
			!preg_match("#(galleries|tags|sources|models|model_galleries|tag_galleries|source_galleries|likes_galleries)#", $list_of) ||
			!preg_match("#(date|pageviews|likes|name|count|array|rating)#", $sort_type) ||
			!preg_match("#(all|pics|movies|embed|gif)#", $content_type) ||
			!$site_id
		) return NULL;

		/* 
			//new				
			$key_prefix = $this->default_prefix . $site_id .":list_of";
			$key = $key_prefix .':'. $list_of .':'. $sort_type .':'. $content_type .':'. $site_id;
			if (preg_match("#(model_galleries|tag_galleries|source_galleries)#", $list_of)) {
				if (intval($item_id)) $key .= $item_id;
				else return NULL;
			} 
			*/
		$key_prefix = $this->default_prefix . $site_id . ":";
		if ($list_of == 'tags') {
			if ($sort_type == 'array') $key = $key_prefix .= "list:tags:array:" . $content_type;
			elseif ($sort_type == 'name') $key = $key_prefix .= "list:tags:" . $content_type;
			elseif ($sort_type == 'pageviews') $key = $key_prefix .= "list:tags:pageviews:" . $content_type;
			else $key = NULL;
		} elseif ($list_of == 'sources') {
			if ($sort_type == 'name') $key = $key_prefix .= "sources:" . $content_type;
			elseif ($sort_type == 'pageviews') $key = $key_prefix .= "sources:pageviews:" . $content_type;
			else $key = NULL;
		} elseif ($list_of == 'models') {
			if ($sort_type == 'name') $key = $key_prefix .= "models:" . $content_type;
			elseif ($sort_type == 'pageviews') $key = $key_prefix .= "models:pageviews";
			elseif ($sort_type == 'likes') $key = $key_prefix .= "models:likes";
			else $key = NULL;
		} elseif ($list_of == 'galleries') {
			if ($sort_type == 'date') {
				$key = $key_prefix .= "galleries_by_date";
				if ($content_type != 'all') $key .= ":" . $content_type;
			} elseif ($sort_type == 'pageviews') {
				$key = $key_prefix .= "galleries:pageviews";
				if ($content_type != 'all') $key .= ":" . $content_type;
			} elseif ($sort_type == 'likes') {
				$key = $key_prefix .= "galleries:likes";
				if ($content_type != 'all') $key .= ":" . $content_type;
			} elseif ($sort_type == 'rating') {
				$key = $key_prefix .= "galleries:rating";
				if ($content_type != 'all') $key .= ":" . $content_type;
			} else $key = NULL;
		} elseif ($list_of == 'model_galleries') {
			if ($sort_type == 'date' && intval($item_id)) $key = $key_prefix .= "model_galleries:" . $content_type . ":" . intval($item_id);
			else $key = NULL;
		} elseif ($list_of == 'tag_galleries') {
			if ($sort_type == 'date' && intval($item_id)) $key = $key_prefix .= "tag_galleries_by_date:" . $content_type . ":" . intval($item_id);
			else $key = NULL;
		} elseif ($list_of == 'source_galleries') {
			if ($sort_type == 'date' && intval($item_id)) $key = $key_prefix .= "source_galleries_by_date:" . $content_type . ":" . intval($item_id);
			elseif ($sort_type == 'pageviews' && intval($item_id)) $key = $key_prefix .= "source_galleries:" . $content_type . ":" . intval($item_id);
			else $key = NULL;
		} elseif ($list_of == 'likes_galleries') { // общие списки лайков likes_key

			if ($content_type == 'pics') $key = $key_prefix .= "likes_key:pics";
			elseif ($content_type == 'movies') $key = $key_prefix .= "likes_key:movies";
			elseif ($content_type == 'gif') $key = $key_prefix .= "likes_key:gif";
			elseif ($content_type == 'all') $key = $key_prefix .= "likes_key";
			else $key = NULL;
		}

		if ($key == NULL) {
			$log = new Logger(__METHOD__ . ": Битый ключ, входящие данные: list_of :'" . $list_of . "', sort_type :'" . $sort_type . "', list_of :'" . $list_of . "', site_id :'" . $site_id . "', item_id :'" . $item_id . "' ", true);
		}
		return $key;
	}

	/*
					list_of: // без участия ID галлерей
						tags, name, type "all - all"
						OLD:
							$this->key_prefix . "list:tags:all"
							$this->key_prefix . "list:tags:pics"
							$this->key_prefix . "list:tags:movies"

*/
	public function renameOldCacheListOfTags($site_id)
	{
		$old_key = $this->default_prefix . $site_id . ":list:tags:all";
		$new_key = $this->keyFor('tags', 'name', 'all', $site_id);
		var_dump($old_key);
		var_dump($this->redis->exists($old_key));
		var_dump($new_key);
		var_dump($this->redis->exists($new_key));
	}
	/*					

						tags, pageviews, type "all - all"

						sources, name, type "all - all"
						OLD:
							$key_prefix."sources:all"
							$key_prefix."sources:pics"
							$key_prefix."sources:movies"

						sources, pageviews, type "all - all"

						models, by_name, type "all - all "
						OLD:
							$all_site_models_key = $key_prefix."models:all" счетчик
							$pics_site_models_key = $key_prefix."models:pics" счетчик
							$movies_site_models_key = $key_prefix."models:movies"  счетчик

						models, likes, type "all - all"
							$site_models_likes = $key_prefix."models:likes";
				
						models, pageviews, type "all - all"
							$site_models_pageviews = $key_prefix."models:pageviews";

					// с участием ID галлереи
						local_ids_first
						global_ids_first

					list_of:
						site:
						galleries, date, type "all - пустой"
						OLD:
							$key_prefix."galleries_by_date"
							$key_prefix."galleries_by_date:pics"
							$key_prefix."galleries_by_date:movies"

						galleries, pageviews, type "all - пустой"
						OLD:
							$gallery_pageviews_key = $key_prefix."galleries:pageviews"
							$gallery_pageviews_key = $key_prefix."galleries:pageviews:pics"
							$gallery_pageviews_key = $key_prefix."galleries:pageviews:movies"

						galleries, likes, type "all - пустой"
						OLD:
							$gallery_pageviews_key = $key_prefix."galleries:likes_key"
							$gallery_pageviews_key = $key_prefix."galleries:likes_key:pics"
							$gallery_pageviews_key = $key_prefix."galleries:likes_key:movies"
						
						model_galleries, date, type, (id) "all - all"	
						OLD:
							Галлереи модели : zAdd, local_gal_id: дата
							$model_galleries_prefix = $key_prefix."model_galleries:all:";
							$model_pics_galleries_prefix = $key_prefix."model_galleries:pics:";
							$model_movies_galleries_prefix = $key_prefix."model_galleries:movies:";

						tag_galleries, date, type, (id) "all - all"
						OLD:
							$key_prefix."tag_galleries_by_date:all:"
							$key_prefix."tag_galleries_by_date:pics:"
							$key_prefix."tag_galleries_by_date:movies:"

						tag_galleries, pageviews, type, (id) "all - all"

						source_galleries, date, type, (id) "all - all"
						OLD: 
							$key_prefix."source_galleries_by_date:all:"
							$key_prefix."source_galleries_by_date:pics:"
							$key_prefix."source_galleries_by_date:movies:"

						source_galleries, pageviews, type, (id) "all - all"
						OLD:
							$key_prefix."source_galleries:all:"
							$key_prefix."source_galleries:pics:"
							$key_prefix."source_galleries:movies:"

*/
	/*
		private function generateCacheKey ($site_id, $element_type, $content_type, $sort_by, $element_id) {
			$site_id = intval($site_id);
			$key_prefix = $this->default_prefix . $site_id.":";	
			$key .= $key_prefix;
			if ($element_type == 'tag') {
				if($sort_by == 'count') $key .= "list:tags:"; // просто список тегов
				elseif($sort_by == 'date') $key .= "tag_galleries_by_date:"; //списки галлерей тега

				$key .= $content_type;

				if($sort_by == 'date') $key .= ":". $element_id;
			} elseif ($element_type == 'source') {
				if($sort_by == 'views') $key .= "source_galleries:";
				elseif($sort_by == 'date') $key .= "source_galleries_by_date:";
				else $key .= "sources:";
				$key .= $content_type;
				if($sort_by == 'date' || $sort_by == 'views') $key .= ":". $element_id;
			} elseif ($element_type == 'gallery') {
				if($sort_by == 'date') $key .= "galleries_by_date"; // $galleries_sorted_by_date
				elseif($sort_by == 'views') $key .= "galleries:pageviews"; //$gallery_pageviews_key = $key_prefix."galleries:pageviews";
				elseif($sort_by == 'likes') $key .= "galleries:likes_key"; //$gallery_pageviews_key = $key_prefix."galleries:pageviews";
				if($content_type == 'pics' || $content_type == 'movies') $key .= ":". $content_type;
			}

			return $key;
		}
		*/


	/*
			Список всех возможных ключей с описаниями:

			- Где хранятся галеры (для удаления):

			Локальные параметры галлереи, включая тип. : hMset, local_gal_id
				$key_prefix."gallery:local:" . $local_gal_id

				Связка локальный-глобальный ID галлереи
				hMset local_id:global_id
					$key_prefix."galleries"
					
				hMset global_id:local_id
					$key_prefix."galleries:no_local"

				Списки галлерей : zAdd, local_gal_id:date
					$key_prefix."galleries_by_date"
					$key_prefix."galleries_by_date:pics"
					$key_prefix."galleries_by_date:movies"	

				Списки галлерей : zAdd, local_gal_id: лайк/пейджвью
					$gallery_pageviews_key = $key_prefix."galleries:pageviews"
					$gallery_pageviews_key = $key_prefix."galleries:pageviews:pics"
					$gallery_pageviews_key = $key_prefix."galleries:pageviews:movies"
					$gallery_pageviews_key = $key_prefix."galleries:likes_key"
					$gallery_pageviews_key = $key_prefix."galleries:likes_key:pics"
					$gallery_pageviews_key = $key_prefix."galleries:likes_key:movies"				
					$total_likes_key = $key_prefix .":total_likes";

				Теги сайта по количеству галлерей, метод: zAdd, gal_id:количество галер
					$this->key_prefix . "list:tags:all"
					$this->key_prefix . "list:tags:pics"
					$this->key_prefix . "list:tags:movies"

				Галлерей тега, отсортировано по дате: zAdd, gal_id:дата добавления галлереи
					$key_prefix."tag_galleries_by_date:all:"
					$key_prefix."tag_galleries_by_date:pics:"
					$key_prefix."tag_galleries_by_date:movies:"

				Платники сайта по количеству галлерей, метод: zAdd, gal_id:количество галер
					$key_prefix."sources:all"
					$key_prefix."sources:pics"
					$key_prefix."sources:movies"
				
				Галлерей платника, отсортировано по дате: zAdd, gal_id:дата добавления галлереи
					$key_prefix."source_galleries_by_date:all:"
					$key_prefix."source_galleries_by_date:pics:"
					$key_prefix."source_galleries_by_date:movies:"

				Галлерей платника, отсортировано по кликам: zAdd 0, gal_id:дата добавления галлереи
					$key_prefix."source_galleries:all:"
					$key_prefix."source_galleries:pics:"
					$key_prefix."source_galleries:movies:"

				Списки моделей : zAdd, model_id: кол-во галер
					$all_site_models_key = $key_prefix."models:all" количество галер модели
					$pics_site_models_key = $key_prefix."models:pics"  количество галер модели
					$movies_site_models_key = $key_prefix."models:movies"  количество галер модели

				Списки моделей : zAdd, model_id: лайки/пейджвью (не относится к галерам, разве что если удаляется галера, и больше нет галер с моделью)
					$site_models_likes = $key_prefix."models:likes";
					$site_models_pageviews = $key_prefix."models:pageviews";

				Галлереи модели : zAdd, local_gal_id: дата
					$model_galleries_prefix = $key_prefix."model_galleries:all:";
					$model_pics_galleries_prefix = $key_prefix."model_galleries:pics:";
					$model_movies_galleries_prefix = $key_prefix."model_galleries:movies:";

						galleries, date, type "all - пустой"
						galleries, pageviews, type "all - пустой"
						galleries, likes, type "all - пустой"

						tags, count, type "all - all"
						tags, date, type "all - all"

						sources, count, type "all - all"
						sources, date, type "all - all"
						sources, pageviews, type "all - all"

						models, count, type "all - all "
						models, likes, type "all - all"
						models, pageviews, type "all - all"
						

						model (id), date, type "all - all"					

		*/

	private function generateTempKey() {}

	public function setServerId($cache_server_id)
	{
		return $this->redis_connection($cache_server_id);
	}

	private function redis_connection($redis_server_id = 0)
	{
		$result = false;
		$this->redis = new Redis();
		$redis_server_id = (int)$redis_server_id;
		$redis_server_ip = false;
		$redis_server_name = false;
		$redis_port = false;

		if (CachingServers::setServer($redis_server_id)) {
			$redis_server_ip = CachingServers::currentIP();
			$redis_server_name = CachingServers::currentName();
			$redis_port = CachingServers::currentPort();
		} else {
			$caching_server_error_msg = CachingServers::getLastErrorMsg();
			$log = new Logger($caching_server_error_msg, true);
			$redis_server_id = false;
			$this->connected = false;
		}

		if ($redis_server_ip && $redis_port) {
			$this->connected = $this->redis->connect($redis_server_ip, $redis_port, 3);
			if ($this->connected) {
				$this->current_server = $redis_server_id;
				$this->redis->select(REDIS_SERVER);
				$result = true;
			} else {
				$log = new Logger("Нет коннекта к редису ID:" . $redis_server_id . ", (" . $redis_server_name . ")", true);
			}
		}
		return $result;
	}

	function checkConnect()
	{
		return $this->connected;
	}

	function server_checkTags($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->checkTags();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->checkTags();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", коннект к редису #" . $this->current_server, true);
		}
		return $result;
	}

	function checkTags()
	{
		$result = false;
		if ($this->connected) {
			$tags_list = $this->tags->getAllTags(false, true);
			$tags_count = count($tags_list);
			$tags_cached = $this->tagsCount();
			if ($tags_count !== false && $tags_count && $tags_count == $tags_cached) $result = true;
		}
		return $result;
	}

	function server_checkModels($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->checkModels();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->checkModels();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", коннект к редису #" . $server, true);
		}
		return $result;
	}

	function checkModels()
	{
		$result = false;
		if ($this->connected) {
			$models_list = $this->models->getAllModels();
			$models_count = count($models_list);
			$models_cached = $this->modelsCount();
			if ($models_count !== false && $models_count && $models_count == $models_cached) $result = true;
		}
		return $result;
	}

	function server_checkSources($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->checkSources();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->checkSources();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", коннект к редису #" . $server, true);
		}
		return $result;
	}

	function checkSources()
	{
		$result = false;
		if ($this->connected) {
			$sources_list = $this->sources->getAllSources(false);
			$sources_count = count($sources_list);
			$sources_cached = $this->sourcesCount();
			if ($sources_count !== false && $sources_count && $sources_count == $sources_cached) $result = true;
		}
		return $result;
	}

	function server_checkGalleries($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->checkGalleries();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->checkGalleries();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", коннект к редису #" . $server, true);
		}
		return $result;
	}

	function checkGalleries()
	{
		$result = false;
		if ($this->connected) {
			$galleries_count = $this->gallery->countGalleries();
			$galleries_cached = $this->galleriesCount();
			if ($galleries_count !== false && $galleries_count && $galleries_count == $galleries_cached) $result = true;
		}
		return $result;
	}

	function server_siteStats($site_id, $year, $month, $day)
	{
		$result = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		$server_changed = false;
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				if (!$this->connected) $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				//echo "switch to ".$site_redis_server;
			}
			$result = $this->siteStats($site_id, $year, $month, $day);
			if ($server_changed) $this->redis_connection($previous_server);
		}

		return $result;
	}


	function getKeysList()
	{
		var_dump($this->default_prefix . '*');
		$dates = $this->redis->keys($this->default_prefix . '*');
		foreach ($dates as $date_key) {
			echo $date_key . "<br>";
		}
	}

	function cutTotalPageviews($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		$server_changed = false;
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				if (!$this->connected) $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				//echo "switch to ".$site_redis_server;
			}
			$key_prefix = $this->default_prefix . $site_id . ":";
			$total_pageviews_key = $key_prefix . ":total_pageviews";
			$thirty_days_in_secs = 2592000; //30*24*60*60
			$current_time = time();
			$cut_from = $current_time - $thirty_days_in_secs;
			$cut_to = 0;
			$this->redis->zRemRangeByScore($total_pageviews_key, $cut_to, $cut_from);
			if ($server_changed) $this->redis_connection($previous_server);
		}
	}

	function cutUsersDB($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		$server_changed = false;
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				if (!$this->connected) $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				//echo "switch to ".$site_redis_server;
			}
			$key_prefix = $this->default_prefix . $site_id . ":";
			$total_users_db_key = $key_prefix . ":db:visitors";
			$thirty_days_in_secs = 2592000; //30*24*60*60
			$current_time = time();
			$cut_from = $current_time - $thirty_days_in_secs;
			$cut_to = 0;
			$this->redis->zRemRangeByScore($total_users_db_key, $cut_to, $cut_from);
			if ($server_changed) $this->redis_connection($previous_server);
		}
	}


	function cutStatsToWeek($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		$server_changed = false;
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				if (!$this->connected) $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			}

			$use_site_id = $this->sites->getUseGalleriesFrom() ? $this->sites->getUseGalleriesFrom() : $site_id;

			$key_prefix = $this->default_prefix . $use_site_id . ":";
			$total_pageviews_key = $key_prefix . ":total_pageviews";
			$total_users_db_key = $key_prefix . "db:visitors";

			$thirty_days_in_secs = 2592000; //30*24*60*60
			$week_in_secs = 604800;
			$current_time = time();
			$cut_from = $current_time - $week_in_secs;
			$cut_to = 0;
			echo "<h3>objects in table prior cut</h3>" .
				$total_pageviews_key . ":" . $this->redis->zCard($total_pageviews_key) .
				$total_users_db_key . ":" . $this->redis->zCard($total_users_db_key) . "<br>";

			$this->redis->zRemRangeByScore($total_pageviews_key, $cut_to, $cut_from);
			$this->redis->zRemRangeByScore($total_users_db_key, $cut_to, $cut_from);

			echo "<h3>objects in table after cut</h3>" .
				$total_pageviews_key . ":" . $this->redis->zCard($total_pageviews_key) .
				$total_users_db_key . ":" . $this->redis->zCard($total_users_db_key) . "<br>";

			if ($server_changed) {
				$this->redis_connection($previous_server);
			}
		}
	}

	function siteStats($site_id, $year, $month, $day)
	{
		$from_date = date("Y-m-d", time());
		$key_prefix = $this->default_prefix . $site_id . ":";
		$total_pageviews_key = $key_prefix . ":total_pageviews";
		$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
		$gallery_likes_key = $key_prefix . "likes_key";
		$visitors_db_key = $key_prefix . "visitors:" . $from_date;
		$visitors_db_key_prefix = $key_prefix . "visitor:";
		$total_likes_key = $key_prefix . ":total_likes";
		$gallery_today_likes_key = $gallery_likes_key . ":" . $from_date;
		$total_users_db_key = $key_prefix . "db:visitors";
		$result = false;
		if ($this->connected) {
			$result = array();
			$result['qualified_pageviews'] = $this->redis->zCard($total_pageviews_key);
			$result['qualified_pageviews_today'] = $this->redis->zCount($total_pageviews_key, (time() - (24 * 60 * 60)), time());
			$result['likes_added'] = $this->redis->zCard($total_likes_key);
			$result['likes_added_today'] = $this->redis->zCard($gallery_today_likes_key);
			$result['likes_added_today_new'] = $this->redis->zCount($total_likes_key, (time() - (24 * 60 * 60)), time());
			$result['nonqualified_pageviews_today'] = $this->redis->llen($visitors_db_key);
			$result['total_unique_users'] = $this->redis->zCard($total_users_db_key);
			$result['total_unique_users_today'] = $this->redis->zCount($total_users_db_key, (time() - (24 * 60 * 60)), time());
		} else {
			echo "No redis connection";
		}


		return $result;
	}

	function server_getLikes($site_id, $server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->getLikes($site_id);
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->getLikes($site_id);
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", коннект к редису #" . $server, true);
		}
		return $result;
	}

	function getLikes($site_id)
	{
		$key_prefix = $this->default_prefix . $site_id . ":";
		$gallery_likes_key = $key_prefix . "likes_key";
		return $this->redis->zRevRange($gallery_likes_key);
	}

	function getLikesFullInfo($site_id)
	{
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		$server_changed = false;
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				if (!$this->connected) $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				//echo "switch to ".$site_redis_server;
			}
			$key_prefix = $this->default_prefix . $site_id . ":";
			$total_likes_key = $key_prefix . ":total_likes";
			$stats = $this->redis->zRevRange($total_likes_key, 0, -1);
			foreach ($stats as $stat) {
				$stat_elements = explode("_", $stat);
				$gal_id = $stat_elements[0];
				$time = $stat_elements[1];
				$user_key = $stat_elements[2];
				$gallery_likes_users_key = $key_prefix . "likes:users:" . $gal_id;
				$user_info = $this->redis->hGetAll($gallery_likes_users_key);
				echo "Gal #" . $gal_id . "|" . date("Y-m-d, G:i:s", $time) . "|" . $user_info[$user_key] . "<br>";
			}
			if ($server_changed) $this->redis_connection($previous_server);
		}
	}


	function getPageviews($site_id)
	{
		$key_prefix = $this->default_prefix . $site_id . ":";
		$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
		// var_dump($gallery_pageviews_key);
		// var_dump($this->keyFor('galleries','pageviews','all'));
		return $this->redis->zRevRange($gallery_pageviews_key);
	}


	private function siteModelExists($site_id, $model_id)
	{
		return $this->redis->exists($this->keyFor('model_galleries', 'date', 'all', $site_id, $model_id));
	}

	private function siteModelDelete($site_id, $model_id)
	{
		$pipeline = $this->redis->multi(Redis::PIPELINE);
		$pipeline->zRem($this->keyFor('models', 'name', 'all', $site_id), $model_id);
		$pipeline->zRem($this->keyFor('models', 'name', 'pics', $site_id), $model_id);
		$pipeline->zRem($this->keyFor('models', 'name', 'movies', $site_id), $model_id);
		$pipeline->zRem($this->keyFor('models', 'name', 'gif', $site_id), $model_id);
		$pipeline->zRem($this->keyFor('models', 'likes', 'all', $site_id), $model_id);
		$pipeline->zRem($this->keyFor('models', 'pageviews', 'all', $site_id), $model_id);
		$pipeline->exec();
	}

	private function siteTagExists($site_id, $tag_id)
	{
		return $this->redis->exists($this->keyFor('tag_galleries', 'date', 'all', $site_id, $tag_id));
	}

	private function siteTagDelete($site_id, $tag_id)
	{
		$pipeline = $this->redis->multi(Redis::PIPELINE);
		$pipeline->zRem($this->keyFor('tags', 'name', 'all', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'name', 'pics', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'name', 'movies', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'name', 'gif', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'pageviews', 'all', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'pageviews', 'pics', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'pageviews', 'movies', $site_id), $tag_id);
		$pipeline->zRem($this->keyFor('tags', 'pageviews', 'gif', $site_id), $tag_id);
		$pipeline->exec();
	}
	function server_getGalleryPageviews($site_id, $gal_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$server_changed = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			$result = $this->getGalleryPageviews($site_id, $gal_id);
			if ($server_changed) $this->redis_connection($previous_server);
		}
		return $result;
	}

	function getGalleryPageviews($site_id, $gal_id)
	{
		$result = false;
		if ($this->connected) {
			$site_id = intval($site_id);
			$gal_id = intval($gal_id);
			$key_prefix = $this->default_prefix . $site_id . ":";

			$gallery_pageviews_key = $this->keyFor('galleries', 'pageviews', 'all', $site_id);
			$result = $this->redis->zScore($gallery_pageviews_key, $gal_id);
		}
		$result = intval($result);
		return $result;
	}

	function getSitePageviewsAll($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$server_changed = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$galleries = $this->sites->getSiteGalleries_new(false);
			if ($galleries) {
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}

				$pipeline = $this->redis->multi(Redis::PIPELINE);

				$count = 0;

				foreach ($galleries as $gallery) {
					$gallery_pageviews_key = $this->keyFor('galleries', 'pageviews', 'all', $site_id);
					$pipeline->zScore($gallery_pageviews_key, $gallery);
					$gallery_res[$count] = $gallery;
					$count++;
				}
				$result = $pipeline->exec();

				unset($galleries);

				$count = 0;

				foreach ($result as $gal) {
					echo $gallery_res[$count] . ":" . $gal . "<br>";
					$count++;
				}
				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	function sitePageviewsToDb($site_id, $force = false)
	{
		$result = false;
		$site_id = intval($site_id);
		$server_changed = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$galleries = $this->sites->getGalleriesPageviews();
			if ($galleries) {
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				$count = 0;
				foreach ($galleries as $gallery => $pageviews) {
					$gallery_pageviews_key = $this->keyFor('galleries', 'pageviews', 'all', $site_id);
					$pipeline->zScore($gallery_pageviews_key, $gallery);
					$gallery_res[$count] = $gallery;
					$count++;
				}
				$result = $pipeline->exec();
				$count = 0;
				foreach ($result as $cache_pageviews) {
					$pageviews = $galleries[$gallery_res[$count]];
					if ($force || $cache_pageviews > $pageviews) {
						$this->sites->updatePageviews($gallery_res[$count], $cache_pageviews);
						echo $count . ":" . $gallery_res[$count] . ":" . $cache_pageviews . "<br>";
					}
					$count++;
				}
				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	function siteLikesToDb($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$server_changed = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$galleries = $this->sites->getGalleriesLikes();
			if ($galleries) {
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				$count = 0;
				foreach ($galleries as $gallery => $likes) {
					$gallery_likes_key = $this->keyFor('likes_galleries', 'likes', 'all', $site_id);
					echo $this->keyFor('likes_galleries', 'likes', 'all', $site_id) . ", $gallery<br>";
					$pipeline->zScore($gallery_likes_key, $gallery);
					$gallery_res[$count] = $gallery;
					$count++;
				}
				$result = $pipeline->exec();
				$count = 0;
				foreach ($result as $cache_likes) {
					$likes = $galleries[$gallery_res[$count]];
					echo $gallery_res[$count] . ":" . $cache_likes . "<br>";
					if ($cache_likes > $likes) {
						$this->sites->updateLikes($gallery_res[$count], $cache_likes);
						echo $count . ":" . $gallery_res[$count] . ":" . $cache_likes . "<br>";
					}
					$count++;
				}
				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	function siteModelsPageviewsToDb($site_id, $force = false)
	{
		$result = false;
		$site_id = intval($site_id);
		$server_changed = false;
		$site_switched = $this->sites->switchSite($site_id);
		// var_dump($site_switched);
		if ($site_switched) {
			$galleries = $this->models->getModelsPageviews($site_id);
			// var_dump($galleries);
			if ($galleries) {
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				$count = 0;
				foreach ($galleries as $gallery => $pageviews) {
					$gallery_pageviews_key = $this->keyFor('models', 'pageviews', 'all', $site_id);
					$pipeline->zScore($gallery_pageviews_key, $gallery);
					$gallery_res[$count] = $gallery;
					$count++;
				}
				$result = $pipeline->exec();
				$count = 0;
				foreach ($result as $cache_pageviews) {
					$pageviews = $galleries[$gallery_res[$count]];
					if ($force || $cache_pageviews > $pageviews) {
						$this->models->setModelPageviews($gallery_res[$count], $site_id, $cache_pageviews);
						echo "Set: Model #" . $gallery_res[$count] . ", DB pageviews <b>" . $galleries[$gallery_res[$count]] . "</b>, cache pageviews: <b>" . $cache_pageviews . "</b><br>";
					}
					$count++;
				}
				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	function siteModelLikesToDb($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$server_changed = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$galleries = $this->models->getModelsLikes($site_id);
			// var_dump($galleries);
			if (!$galleries) {
				$galleries_x = $this->sites->getSiteModelsList($site_id);
				if ($galleries_x) {
					foreach ($galleries_x as $model_id => $model) {
						$galleries[$model_id] = 0;
					}
				}
			}
			// var_dump($galleries);
			if ($galleries) {
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				$count = 0;
				foreach ($galleries as $gallery => $likes) {
					$gallery_likes_key = $this->keyFor('models', 'likes', 'all', $site_id);
					// echo $this->keyFor('likes_galleries','likes','all', $site_id).", $gallery<br>";
					$pipeline->zScore($gallery_likes_key, $gallery);
					$gallery_res[$count] = $gallery;
					$count++;
				}
				$result = $pipeline->exec();
				$count = 0;
				foreach ($result as $cache_likes) {
					$likes = $galleries[$gallery_res[$count]];

					if ($cache_likes > $likes) {
						$this->models->setModelLikes($gallery_res[$count], $site_id, $cache_likes);
						echo "Set: Model #" . $gallery_res[$count] . ", DB likes <b>" . $galleries[$gallery_res[$count]] . "</b>, cache likes <b>" . $cache_likes . "</b><br>";
						$log = new Logger(__METHOD__ . ": Model #" . $gallery_res[$count] . ", DB likes: " . $galleries[$gallery_res[$count]] . ", cache likes: " . $cache_likes);
						// echo $count .":".$gallery_res[$count].":".$cache_likes."<br>";
					}
					$count++;
				}
				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	// можно переделать и под галеры, и под теги и статы остальные.. имхо лучший вариант чем полный перебор, как в текущее время
	// при синхроне полный пиздец идет

	// $site_id
	// @int
	// $update_object
	// @ galleries|models|tags|sources
	// $update_what
	// @ likes|pageviews
	function updateStatsInDb($site_id, $update_object, $update_what)
	{
		$result = false;

		// var_dump($type, $site_id);
		if (
			preg_match("#^(likes|pageviews)$#", $update_what)
			&& preg_match("#^(galleries|models|tags|sources)$#", $update_object)
		) {
			$site_id = (int)$site_id;



			$site_switched = $this->sites->switchSite($site_id);
			if ($site_switched) {
				$server_changed = false;
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}

				if ($update_object == 'galleries' && $update_what == 'likes') {
					$gallery_change_key = $this->keyFor('likes_galleries', $update_what, 'all', $site_id) . ":temp";
				} else {
					$gallery_change_key = $this->keyFor($update_object, $update_what, 'all', $site_id) . ":temp"; // добавить временный ключ, и ключ синхронизации
				}

				$gallery_change_synch_key = $gallery_change_key . ":synch";

				$gallery_change_exist = $this->redis->exists($gallery_change_key);
				$synch_key_exists = $this->redis->exists($gallery_change_synch_key);

				$count_result_number_in_array = 1;
				// var_dump($type, $synch_key_exists || $gallery_change_exist);
				if ($synch_key_exists || $gallery_change_exist) {
					$pipeline = $this->redis->multi(Redis::PIPELINE);
					if (!$synch_key_exists && $gallery_change_exist) {
						$pipeline->rename($gallery_change_key, $gallery_change_synch_key);
						$count_result_number_in_array = 2;
					}

					$pipeline->expire($gallery_change_synch_key, 360);
					$pipeline->zCard($gallery_change_synch_key);
					$pipeline_result = $pipeline->exec();


					if ($pipeline_result) {
						$models_changed_count = $pipeline_result[$count_result_number_in_array];

						$xx = $this->redis->zRange($gallery_change_synch_key, 0, -1, true);
						$scr_start = get_time();
						if ($update_object == 'models') {
							if ($update_what == 'likes') {
								foreach ($xx as $model_id => $changes) {
									$this->models->updateModelLikes($model_id, $site_id, $changes);
									$remove_model_from_temp_list[] = $model_id;
								}
							} else {
								foreach ($xx as $model_id => $changes) {
									$this->models->updateModelPageviews($model_id, $site_id, $changes);
									$remove_model_from_temp_list[] = $model_id;
								}
							}
						} elseif ($update_object == 'galleries') {
							if ($update_what == 'likes') {
								foreach ($xx as $gal_id => $changes) {
									$this->sites->updateSiteLikes($gal_id, $site_id, $changes);
									$remove_model_from_temp_list[] = $gal_id;
								}
							} else {
								$this->sites->pageviewsMassUpdateTransactionStart($site_id);
								foreach ($xx as $gal_id => $changes) {
									// $this->sites->updateSitePageviews($gal_id, $site_id, $changes);
									$remove_model_from_temp_list[] = $gal_id;
									$this->sites->pageviewsMassUpdateTransactionAddPageview($gal_id, $changes);
								}
								$this->sites->pageviewsMassUpdateTransactionExecute();
							}
						}

						$scr_finish = get_time();
						$scr_exec_time = $scr_finish - $scr_start;
						$log = new Logger("Site #" . $site_id . ", " . __METHOD__ . " SQL exec time:" . $scr_exec_time, false, true);

						if (isset($remove_model_from_temp_list) && $remove_model_from_temp_list) {
							$this->redis->del($gallery_change_synch_key);
						}
						$log_text = __METHOD__ . ": С сайта #" . $site_id . " проапдейчено " . $models_changed_count . " " . $update_object . " с ";
						if ($update_what == 'likes') {
							$log_text .= "лайками";
						} else {
							$log_text .= "просмотрами";
						}
						$log = new Logger($log_text);
					}
				}


				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	function updateSitesGalleriesLikes($site_id)
	{
		return $this->updateStatsInDb($site_id, 'galleries', 'likes');
	}

	function updateSitesGalleriesPageviews($site_id)
	{
		return $this->updateStatsInDb($site_id, 'galleries', 'pageviews');
	}

	function changeModelsStats($site_id, $type)
	{
		$result = false;

		// var_dump($type, $site_id);

		if ($type == 'likes' || $type == 'pageviews') {
			$site_id = (int)$site_id;



			$site_switched = $this->sites->switchSite($site_id);
			if ($site_switched) {
				$server_changed = false;
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}

				$gallery_change_key = $this->keyFor('models', $type, 'all', $site_id) . ":temp"; // добавить временный ключ, и ключ синхронизации
				$gallery_change_synch_key = $gallery_change_key . ":synch";

				$gallery_change_exist = $this->redis->exists($gallery_change_key);
				$synch_key_exists = $this->redis->exists($gallery_change_synch_key);

				$count_result_number_in_array = 1;
				// var_dump($type, $synch_key_exists || $gallery_change_exist);
				if ($synch_key_exists || $gallery_change_exist) {
					$pipeline = $this->redis->multi(Redis::PIPELINE);
					if (!$synch_key_exists && $gallery_change_exist) {
						$pipeline->rename($gallery_change_key, $gallery_change_synch_key);
						$count_result_number_in_array = 2;
					}

					$pipeline->expire($gallery_change_synch_key, 360);
					$pipeline->zCard($gallery_change_synch_key);
					$pipeline_result = $pipeline->exec();


					if ($pipeline_result) {
						$models_changed_count = $pipeline_result[$count_result_number_in_array];

						$xx = $this->redis->zRange($gallery_change_synch_key, 0, -1, true);

						if ($type == 'likes') {
							foreach ($xx as $model_id => $changes) {
								$this->models->updateModelLikes($model_id, $site_id, $changes);
								$remove_model_from_temp_list[] = $model_id;
							}
						} else {
							foreach ($xx as $model_id => $changes) {
								$this->models->updateModelPageviews($model_id, $site_id, $changes);
								$remove_model_from_temp_list[] = $model_id;
							}
						}

						if ($remove_model_from_temp_list) {
							$this->redis->del($gallery_change_synch_key);
						}
						$log_text = __METHOD__ . ": С сайта #" . $site_id . " проапдейчено " . $models_changed_count . " моделей с ";
						if ($type == 'likes') {
							$log_text .= "лайками";
						} else {
							$log_text .= "просмотрами";
						}
						$log = new Logger($log_text);
					}
				}


				if ($server_changed) $this->redis_connection($previous_server);
			}
		}
		return $result;
	}

	function modelsPageviewsChangeToDb($site_id)
	{
		return $this->changeModelsStats($site_id, 'pageviews');
	}

	function modelsLikesChangeToDb($site_id)
	{
		return $this->changeModelsStats($site_id, 'likes');
	}
	/*
		function generateSitePageviewsAll($site_id) {
			$result = false;
			$site_id = intval($site_id);
			$server_changed = false;
			$site_switched = $this->sites->switchSite($site_id);
			if ($site_switched) {
				$galleries = $this->sites->getSiteGalleries_new(false);
				if ($galleries) {
					$site_redis_server = $this->sites->redisServer();
					if ($this->current_server != $site_redis_server) {
						$server_changed = true;
						$previous_server = $this->current_server;
						$this->redis_connection($site_redis_server);
					}	
					$pipeline = $this->redis->multi(Redis::PIPELINE);		
					$count = 0;
					foreach($galleries as $gallery) {
						$gallery_pageviews_key = $this->keyFor('galleries','pageviews','all', $site_id);
						$pipeline->zIncrBy($gallery_pageviews_key, rand(0,9000),$gallery);
						$gallery_res[$count] = $gallery;
						$count++;
					}
					$result = $pipeline->exec();
					unset($galleries);
				}
				
			}
			return $result;				
		}
*/

	function initializeSiteTags($site_id)
	{
		$result = false;
		$server_changed = false;
		$previous_server = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {

			$all_tags = $this->sites->getSiteTagsList($site_id);
			$pics_tags = $this->sites->getSiteTagsList($site_id, false, 'pics');
			$movies_tags = $this->sites->getSiteTagsList($site_id, false, 'movies');
			$gifs_tags = $this->sites->getSiteTagsList($site_id, false, 'gif');
			// var_dump($all_tags);
			$all_tags_count = count($all_tags);
			$pics_tags_count = count($pics_tags);
			$movies_tags_count = count($movies_tags);
			$gifs_tags_count = count($gifs_tags);

			$key_prefix = $this->default_prefix . $site_id . ":";

			$all_site_tags_key = $this->keyFor('tags', 'name', 'all', $site_id);
			$pics_site_tags_key = $this->keyFor('tags', 'name', 'pics', $site_id);
			$movies_site_tags_key = $this->keyFor('tags', 'name', 'movies', $site_id);
			$gifs_site_tags_key = $this->keyFor('tags', 'name', 'gif', $site_id);



			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$tag_info = array();
				$pics_tag_info = array();
				$movies_tags_info = array();
				$gifs_tags_info = array();
				$count = 0;
				$all_site_temp_key = $all_site_tags_key . ":temp:" . getmypid();
				$pics_site_temp_key = $pics_site_tags_key . ":temp:" . getmypid();
				$movies_site_temp_key = $movies_site_tags_key . ":temp:" . getmypid();
				$gifs_site_temp_key = $gifs_site_tags_key . ":temp:" . getmypid();

				$pipeline = $this->redis->multi(Redis::PIPELINE);

				if (is_array($all_tags) && count($all_tags) > 0) {
					$count = 0;
					foreach ($all_tags as $tag) {
						$count++;
						$tag_galleries = $this->sites->getTagsGalleries($tag['id']);
						if (is_array($tag_galleries) && count($tag_galleries) > 0) {
							foreach ($tag_galleries as $gallery) {
								$pipeline->zAdd($this->keyFor('tag_galleries', 'date', 'all', $site_id, $tag['id']), floatval($gallery['time_added']), $gallery['id']);
								$pipeline->zAdd($this->keyFor('tag_galleries', 'date', $gallery['gal_type'], $site_id, $tag['id']), floatval($gallery['time_added']), $gallery['id']);
							}
						}
						// echo $count.":".$tag['name'].":".$tag['id']."<br>\n";
						$pipeline->zAdd($all_site_temp_key, $count, $tag['id']);
					}
					$pipeline->rename($all_site_temp_key, $all_site_tags_key);
				}

				if (is_array($pics_tags) && count($pics_tags) > 0) {
					$count = 0;
					foreach ($pics_tags as $tag) {
						$count++;
						$pipeline->zAdd($pics_site_temp_key, $count, $tag['id']);
					}
					$pipeline->rename($pics_site_temp_key, $pics_site_tags_key);
				}

				if (is_array($movies_tags) && count($movies_tags) > 0) {
					$count = 0;
					foreach ($movies_tags as $tag) {
						$count++;
						$pipeline->zAdd($movies_site_temp_key, $count, $tag['id']);
					}
					$pipeline->rename($movies_site_temp_key, $movies_site_tags_key);
				}
				if (is_array($gifs_tags) && count($gifs_tags) > 0) {
					$count = 0;
					foreach ($gifs_tags as $tag) {
						$count++;
						$pipeline->zAdd($gifs_site_temp_key, $count, $tag['id']);
					}
					$pipeline->rename($gifs_site_temp_key, $gifs_site_tags_key);
				}

				$tags_array_pics_key = $this->keyFor('tags', 'array', 'pics', $site_id);
				$tags_array_movies_key = $this->keyFor('tags', 'array', 'movies', $site_id);
				$tags_array_pics = json_encode($pics_tags);
				$tags_array_movies = json_encode($movies_tags);
				$pipeline->set($tags_array_pics_key, $tags_array_pics, 86400);
				$pipeline->set($tags_array_movies_key, $tags_array_movies, 86400);

				$result = $pipeline->exec();
			} else $log = new Logger("Кэш: " . __FUNCTION__ . ", Cайт " . $site_id . " не инициализирован, ошибка коннекта, сервер " . $site_redis_server . ";", true);
			if ($server_changed) $this->redis_connection($previous_server);
		} else $log = new Logger("Кэш: " . __FUNCTION__ . ", Cайт " . $site_id . " не инициализирован, не найден в таблицах сайтов", true);
		return $result;
	}

	function initializeSiteSources($site_id, $source_id = false, $gal_id = false, $type = false)
	{
		$result = false;
		$server_changed = false;
		$previous_server = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			//
			//	добавить нишевую выборку для мультиниша
			//
			if (intval($source_id)) {
				$all_sources[] = $source_id;
			} else {
				$all_sources = $this->sites->getSiteSourcesList($site_id);
			}
			$pics_sources = $this->sites->getSiteSourcesList($site_id, false, 'pics');
			$movies_sources = $this->sites->getSiteSourcesList($site_id, false, 'movies');
			$gifs_sources = $this->sites->getSiteSourcesList($site_id, false, 'gif');

			$all_sources_count = count($all_sources);
			$pics_sources_count = count($pics_sources);
			$movies_sources_count = count($movies_sources);
			$gifs_sources_count = count($gifs_sources);

			$key_prefix = $this->default_prefix . $site_id . ":";
			$all_site_sources_key = $this->keyFor('sources', 'name', 'all', $site_id);
			$pics_site_sources_key = $this->keyFor('sources', 'name', 'pics', $site_id);
			$movies_site_sources_key = $this->keyFor('sources', 'name', 'movies', $site_id);
			$gifs_site_sources_key = $this->keyFor('sources', 'name', 'gif', $site_id);

			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			// var_dump($this->connected);
			if ($this->connected) {
				$source_info = array();
				$pics_source_info = array();
				$movies_sources_info = array();
				$gifs_sources_info = array();
				$count = 0;
				$all_site_temp_key = $all_site_sources_key . ":temp:" . getmypid();
				$pics_site_temp_key = $pics_site_sources_key . ":temp:" . getmypid();
				$movies_site_temp_key = $movies_site_sources_key . ":temp:" . getmypid();
				$gifs_site_temp_key = $gifs_site_sources_key . ":temp:" . getmypid();
				$pipeline = $this->redis->multi(Redis::PIPELINE);

				if (is_array($all_sources) && count($all_sources) > 0) {
					$start = get_time();
					$count = 0; // сделан для zAdd в алфавитном порядке, чтобы не считать буквы
					foreach ($all_sources as $source) {
						$count++;
						if (intval($gal_id) && preg_match("#(movies|pics|gif)#", $type)) {
							$source_galleries[0]['id'] = $gal_id;
							$source_galleries[0]['gal_type'] = $type;
							$source_galleries[0]['time_added'] = time();
						} else $source_galleries = $this->sites->getSourcesGalleries_short($source['id']);

						if (is_array($source_galleries) && count($source_galleries) > 0) {
							foreach ($source_galleries as $gallery) {
								$source_id = $source['id'];
								$source_galleries_prefix = $key_prefix . "source_galleries:all:";
								$source_pics_galleries_prefix = $key_prefix . "source_galleries:pics:";
								$source_movies_galleries_prefix = $key_prefix . "source_galleries:movies:";
								$source_gifs_galleries_prefix = $key_prefix . "source_galleries:gif:";

								$pipeline->zAdd($this->keyFor('source_galleries', 'pageviews', 'all', $site_id, $source_id), 0, $gallery['id']);
								$pipeline->zAdd($this->keyFor('source_galleries', 'pageviews', $gallery['gal_type'], $site_id, $source_id), 0, $gallery['id']);

								$pipeline->zAdd($this->keyFor('source_galleries', 'date', 'all', $site_id, $source_id), floatval($gallery['time_added']), $gallery['id']);
								$pipeline->zAdd($this->keyFor('source_galleries', 'date', $gallery['gal_type'], $site_id, $source_id), floatval($gallery['time_added']), $gallery['id']);
							}
						}
						$pipeline->zAdd($all_site_temp_key, $count, $source['id']);
						echo $count . ":" . $source['name'] . ":" . $source['id'] . ":" . $gallery['time_added'] . "<br>\n";
					}
					$end = get_time();
					$dif = $end - $start;
					echo "Exec.t: '" . $dif . "'\n";
					$pipeline->rename($all_site_temp_key, $all_site_sources_key);
					echo "Key: " . $all_site_sources_key . "<br>";
				}

				if (is_array($pics_sources) && count($pics_sources) > 0) {
					$count = 0;
					foreach ($pics_sources as $source) {
						$count++;
						$pipeline->zAdd($pics_site_temp_key, $count, $source['id']);
					}
					$pipeline->rename($pics_site_temp_key, $pics_site_sources_key);
					echo "Key: " . $pics_site_sources_key . "<br>";
				}

				if (is_array($movies_sources) && count($movies_sources) > 0) {
					$count = 0;
					foreach ($movies_sources as $source) {
						$count++;
						$pipeline->zAdd($movies_site_temp_key, $count, $source['id']);
					}
					$pipeline->rename($movies_site_temp_key, $movies_site_sources_key);
					echo "Key: " . $movies_site_sources_key . "<br>";
				}

				if (is_array($gifs_sources) && count($gifs_sources) > 0) {
					$count = 0;
					foreach ($gifs_sources as $source) {
						$count++;
						$pipeline->zAdd($gifs_site_temp_key, $count, $source['id']);
					}
					$pipeline->rename($gifs_site_temp_key, $gifs_site_sources_key);
					echo "Key: " . $gifs_site_sources_key . "<br>";
				}
				$result = $pipeline->exec();
			} else $log = new Logger("Кэш: " . __FUNCTION__ . ", Cайт " . $site_id . " не инициализирован, ошибка коннекта, сервер " . $site_redis_server . ";", true);
			if ($server_changed) $this->redis_connection($previous_server);
		} else $log = new Logger("Кэш: " . __FUNCTION__ . ", Cайт " . $site_id . " не инициализирован, не найден в таблицах сайтов", true);
		return $result;
	}

	function initializeSiteModels($site_id)
	{
		$result = false;
		$server_changed = false;
		$previous_server = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			//
			//	добавить нишевую выборку для мультиниша
			//
			$all_models = $this->models->getSiteModelsList($site_id);
			$pics_models = $this->models->getSiteModelsList($site_id, false, 'pics');
			$movies_models = $this->models->getSiteModelsList($site_id, false, 'movies');
			$gifs_models = $this->models->getSiteModelsList($site_id, false, 'gif');

			$all_model_count = count($all_models);
			$pics_model_count = count($pics_models);
			$movies_model_count = count($movies_models);
			$gifs_model_count = count($gifs_models);

			$key_prefix = $this->default_prefix . $site_id . ":";
			$all_site_models_key = $this->keyFor('models', 'name', 'all', $site_id);
			$pics_site_models_key = $this->keyFor('models', 'name', 'pics', $site_id);
			$movies_site_models_key = $this->keyFor('models', 'name', 'movies', $site_id);
			$gifs_site_models_key = $this->keyFor('models', 'name', 'gif', $site_id);

			$site_models_likes = $this->keyFor('models', 'likes', 'all', $site_id);
			$site_models_pageviews = $this->keyFor('models', 'pageviews', 'all', $site_id);


			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$model_info = array();
				$pics_model_info = array();
				$movies_models_info = array();
				$gifs_models_info = array();
				$count = 0;
				$all_site_temp_key = $all_site_models_key . ":temp:" . getmypid();
				$pics_site_temp_key = $pics_site_models_key . ":temp:" . getmypid();
				$movies_site_temp_key = $movies_site_models_key . ":temp:" . getmypid();
				$gifs_site_temp_key = $gifs_site_models_key . ":temp:" . getmypid();
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				// var_dump($all_models);
				if (is_array($all_models) && count($all_models) > 0) {
					$count = 0;
					foreach ($all_models as $model) {
						$count++;
						$model_info[] = implode("|", $model);
						$model_galleries = $this->models->getModelsGalleries($model['id'], $site_id);
						if (is_array($model_galleries) && count($model_galleries) > 0) {
							foreach ($model_galleries as $gallery) {
								echo "Site_id:" . $site_id . ",Model:" . $model['id'] . ", Gallery:" . $gallery['id'] . "<br>";
								$pipeline->zAdd($this->keyFor('model_galleries', 'date', 'all', $site_id, $model['id']), floatval($gallery['time_added']), $gallery['id']);
								$pipeline->zAdd($this->keyFor('model_galleries', 'date', $gallery['gal_type'], $site_id, $model['id']), floatval($gallery['time_added']), $gallery['id']);
							}
						} else {
							echo "Site_id:" . $site_id . ",Model:" . $model['id'] . ", 0 galleries<br>";
						}
						if ($model['likes']) $likes = $model['likes'];
						else $likes = 0;
						if ($model['pageviews']) $pageviews = $model['pageviews'];
						else $pageviews = 0;
						$pipeline->zAdd($all_site_temp_key, $count, $model['id']);
						$pipeline->zIncrBy($site_models_likes, $likes, $model['id']);
						$pipeline->zIncrBy($site_models_pageviews, $pageviews, $model['id']);
						// echo $count.", Model name: ".$model['name'].", model id: ".$model['id'].", pageviews:".$pageviews.", likes:".$likes."<br>\n";
					}
					$pipeline->rename($all_site_temp_key, $all_site_models_key);
				}

				if (is_array($pics_models) && count($pics_models) > 0) {
					$count = 0;
					foreach ($pics_models as $model) {
						$count++;
						$pics_model_info[] = implode("|", $model);
						$pipeline->zAdd($pics_site_temp_key, $count, $model['id']);
					}
					$pipeline->rename($pics_site_temp_key, $pics_site_models_key);
				}

				if (is_array($movies_models) && count($movies_models) > 0) {
					$count = 0;
					foreach ($movies_models as $model) {
						$count++;
						$movies_models_info[] = implode("|", $model);
						$pipeline->zAdd($movies_site_temp_key, $count, $model['id']);
					}
					$pipeline->rename($movies_site_temp_key, $movies_site_models_key);
				}
				if (is_array($gifs_models) && count($gifs_models) > 0) {
					$count = 0;
					foreach ($gifs_models as $model) {
						$count++;
						$gifs_models_info[] = implode("|", $model);
						$pipeline->zAdd($gifs_site_temp_key, $count, $model['id']);
					}
					$pipeline->rename($gifs_site_temp_key, $gifs_site_models_key);
				}
				$result = $pipeline->exec();
			} else {
				$log = new Logger("Кэш: " . __FUNCTION__ . ", Cайт " . $site_id . " не инициализирован, ошибка коннекта, сервер " . $site_redis_server . ";", true);
			}
			if ($server_changed) {
				$this->redis_connection($previous_server);
			}
		} else {
			$log = new Logger("Кэш: " . __FUNCTION__ . ", Cайт " . $site_id . " не инициализирован, не найден в таблицах сайтов", true);
		}
		return $result;
	}


	//зарядить рейтинг
	function initializeSiteGalleriesRating($site_id)
	{ // не доделано
		$result = false;
		$server_changed = false;
		$previous_server = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}

			if ($this->connected) {
				$site_id = intval($site_id);
				$key_prefix = $this->default_prefix . $site_id . ":";
				$galleries_db_key = $this->localGalleriesDB($site_id);
				$galleries_db_key_no_local = $this->no_localGalleriesDB($site_id);

				if ($site_switched['site_own_titles'] || $site_switched['site_own_main_thumbs']) {
					// echo "Self titles";
					$galleries = $this->sites->getSiteGalleries_newCache($added_galleries_list, true);
				} else {
					$galleries = $this->sites->getSiteGalleries_newCache();
				}


				$pipeline = $this->redis->multi(Redis::PIPELINE);
				foreach ($galleries as $local_id => $gallery) {
					if (
						isset($gallery['id']) && isset($gallery['global_id']) && isset($gallery['gal_type'])
						&& isset($gallery['time_added']) && isset($gallery['url_desc']) && preg_match("#^(pics|movies|gif)$#im", $gallery['gal_type'])
					) {
						$type = strtolower($gallery['gal_type']);
						$set_galleries[$gallery['id']] = $gallery['global_id'];
						$set_galleries_no_local[$gallery['global_id']] = $gallery['id'];

						$pipeline->zAdd($this->keyFor('galleries', 'rating', $type, $site_id), $gallery['rating'], $gallery['id']);
						// var_dump($gallery['id'], $gallery['rating']);
					} else $log = new Logger(__METHOD__ . "Не правильный формат галеры получен из getSiteGalleries_newCache, Site_ID:" . $site_id . "Local gal ID:" . $local_id, true);
				}
				$pipeline->exec();
				unset($gallery);
				$log = new Logger($galleries_db_key . ": сайт " . $site_id . " инициализирован");
				$result =  true;
				if ($server_changed) $this->redis_connection($previous_server);
			} else {
				echo "Кэш: Cайт " . $site_id . " не инициализирован, ошибка коннекта, сервер " . $site_redis_server;
				$log = new Logger("Кэш: Cайт " . $site_id . " не инициализирован, ошибка коннекта, сервер " . $site_redis_server . "", true);
			}
		} else $log = new Logger("Кэш: Cайт " . $site_id . " не инициализирован, не найден в таблицах сайтов", true);
		return $result;
	}

	// пересборка полная
	function initializeSiteGalleries($site_id)
	{
		$result = false;
		$server_changed = false;
		$previous_server = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}

			if ($this->connected) {
				$site_id = intval($site_id);
				$key_prefix = $this->default_prefix . $site_id . ":";
				$galleries_db_key = $this->localGalleriesDB($site_id);
				$galleries_db_key_no_local = $this->no_localGalleriesDB($site_id);

				if ($site_switched['site_own_titles'] || $site_switched['site_own_main_thumbs']) {
					// echo "Self titles";
					$galleries = $this->sites->getSiteGalleries_newCache(false, true);
				} else {
					$galleries = $this->sites->getSiteGalleries_newCache();
				}

				foreach ($galleries as $local_id => $gallery) {
					echo "Local ID:" . $local_id . ", Global ID:" . $gallery['global_id'] . ", Type: " . $gallery['gal_type'] . ":" . $gallery['video_embed'];
					$gallery_global_key = $this->galleryGlobalKey($gallery['global_id']);
					if ($gallery['video_embed'] || !$this->redis->exists($gallery_global_key)) {
						// echo "Нет глобального кэша галеры #". $gallery['global_id'].", собираю<br>\n";
						echo "..Recaching..";
						$start = get_time();
						$this->cacheGallery($gallery['global_id']);
						$finish = get_time();
						$exec_time = $finish - $start;
						$log = new Logger(__METHOD__ . ", вызов cacheGallery: Redis Init exec time: " . $exec_time);
					}
					echo "<br>";
				}

				$pipeline = $this->redis->multi(Redis::PIPELINE);
				foreach ($galleries as $local_id => $gallery) {
					if (
						isset($gallery['id']) && isset($gallery['global_id']) && isset($gallery['gal_type'])
						&& isset($gallery['time_added']) && isset($gallery['url_desc']) && preg_match("#^(pics|movies|gif)$#im", $gallery['gal_type'])
					) {
						$type = strtolower($gallery['gal_type']);
						$set_galleries[$gallery['id']] = $gallery['global_id'];
						$set_galleries_no_local[$gallery['global_id']] = $gallery['id'];

						// установка локальных параметров галлереи, включая тип
						$pipeline->hMset($this->galleryLocalInfoKey($site_id, $gallery['id']), $gallery);

						// добавление в основную (all|pics|movies) таблицу по времени
						$pipeline->zAdd($this->keyFor('galleries', 'date', 'all', $site_id), floatval($gallery['time_added']), $gallery['id']);
						$pipeline->zAdd($this->keyFor('galleries', 'date', $type, $site_id), floatval($gallery['time_added']), $gallery['id']);
						$pipeline->zAdd($this->keyFor('galleries', 'rating', $type, $site_id), $gallery['rating'], $gallery['id']);

						// добавление галеры в таблицу лайков all|pics|movies|gif
						$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', 'all', $site_id), $gallery['likes'], $gallery['id']);
						$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', $type, $site_id), $gallery['likes'], $gallery['id']);
						// echo $this->keyFor('galleries','likes',$type, $site_id) ."\tAdded to likes (pics) G:".$gallery['global_id'].", L:".$gallery['id']."<br>\n";
						// $log = new Logger ($this->keyFor('galleries','likes',$type, $site_id), true);

						// добавление галеры в таблицу общих пейджвью
						$pipeline->zAdd($this->keyFor('galleries', 'pageviews', 'all', $site_id), $gallery['pageviews'], $gallery['id']);
						// добавление в таблицу пейджвью по типу (pics|movies|gif)
						$pipeline->zAdd($this->keyFor('galleries', 'pageviews', $type, $site_id), $gallery['pageviews'], $gallery['id']);
						// echo "\tAdded to pageviews (movies) G:".$gallery['global_id'].", L:".$gallery['id']."<br>\n";

						$pipeline->hSet($galleries_db_key, $gallery['id'], $gallery['global_id']);
						$pipeline->hSet($galleries_db_key_no_local, $gallery['global_id'], $gallery['id']);
					} else $log = new Logger(__METHOD__ . "Не правильный формат галеры получен из getSiteGalleries_newCache, Site_ID:" . $site_id . "Local gal ID:" . $local_id, true);
				}
				$pipeline->exec();
				unset($gallery);
				$log = new Logger($galleries_db_key . ": сайт " . $site_id . " инициализирован");
				echo $galleries_db_key . "<br>";
				$result =  true;
				if ($server_changed) $this->redis_connection($previous_server);
			} else $log = new Logger("Кэш: Cайт " . $site_id . " не инициализирован, ошибка коннекта, сервер " . $site_redis_server . "", true);
		} else $log = new Logger("Кэш: Cайт " . $site_id . " не инициализирован, не найден в таблицах сайтов", true);
		return $result;
	}

	function removeGallery($gal_id, $source_id = false, $sites = false, $tags = false, $models = false)
	{

		$gallery_global_key = $this->galleryGlobalKey($gal_id);
		$galleries_db_key = $this->galleriesDBKey();

		if ($sites && is_array($sites)) {
			foreach ($sites as $site_id) {
				$this->removeGalleryFromSite($gal_id, $source_id, $site_id, $tags, $models);
			}
		}
		// echo "Removing from: ".$gallery_global_key."\n";
		$this->redis->del($gallery_global_key);
		$this->redis->hDel($galleries_db_key, $gal_id);
		$this->redis->hDel($galleries_db_key, $gal_id);
	}

	private function removeGalleryFromSite($gal_id, $source_id = false, $site_id = false, $tags = false, $models = false)
	{
		//		1. Ищется локальный айди
		$galleries_db_key = $this->localGalleriesDB($site_id);

		$galleries_db_key_no_local = $this->no_localGalleriesDB($site_id);

		$local_gal_id = $this->redis->hGet($galleries_db_key_no_local, $gal_id);
		//		2. ИД переименовывается в delete_gal_id
		//		3. Удаляется локальная информация галлереи
		$this->redis->del($this->galleryLocalInfoKey($site_id, $local_gal_id));
		//		4. Удаляется из источника (по дате, по кликам)
		//			Источник передается на пересборку
		$pipeline = $this->redis->multi(Redis::PIPELINE);
		$pipeline->zRem($this->keyFor('source_galleries', 'pageviews', 'all', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'pageviews', 'pics', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'pageviews', 'movies', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'pageviews', 'gif', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'date', 'all', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'date', 'pics', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'date', 'movies', $site_id, $source_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('source_galleries', 'date', 'gif', $site_id, $source_id), $local_gal_id);
		//		5. Удаляется из каждого тега (по дате)
		//			Тег передается на пересборку
		if ($tags && is_array($tags)) {
			foreach ($tags as $tag) {
				$pipeline->zRem($this->keyFor('tag_galleries', 'date', 'all', $site_id, $tag), $local_gal_id);
				$pipeline->zRem($this->keyFor('tag_galleries', 'date', 'pics', $site_id, $tag), $local_gal_id);
				$pipeline->zRem($this->keyFor('tag_galleries', 'date', 'movies', $site_id, $tag), $local_gal_id);
				$pipeline->zRem($this->keyFor('tag_galleries', 'date', 'gif', $site_id, $tag), $local_gal_id);
			}
		}
		//		6. Удаляется из каждогой модели (по дате)
		//			Модель передается на пересборку
		if ($models && is_array($models)) {
			foreach ($models as $model) {
				$pipeline->zRem($this->keyFor('model_galleries', 'date', 'all', $site_id, $model), $local_gal_id);
				$pipeline->zRem($this->keyFor('model_galleries', 'date', 'pics', $site_id, $model), $local_gal_id);
				$pipeline->zRem($this->keyFor('model_galleries', 'date', 'movies', $site_id, $model), $local_gal_id);
				$pipeline->zRem($this->keyFor('model_galleries', 'date', 'gif', $site_id, $model), $local_gal_id);
			}
		}
		//		7. Удаляется из пейджвью
		$pipeline->zRem($this->keyFor('galleries', 'pageviews', 'all', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'pageviews', 'pics', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'pageviews', 'movies', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'pageviews', 'gif', $site_id), $local_gal_id);
		//		8. Удаляется из лайков 
		$pipeline->zRem($this->keyFor('galleries', 'likes', 'all', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'likes', 'pics', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'likes', 'movies', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'likes', 'gif', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('likes_galleries', 'likes', 'all', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('likes_galleries', 'likes', 'pics', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('likes_galleries', 'likes', 'movies', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('likes_galleries', 'likes', 'gif', $site_id), $local_gal_id);
		//		9. Удаляется из "по дате"
		$pipeline->zRem($this->keyFor('galleries', 'date', 'all', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'date', 'pics', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'date', 'movies', $site_id), $local_gal_id);
		$pipeline->zRem($this->keyFor('galleries', 'date', 'gif', $site_id), $local_gal_id);
		//		10. удаляется ИД из базы локальные и не_локальные
		$pipeline->hDel($galleries_db_key, $local_gal_id);
		$pipeline->hDel($galleries_db_key_no_local, $gal_id);
		$this->redis->exec();
		if ($models && is_array($models)) {
			foreach ($models as $model) {
				if (!$this->siteModelExists($site_id, $model)) $this->siteModelDelete($site_id, $model);
			}
		}
		if ($tags && is_array($tags)) {
			foreach ($tags as $tag) {
				if (!$this->siteTagExists($site_id, $tag)) $this->siteTagDelete($site_id, $tag);
			}
		}
	}

	function resetSiteLikes($site_id)
	{
		$result = false;
		if ($this->connected && $this->sites->switchSite($site_id)) {
			$site_id = intval($site_id);
			$key_prefix = $this->default_prefix . $site_id . ":";
			$gallery_likes_key = $key_prefix . "likes_key";
			$gallery_likes_pics_key = $gallery_likes_key . ":pics";
			$gallery_likes_movies_key = $gallery_likes_key . ":movies";
			$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
			$total_likes_key = $key_prefix . ":total_likes";
			$this->redis->del($gallery_likes_key);
			$this->redis->del($gallery_likes_pics_key);
			$this->redis->del($gallery_likes_movies_key);
			$this->redis->del($gallery_likes_gifs_key);
			$this->redis->del($total_likes_key);
			$total_galleries = $this->redis->zRevRange($this->keyFor('galleries', 'date', 'all', $site_id), 0, -1);
			$pics_galleries = $this->redis->zRevRange($this->keyFor('galleries', 'date', 'pics', $site_id), 0, -1);
			$movies_galleries = $this->redis->zRevRange($this->keyFor('galleries', 'date', 'movies', $site_id), 0, -1);
			$gifs_galleries = $this->redis->zRevRange($this->keyFor('galleries', 'date', 'gif', $site_id), 0, -1);
			$pipeline = $this->redis->multi(Redis::PIPELINE);
			if (is_array($total_galleries)) {
				foreach ($total_galleries as $gallery) {
					$pipeline->zAdd($gallery_likes_key, 0, $gallery);
				}
				foreach ($pics_galleries as $gallery) {
					$pipeline->zAdd($gallery_likes_pics_key, 0, $gallery);
				}
				foreach ($movies_galleries as $gallery) {
					$pipeline->zAdd($gallery_likes_movies_key, 0, $gallery);
				}
				foreach ($gifs_galleries as $gallery) {
					$pipeline->zAdd($gallery_likes_gifs_key, 0, $gallery);
				}
			}
			$pipeline->exec();
		}
		return $result;
	}

	function repairLikes_Pageviews($site_id)
	{
		$result = false;
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$site_id = intval($site_id);
				$key_prefix = $this->default_prefix . $site_id . ":";
				$galleries_db_key = $key_prefix . "galleries";
				$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
				$gallery_likes_key = $key_prefix . "likes_key";
				$gallery_likes_pics_key = $gallery_likes_key . ":pics";
				$gallery_likes_movies_key = $gallery_likes_key . ":movies";
				$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
				$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
				$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
				$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
				$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";

				$total_galleries = $this->redis->zRevRange($galleries_sorted_by_date, 0, -1);

				$liked_pics_galleries = $this->redis->zRevRange($gallery_likes_pics_key, 0, -1);
				$liked_movies_galleries = $this->redis->zRevRange($gallery_likes_movies_key, 0, -1);
				$liked_gifs_galleries = $this->redis->zRevRange($gallery_likes_gifs_key, 0, -1);

				$viewed_galleries = $this->redis->zRevRange($gallery_pageviews_key, 0, -1);
				$viewed_pics_galleries = $this->redis->zRevRange($gallery_pageviews_pics_key, 0, -1);
				$viewed_movies_galleries = $this->redis->zRevRange($gallery_pageviews_movies_key, 0, -1);
				$viewed_gifs_galleries = $this->redis->zRevRange($gallery_pageviews_gifs_key, 0, -1);

				foreach ($total_galleries as $gallery) {
					$global_gallery_id = $this->redis->hGet($galleries_db_key, $gallery);
					$gallery_global_key = $this->galleryGlobalKey($global_gallery_id);
					$type = $this->redis->hGet($gallery_global_key, 'type');
					echo "<br><br>\n\nГаллерея #" . $gallery . ", Глобальный #" . $global_gallery_id . ", тип: '" . $type . "':<br>\n";
					if ($type) {
						$type = strtolower($type);
						if ($type == 'pics') {
							// if (!in_array($gallery, $liked_galleries) || !in_array($gallery, $liked_pics_galleries)) {
							$pipeline = $this->redis->multi(Redis::PIPELINE);
							$pipeline->zAdd($gallery_likes_key, 0, $gallery);
							$pipeline->zAdd($gallery_likes_pics_key, 0, $gallery);
							echo "\tFixed likes (pics) G:" . $global_gallery_id . ", L:" . $gallery . "<br>\n";
							// }
							// if (!in_array($gallery, $viewed_galleries) || !in_array($gallery, $viewed_pics_galleries)) {
							$pipeline->zAdd($gallery_pageviews_key, 0, $gallery);
							$pipeline->zAdd($gallery_pageviews_pics_key, 0, $gallery);
							echo "\tFixed pageviews (pics) G:" . $global_gallery_id . ", L:" . $gallery . "<br>\n";
							$pipeline->exec();
							// }
						} elseif ($type == 'gif') {
							// if (!in_array($gallery, $liked_galleries) || !in_array($gallery, $liked_pics_galleries)) {
							$pipeline = $this->redis->multi(Redis::PIPELINE);
							$pipeline->zAdd($gallery_likes_key, 0, $gallery);
							$pipeline->zAdd($gallery_likes_gifs_key, 0, $gallery);
							echo "\tFixed likes (pics) G:" . $global_gallery_id . ", L:" . $gallery . "<br>\n";
							// }
							// if (!in_array($gallery, $viewed_galleries) || !in_array($gallery, $viewed_pics_galleries)) {
							$pipeline->zAdd($gallery_pageviews_key, 0, $gallery);
							$pipeline->zAdd($gallery_pageviews_gifs_key, 0, $gallery);
							echo "\tFixed pageviews (pics) G:" . $global_gallery_id . ", L:" . $gallery . "<br>\n";
							$pipeline->exec();
							// }
						} elseif ($type == 'movies') {
							$pipeline = $this->redis->multi(Redis::PIPELINE);
							// if (!in_array($gallery, $liked_galleries) || !in_array($gallery, $liked_movies_galleries)) {
							$this->redis->zAdd($gallery_likes_key, 0, $gallery);
							$this->redis->zAdd($gallery_likes_movies_key, 0, $gallery);
							echo "\tFixed likes (movies) G:" . $global_gallery_id . ", L:" . $gallery . "<br>\n";
							// }
							// if (!in_array($gallery, $viewed_galleries) || !in_array($gallery, $viewed_movies_galleries)) {
							$this->redis->zAdd($gallery_pageviews_key, 0, $gallery);
							$this->redis->zAdd($gallery_pageviews_movies_key, 0, $gallery);
							echo "\tFixed pageviews (movies) G:" . $global_gallery_id . ", L:" . $gallery . "<br>\n";
							// }
							$pipeline->exec();
						} else  echo "Неверный тип галеры " . $global_gallery_id . "<br>";
					} else echo "Ошибка обнаружения типа галеры " . $global_gallery_id . "<br>";
				}
			} else $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
		}
		return $result;
	}

	function resetSitePageviews($site_id)
	{
		$result = false;
		if ($this->connected && $this->sites->switchSite($site_id)) {
			$site_id = intval($site_id);
			$from_date = date("Y-m-d", time());
			$key_prefix = $this->default_prefix . $site_id . ":";
			$total_pageviews_key = $key_prefix . ":total_pageviews";
			$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
			$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
			$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
			$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";
			$visitors_db_key = $key_prefix . "visitors:" . $from_date;
			$this->redis->del($visitors_db_key);
			$this->redis->del($total_pageviews_key);
			$this->redis->del($total_pageviews_key);
			$this->redis->del($gallery_pageviews_key);
			$this->redis->del($gallery_pageviews_pics_key);
			$this->redis->del($gallery_pageviews_movies_key);
			$this->redis->del($gallery_pageviews_gifs_key);

			$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
			$total_galleries = $this->redis->zRevRange($galleries_sorted_by_date, 0, -1);
			$pics_galleries = $this->redis->zRevRange($galleries_sorted_by_date . ":pics", 0, -1);
			$movies_galleries = $this->redis->zRevRange($galleries_sorted_by_date . ":movies", 0, -1);
			$gifs_galleries = $this->redis->zRevRange($galleries_sorted_by_date . ":gif", 0, -1);
			$pipeline = $this->redis->multi(Redis::PIPELINE);
			if (is_array($total_galleries)) {
				foreach ($total_galleries as $gallery) {
					$pipeline->zAdd($gallery_pageviews_key, 0, $gallery);
				}
				foreach ($pics_galleries as $gallery) {
					$pipeline->zAdd($gallery_pageviews_pics_key, 0, $gallery);
				}
				foreach ($movies_galleries as $gallery) {
					$pipeline->zAdd($gallery_pageviews_movies_key, 0, $gallery);
				}
				foreach ($gifs_galleries as $gallery) {
					$pipeline->zAdd($gallery_pageviews_gifs_key, 0, $gallery);
				}
			}
			$pipeline->exec();
		}
		return true;
	}

	function updateGalleryLocalTitle($site_id, $local_gal_id, $title)
	{
		$site_switched = $this->sites->switchSite($site_id);
		if ($site_switched) {
			$server_changed = false;
			$previous_server = false;
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$key_prefix = $this->default_prefix . $site_id . ":";
				$gallery_local_key_prefix = $key_prefix . "gallery:local:";
				$this->redis->hSet($gallery_local_key_prefix . $local_gal_id, 'own_title', $title);
			}
			if ($server_changed) $this->redis_connection($previous_server);
		}
	}

	private function getCacheQuery()
	{
		$result = false;

		$db = DB::get();

		if ($db) {
			$sql = "SELECT site_id, cache_server_id, gal_id, gal_local_id, gal_type, item_type, change_type, item_id, added_on, updadted_on
						FROM sites_cache_query
						WHERE error = 0
						ORDER BY added_on ASC";
			$stmt = $db->prepare($sql);

			if ($stmt) {
				$stmt->execute();

				$site_id = false;
				$cache_server_id = false;
				$gal_id = false;
				$gal_local_id = false;
				$gal_type = false;
				$item_type = false;
				$change_type = false;
				$item_id = false;
				$added_on = false;
				$updadted_on = false;

				$stmt->bind_result($site_id, $cache_server_id, $gal_id, $gal_local_id, $gal_type, $item_type, $change_type, $item_id, $added_on, $updadted_on);

				if ($stmt->fetch()) {
					$result = compact("site_id", "cache_server_id", "gal_id", "gal_local_id", "gal_type", "item_type", "change_type", "item_id", "added_on", "updadted_on");
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT prepare error. '" . $db->error . "'", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connection", true);
		}

		return $result;
	}

	function processCacheQuery()
	{
		$result = false;

		$query_result = $this->getCacheQuery();

		if ($query_result) {
			$site_id = false;
			$cache_server_id = false;
			$gal_id = false;
			$gal_local_id = false;
			$gal_type = false;
			$item_type = false;
			$change_type = false;
			$item_id = false;
			$added_on = false;
			$updadted_on = false;

			$query_result = $this->getCacheQuery();

			extract($query_result);

			if ($item_type) {
			} else {
				$log = new Logger(__METHOD__ . ": ОЧередь пустая, или ошибка разбора массива из  getCacheQuery", true);
			}
		}

		return $result;
	}

	// подавать полный массив галер на вход, желательно частями
	function new_addNewGalleries($site_id, $redis_server, $galleries_list)
	{
		$result = false;

		$server_changed = false;
		$previous_server = false;

		$site_id = (int)$site_id;
		$redis_server = (int)$redis_server;

		if ($site_id > 0 && $redis_server >= 0) {

			if (is_array($galleries_list)) {

				if ($this->current_server != $redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($redis_server);
				}

				$key_prefix = $this->default_prefix . $site_id . ":";

				$galleries_db_key = $key_prefix . "galleries";
				$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
				$gallery_likes_key = $key_prefix . "likes_key";
				$gallery_likes_pics_key = $gallery_likes_key . ":pics";
				$gallery_likes_movies_key = $gallery_likes_key . ":movies";
				$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
				$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
				$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
				$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
				$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";
				$galleries_db_key_no_local = $key_prefix . "galleries:no_local";
				$gallery_local_key_prefix = $key_prefix . "gallery:local:";

				if ($this->connected) {

					// foreach для проверки есть ли глобальные параметры галеры
					// отделил из-за проблем с pipeline'ом, потом проверить и исправить

					$galleries_checklist = array();

					$pipeline = $this->redis->multi(Redis::PIPELINE);

					if ($pipeline) {
						$gals_counter = 0;
						foreach ($galleries_list as $local_id => $gallery) {
							if (is_array($gallery)) {
								$id = false;
								$global_id = false;
								$url_desc = false;
								$time_added = false;
								$gal_type = false;
								$gal_paysite = false;
								$rating = 0;
								$likes = 0;
								$pageviews = 0;
								$own_title = false;
								$own_main_thumb = false;
								$gal_tags = false;
								$gal_models = false;
								$video_embed = false;

								extract($gallery);
								// var_dump($gallery);
								$galleries_checklist[$gals_counter] = $global_id;

								$gallery_global_key = $this->galleryGlobalKey($global_id);



								$type = strtolower($gal_type);
								$pipeline->exists($gallery_global_key);

								$gal_local_id = $id;
								$gal_global_id = $global_id;
								$source_id = $gal_paysite;
								$added_on = $time_added;
								$time_added = floatval($time_added);

								// установка локальных параметров галлереи, включая тип
								$pipeline->hMset($gallery_local_key_prefix . $gal_local_id, $gallery);
								// добавление в основную (all) таблицу по времени
								$pipeline->zAdd($galleries_sorted_by_date, $time_added, $gal_local_id);
								// добавление в таблицу типов (pics|movies)
								$pipeline->zAdd($galleries_sorted_by_date . ":" . $type, $time_added, $gal_local_id);
								// добавление галеры в таблицу общих лайков
								$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', 'all', $site_id), $likes, $gal_local_id);
								// добавление в таблицу лайков по типу (pics|movies)
								$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', $type, $site_id), $likes, $gal_local_id);
								// добавление галеры в таблицу общих пейджвью
								$pipeline->zAdd($gallery_pageviews_key, $pageviews, $gal_local_id);
								// добавление в таблицу пейджвью по типу (pics|movies)
								$pipeline->zAdd($gallery_pageviews_key . ":" . $type, $pageviews, $gal_local_id);

								// добавление в список галлерей для поиска по локальному и глобальному ID
								$pipeline->hSet($galleries_db_key, $gal_local_id, $gal_global_id);
								$pipeline->hSet($galleries_db_key_no_local, $gal_global_id, $gal_local_id);


								$this->addGalleryToSourceTransaction($site_id, $source_id, $gal_global_id, $gal_local_id, $type, $added_on, $pipeline);

								if ($gal_models) {
									foreach ($gal_models as $model_id) {
										$this->addGalleryToModelTransaction($site_id, $model_id, $gal_global_id, $gal_local_id, $type, $added_on, $pipeline);
									}
								}
								if ($gal_tags) {
									foreach ($gal_tags as $tag_id) {
										$this->addGalleryToTagTransaction($site_id, $tag_id, $gal_global_id, $gal_local_id, $type, $added_on, $pipeline);
									}
								}

								$gals_counter++;
							} else {
								$log = new Logger(__METHOD__ . ": в массиве галер ошибка, local_id:" . $local_id . ", site_id:" . $site_id, true);
							}
						}

						$pipe_result = $pipeline->exec();

						if ($pipe_result && $gals_counter) {
							for ($i = 0; $i < $gals_counter; $i++) {
								$gallery_exist = $pipe_result[$i * 14];
								if (!$gallery_exist) {
									$this->cacheGallery($galleries_checklist[$i]);
								}
							}
							$result = true;
						}
						// echo "Pipeline result:";	var_dump($pipeline, $pipe_result);

					} else {
						echo "Pipeline is broke";
					}

					if ($server_changed) {
						$this->redis_connection($previous_server);
					}
				} else {
					echo "Кэш: Cайт " . $site_id . " не инициализирован, сервер redis #" . $site_redis_server . "<br>";
					$log = new Logger("Кэш: Cайт " . $site_id . " не инициализирован, сервер redis #" . $site_redis_server, true);
				}
			} else {
				echo "Нет галер на добавление: " . $site_id . "<br>";
				$log = new Logger("Нет галер на добавление: " . $site_id, true);
			}
		} else {
			echo "Ошибка входящих параметров на кэширование галер для сайта: " . $site_id . "<br>";
			$log = new Logger(__METHOD__ . "Ошибка входящих параметров на кэширование галер для сайта: " . $site_id, true);
		}

		return $result;
	}



	function addNewGalleries($site_id, $added_galleries_list)
	{
		// $added_galleries_list - массив с данными галер вида local_id => global_id
		$result = false;
		$server_changed = false;
		$previous_server = false;
		if (is_array($added_galleries_list)) {
			$site_switched = $this->sites->switchSite($site_id);
			if ($site_switched) {
				// var_dump($site_switched);
				if ($site_switched['site_own_titles'] || $site_switched['site_own_main_thumbs']) {
					// echo "Self titles";
					$galleries = $this->sites->getSiteGalleries_newCache($added_galleries_list, true);
				} else {
					$galleries = $this->sites->getSiteGalleries_newCache($added_galleries_list);
				}


				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
				}
				if ($this->connected) {
					$site_id = intval($site_id);
					$key_prefix = $this->default_prefix . $site_id . ":";
					$galleries_db_key = $key_prefix . "galleries";
					$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
					$gallery_likes_key = $key_prefix . "likes_key";
					$gallery_likes_pics_key = $gallery_likes_key . ":pics";
					$gallery_likes_movies_key = $gallery_likes_key . ":movies";
					$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
					$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
					$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
					$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
					$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";

					$galleries_db_key_no_local = $key_prefix . "galleries:no_local";

					$gallery_local_key_prefix = $key_prefix . "gallery:local:";

					// $liked_galleries = $this->redis->zRevRange($gallery_likes_key, 0, -1);
					//var_dump($liked_galleries);
					// $liked_pics_galleries = $this->redis->zRevRange($gallery_likes_pics_key, 0, -1);
					//var_dump($liked_pics_galleries);
					// $liked_movies_galleries = $this->redis->zRevRange($gallery_likes_movies_key, 0, -1);
					// $viewed_galleries = $this->redis->zRevRange($gallery_pageviews_key, 0, -1);
					// $viewed_pics_galleries = $this->redis->zRevRange($gallery_pageviews_pics_key, 0, -1);
					// $viewed_movies_galleries = $this->redis->zRevRange($gallery_pageviews_movies_key, 0, -1);


					// foreach для проверки есть ли глобальные параметры галеры
					// отделил из-за проблем с pipeline'ом, потом проверить и исправить
					foreach ($galleries as $local_id => $gallery) {
						var_dump($gallery);
						// echo "Local ID:".$local_id.", Global ID:".$gallery['global_id'].", Type: ".$gallery['gal_type']."<br>";
						$gallery_global_key = $this->galleryGlobalKey($gallery['global_id']);
						if (!$this->redis->exists($gallery_global_key)) {
							// echo "Нет глобального кэша галеры #". $gallery['global_id'].", собираю<br>";
							$start = get_time();
							$this->cacheGallery($gallery['global_id']);
							$finish = get_time();
							$exec_time = $finish - $start;
							$log = new Logger(__METHOD__ . ", вызов cacheGallery: Redis Init exec time: " . $exec_time);
						}
					}

					$pipeline = $this->redis->multi(Redis::PIPELINE);
					foreach ($galleries as $local_id => $gallery) {
						if (
							isset($gallery['id']) && isset($gallery['global_id']) && isset($gallery['gal_type'])
							&& isset($gallery['time_added']) && isset($gallery['url_desc']) && preg_match("#^(pics|movies|gif)$#im", $gallery['gal_type'])
						) {
							$type = strtolower($gallery['gal_type']);
							$set_galleries[$gallery['id']] = $gallery['global_id'];
							$set_galleries_no_local[$gallery['global_id']] = $gallery['id'];

							// установка локальных параметров галлереи, включая тип
							$pipeline->hMset($gallery_local_key_prefix . $gallery['id'], $gallery);
							// добавление в основную (all) таблицу по времени
							$pipeline->zAdd($galleries_sorted_by_date, floatval($gallery['time_added']), $gallery['id']);
							// добавление в таблицу типов (pics|movies)
							$pipeline->zAdd($galleries_sorted_by_date . ":" . $type, floatval($gallery['time_added']), $gallery['id']);

							// добавление галеры в таблицу общих лайков
							$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', 'all', $site_id), 0, $gallery['id']);
							// добавление в таблицу лайков по типу (pics|movies)
							$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', $type, $site_id), 0, $gallery['id']);
							// echo "\tAdded to likes (".$type.") G:".$gallery['global_id'].", L:".$gallery['id']."<br>\n";

							// добавление галеры в таблицу общих пейджвью
							$pipeline->zAdd($gallery_pageviews_key, 0, $gallery['id']);
							// добавление в таблицу пейджвью по типу (pics|movies)
							$pipeline->zAdd($gallery_pageviews_key . ":" . $type, 0, $gallery['id']);
							// echo "\tAdded to pageviews (".$type.") G:".$gallery['global_id'].", L:".$gallery['id']."<br>\n";

							$pipeline->hSet($galleries_db_key, $gallery['id'], $gallery['global_id']);
							$pipeline->hSet($galleries_db_key_no_local, $gallery['global_id'], $gallery['id']);

							if (isset($gallery['gal_paysite'])) {
								$source_id = intval($gallery['gal_paysite']);
								$pipeline->zAdd($this->keyFor('source_galleries', 'date', 'all', $site_id, $source_id), floatval($gallery['time_added']), $gallery['id']);
								$pipeline->zAdd($this->keyFor('source_galleries', 'date', $type, $site_id, $source_id), floatval($gallery['time_added']), $gallery['id']);
								$pipeline->zAdd($this->keyFor('source_galleries', 'pageviews', 'all', $site_id, $source_id), 0, $gallery['id']);
								$pipeline->zAdd($this->keyFor('source_galleries', 'pageviews', $type, $site_id, $source_id), 0, $gallery['id']);
							}
						} else $log = new Logger(__METHOD__ . "Не правильный формат галеры получен из getSiteGalleries_newCache, Site_ID:" . $site_id . "Local gal ID:" . $local_id, true);
					}
					$pipeline->exec();
					unset($gallery);
					if ($server_changed) $this->redis_connection($previous_server);
				} else {
					echo "Кэш: Cайт " . $site_id . " не инициализирован, сервер redis #" . $site_redis_server . "<br>";
					$log = new Logger("Кэш: Cайт " . $site_id . " не инициализирован, сервер redis #" . $site_redis_server, true);
				}
			} else {
				echo "Невозможно переключиться на сайт " . $site_id . ". Ошибка базы данных.<br>";
				$log = new Logger("Невозможно переключиться на сайт " . $site_id, true);
			}
		} else {
			echo "Нет галер на добавление: " . $site_id . "<br>";
			$log = new Logger("Нет галер на добавление: " . $site_id, true);
		}
		return $result;
	}


	/*
	Transaction stuff
*/

	function startTransaction()
	{
		if ($this->redis && (!isset($this->pipeline) || $this->pipeline == false)) {
			$this->pipeline = $this->redis->multi(Redis::PIPELINE);
		}
	}

	function executeTransaction()
	{
		$result = false;
		if (isset($this->pipeline) && $this->pipeline) {
			$result = $this->pipeline->exec();
			$this->pipeline = false;
		}
		return $result;
	}

	function cacheItemTransaction($item_type, $change_type, $site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $added_on)
	{
		$result = false;

		if (!preg_match("#^(movies|pics|gif)$#", $gal_type) || $site_id < 0) {
			$log = new Logger(__METHOD__ . ": Ошибка транзакции кэша галеры GID:" . $gal_id . ", LID:" . $gal_local_id . ", ITYPE:" . $item_type . ", CTYPE:" . $change_type, true);
			return false;
		}

		if ($this->pipeline) {
			// var_dump($item_type, $change_type, $site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $added_on);
			switch ($item_type) {
				case 'gallery':
					if ($change_type == 'removed') {
						$result = $this->deleteGalleryFromSiteTr($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $this->pipeline);
					}
					break;
				case 'source':
					if ($change_type == 'added') {
						$result = $this->addGalleryToSourceTransaction($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $added_on, $this->pipeline);
					} elseif ($change_type == 'removed') {
						$result = $this->deleteSourceFromGallery($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $added_on, $this->pipeline);
					}
					break;
				case 'tag':
					if ($change_type == 'added') {
						$result = $this->addGalleryToTagTransaction($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $added_on, $this->pipeline);
					} elseif ($change_type == 'removed') {
						$result = $this->deleteTagFromGalleryTr($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $added_on, $this->pipeline);
					}
					break;
				case 'model':
					if ($change_type == 'added') {
						$result = $this->addGalleryToModelTransaction($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $added_on, $this->pipeline);
					} elseif ($change_type == 'removed') {
						$result = $this->deleteModelFromGalleryTr($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, $added_on, $this->pipeline);
					}
					break;
			}
		}

		return $result;
	}


	public function deleteGalleryFromSiteTr($site_id, $item_id, $gal_id, $gal_local_id, $gal_type, &$pipeline)
	{

		$key_prefix = $this->default_prefix . $site_id . ":";

		$galleries_db_key = $key_prefix . "galleries";
		$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
		$gallery_likes_key = $key_prefix . "likes_key";
		$gallery_likes_pics_key = $gallery_likes_key . ":pics";
		$gallery_likes_movies_key = $gallery_likes_key . ":movies";
		$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
		$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
		$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
		$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
		$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";
		$galleries_db_key_no_local = $key_prefix . "galleries:no_local";
		$gallery_local_key_prefix = $key_prefix . "gallery:local:";


		if ($pipeline) {
			$global_id = $gal_id;
			$type = strtolower($gal_type);
			$gallery_global_key = $this->galleryGlobalKey($global_id);

			$pipeline->del($gallery_local_key_prefix . $gal_local_id);
			$pipeline->zRem($galleries_sorted_by_date, $gal_local_id);
			$pipeline->zRem($galleries_sorted_by_date . ":" . $type, $gal_local_id);
			$pipeline->zRem($this->keyFor('likes_galleries', 'likes', 'all', $site_id), $gal_local_id);
			$pipeline->zRem($this->keyFor('likes_galleries', 'likes', $type, $site_id), $gal_local_id);
			$pipeline->zRem($gallery_pageviews_key, $gal_local_id);
			$pipeline->zRem($gallery_pageviews_key . ":" . $type, $gal_local_id);
			$pipeline->hDel($galleries_db_key, $gal_local_id);
			$pipeline->hDel($galleries_db_key_no_local, $global_id);

			$result = true;
		}
		return $result;
	}


	public function addGalleryTransaction($site_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{ // проверить зачем функция
		$result = false;
		$site_id = intval($site_id);
		$key_prefix = $this->default_prefix . $site_id . ":";
		$galleries_db_key = $key_prefix . "galleries";
		$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
		$gallery_likes_key = $key_prefix . "likes_key";
		$gallery_likes_pics_key = $gallery_likes_key . ":pics";
		$gallery_likes_movies_key = $gallery_likes_key . ":movies";
		$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
		$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
		$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
		$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
		$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";

		$galleries_db_key_no_local = $key_prefix . "galleries:no_local";

		$gallery_local_key_prefix = $key_prefix . "gallery:local:";

		if ($pipeline) {
			// установка локальных параметров галлереи, включая тип
			$pipeline->hMset($gallery_local_key_prefix . $gal_local_id, $gallery);
			// добавление в основную (all) таблицу по времени
			$pipeline->zAdd($galleries_sorted_by_date, floatval($added_on), $gal_local_id);
			// добавление в таблицу типов (pics|movies)
			$pipeline->zAdd($galleries_sorted_by_date . ":" . $gal_type, floatval($added_on), $gal_local_id);

			// добавление галеры в таблицу общих лайков
			$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', 'all', $site_id), 0, $gal_local_id);
			// добавление в таблицу лайков по типу (pics|movies)
			$pipeline->zAdd($this->keyFor('likes_galleries', 'likes', $gal_type, $site_id), 0, $gal_local_id);
			// echo "\tAdded to likes (".$gal_type.") G:".$gal_global_id.", L:".$gal_local_id."<br>\n";

			// добавление галеры в таблицу общих пейджвью
			$pipeline->zAdd($gallery_pageviews_key, 0, $gal_local_id);
			// добавление в таблицу пейджвью по типу (pics|movies)
			$pipeline->zAdd($gallery_pageviews_key . ":" . $gal_type, 0, $gal_local_id);
			// echo "\tAdded to pageviews (".$gal_type.") G:".$gal_global_id.", L:".$gal_local_id."<br>\n";

			$pipeline->hSet($galleries_db_key, $gal_local_id, $gal_global_id);
			$pipeline->hSet($galleries_db_key_no_local, $gal_global_id, $gal_local_id);
			$return = true;
		}
		return $result;
	}

	public function addGalleryToSourceTransaction($site_id, $source_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{
		$result = false;
		if ($pipeline) {
			$pipeline->zAdd($this->keyFor('source_galleries', 'date', 'all', $site_id, $source_id), floatval($added_on), $gal_local_id);
			$pipeline->zAdd($this->keyFor('source_galleries', 'date', $gal_type, $site_id, $source_id), floatval($added_on), $gal_local_id);
			$pipeline->zAdd($this->keyFor('source_galleries', 'pageviews', 'all', $site_id, $source_id), 0, $gal_local_id);
			$pipeline->zAdd($this->keyFor('source_galleries', 'pageviews', $gal_type, $site_id, $source_id), 0, $gal_local_id);
			$result = true;
		}
		return $result;
	}


	public function deleteSourceFromGallery($site_id, $source_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{
		$result = false;
		if ($pipeline) {
			$pipeline->zRem($this->keyFor('source_galleries', 'date', 'all', $site_id, $source_id), $gal_local_id);
			$pipeline->zRem($this->keyFor('source_galleries', 'date', $gal_type, $site_id, $source_id), $gal_local_id);
			$pipeline->zRem($this->keyFor('source_galleries', 'pageviews', 'all', $site_id, $source_id), $gal_local_id);
			$pipeline->zRem($this->keyFor('source_galleries', 'pageviews', $gal_type, $site_id, $source_id), $gal_local_id);
			$result = true;
		}
		return $result;
	}

	public function addGalleryToModelTransaction($site_id, $model_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{
		$result = false;

		if ($pipeline) {

			$site_models_likes = $this->keyFor('models', 'likes', 'all', $site_id);
			$site_models_pageviews = $this->keyFor('models', 'pageviews', 'all', $site_id);

			$pipeline->zAdd($this->keyFor('model_galleries', 'date', 'all', $site_id, $model_id), floatval($added_on), $gal_local_id);
			$pipeline->zAdd($this->keyFor('model_galleries', 'date', $gal_type, $site_id, $model_id), floatval($added_on), $gal_local_id);

			$pipeline->zIncrBy($site_models_likes, 0, $model_id); // таблица с лайками
			$pipeline->zIncrBy($site_models_pageviews, 0, $model_id); // таблица с пейджвью

			$result = true;
		}
		return $result;
	}

	public function addGalleryToTagTransaction($site_id, $tag_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{
		$result = false;

		if ($pipeline) {
			$pipeline->zAdd($this->keyFor('tag_galleries', 'date', 'all', $site_id, $tag_id), floatval($added_on), $gal_local_id);
			$pipeline->zAdd($this->keyFor('tag_galleries', 'date', $gal_type, $site_id, $tag_id), floatval($added_on), $gal_local_id);
			$result = true;
		}
		return $result;
	}

	public function deleteTagFromGalleryTr($site_id, $tag_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{
		$result = false;

		// var_dump($this->keyFor('tag_galleries', 'date', $gal_type, $site_id, $tag_id));

		if ($pipeline) {
			$pipeline->zRem($this->keyFor('tag_galleries', 'date', 'all', $site_id, $tag_id), $gal_local_id);
			$pipeline->zRem($this->keyFor('tag_galleries', 'date', $gal_type, $site_id, $tag_id), $gal_local_id);
			$result = true;
		}
		return $result;
	}

	public function deleteModelFromGalleryTr($site_id, $model_id, $gal_global_id, $gal_local_id, $gal_type, $added_on, &$pipeline)
	{
		$result = false;

		if ($pipeline) {

			$site_models_likes = $this->keyFor('models', 'likes', 'all', $site_id);
			$site_models_pageviews = $this->keyFor('models', 'pageviews', 'all', $site_id);

			$pipeline->zRem($site_models_likes, $model_id);
			$pipeline->zRem($site_models_pageviews, $model_id);
			$pipeline->zRem($this->keyFor('model_galleries', 'date', 'all', $site_id, $model_id), $gal_local_id);
			$pipeline->zRem($this->keyFor('model_galleries', 'date', $gal_type, $site_id, $model_id), $gal_local_id);

			$result = true;
		}
		return $result;
	}

	/*
	Transaction stuff
*/

	function getCacheInfo($server_id)
	{
		$return = false;
		if ($this->redis_connection($server_id)) {
			$redis_state = $this->redis->info();
			if ($redis_state && is_array($redis_state)) {
				$return['memory_used'] = $redis_state['used_memory_human'];
				$return['memory_used_max'] = $redis_state['used_memory_peak_human'];
				$return['uptime'] = $redis_state['uptime_in_days'];
				$return['memory_fragmentation'] = $redis_state['mem_fragmentation_ratio'];
			}
		}
		return $return;
	}


	function server_initializeTags($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) {
			$result = $this->initializeTags();
		} else {
			$previous_server = $this->current_server;

			if ($this->redis_connection($server)) {
				$result = $this->initializeTags();
				$this->redis_connection($previous_server);
			} else {
				$log = new Logger(__FUNCTION__ . ", #" . $server, true);
			}
		}
		return $result;
	}

	function initializeTags()
	{
		$result = false;
		if ($this->connected) {
			$tags_list = $this->tags->getAllTags(false, true);
			if ($tags_list && is_array($tags_list)) {
				$this->redis->del($this->tagsDBKey());
				// var_dump($this->tagsDBKey());
				$md5_array = array();
				foreach ($tags_list as $tag) {

					$url_search_term = trim(preg_replace("#([\s*])#im", " ", $tag['name']));
					$url_search_term = strtolower(str_replace("-", " ", $url_search_term));
					$url_search_term = preg_replace("#([^0-9a-z\s])#im", "", $url_search_term);
					$url_search_term = strtolower(str_replace(" ", "-", $url_search_term));

					$url_name_md5 = md5($url_search_term);

					if (!in_array($url_name_md5, $md5_array)) $md5_array[] = $url_name_md5;
					else $log = new Logger("Дублируется тег: #" . $tag['id'] . ", имя: " . $tag['name'], true);

					$tag_key = $this->tagGlobalKey($tag['id']);
					$this->redis->hMset($tag_key, $tag);



					$this->redis->hSet($this->tagsDBKey(), $url_name_md5, $tag['id']);
					$this->redis->hSet($this->tagsDBKey(), $tag['id'], $url_search_term);

					$tag_by_name_key = $this->tagGlobalKey($url_search_term);

					// echo $tag_by_name_key."\n";

					$result = $this->redis->hMset($tag_by_name_key, $tag);

					// echo $tag_by_name_key;
					// echo ($result) ? " - OK " : " - Error";
					// echo "\n";
				}
				// var_dump($this->redis->hGetAll($this->tagsDBKey()));
				$result = true;
			}
		}
		return $result;
	}



	function server_initializeSiteTags($site_id, $server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) {
			$result = $this->initializeSiteUniqueTags($site_id);
		} else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->initializeSiteUniqueTags($site_id);
				$this->redis_connection($previous_server);
			} else {
				$log = new Logger(__FUNCTION__ . ", #" . $server, true);
			}
		}
		return $result;
	}


	function initializeSiteUniqueTags($site_id)
	{
		$result = false;
		if ($this->connected && (int)$site_id > 0) {
			$tags_list = $this->tags->getSitesUniqueTags($site_id);

			// var_dump($tags_list);

			if ($tags_list && is_array($tags_list)) {

				$pipeline = $this->redis->multi(Redis::PIPELINE);
				// $this->redis->del($this->siteTagsDBKey($site_id));

				$md5_array = array();

				foreach ($tags_list as $tag) {
					$url_name_md5 = $tag['u_md5'];

					if (!in_array($url_name_md5, $md5_array)) {
						$md5_array[] = $url_name_md5;
					} else {
						$log = new Logger("Дублируется тег: #" . $tag['id'] . ", имя: " . $tag['name'], true);
					}

					$u_tags_db_array[$url_name_md5] = $tag['id'];
					$u_tags_db_array[$tag['id']] = $tag['u_folder_name'];

					$tag_key = $this->siteUniqueTagKey($site_id, $tag['id']);

					$pipeline->del($tag_key);
					$pipeline->hMset($tag_key, $tag);

					$tag_name = $tag['u_folder_name'];

					$tag_by_name_key = $this->siteUniqueTagKey($site_id, $tag_name);

					$pipeline->del($tag_by_name_key);
					$pipeline->hMset($tag_by_name_key, $tag);
					// if($tag['id'] == 36) var_dump($tag);
				}
				$pipeline->exec();
				// var_dump($tag_by_name_key,$tag_key,$tag,$this->redis->exists($tag_by_name_key),$this->redis->exists($tag_key));
				if ($this->redis->hMSet($this->siteTagsDBKey($site_id) . "tmp", $u_tags_db_array)) {
					$this->redis->rename($this->siteTagsDBKey($site_id) . "tmp", $this->siteTagsDBKey($site_id));
				} else {
					// ошибка
				}

				// var_dump($this->redis->hGetAll($tag_by_name_key), $this->redis->hGetAll($this->siteTagsDBKey($site_id)));


				$result = true;
			}
		}
		return $result;
	}

	function server_initializeSources($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->initializeSources();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->initializeSources();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function initializeSources()
	{
		$result = false;
		if ($this->connected) {
			$sources_list = $this->sources->getAllSources(false);
			if ($sources_list && is_array($sources_list)) {
				$this->redis->del($this->sourcesDBKey());
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				$pipeline->del($this->sourcesDBKey());
				$md5_array = array();
				foreach ($sources_list as $source) {
					$url_search_term = trim(preg_replace("#([\s*])#im", " ", $source['name']));
					$url_search_term = preg_replace("#([^0-9a-z\s])#im", "", $url_search_term);
					$url_search_term = strtolower(str_replace(" ", "-", $url_search_term));
					$url_name_md5 = md5($url_search_term);
					if (!in_array($url_name_md5, $md5_array)) $md5_array[] = $url_name_md5;
					else $log = new Logger("Дублируется платник: #" . $source['id'] . ", имя" . $source['name'], true);
					$source_key = $this->sourceGlobalKey($source['id']);
					$pipeline->hSet($this->sourcesDBKey(), $source['id'], $source['name']);
					$pipeline->hSet($this->sourcesDBKey(), $url_name_md5, $source['id']);
					$pipeline->hMset($source_key, $source);
				}
				$execu = $pipeline->exec();
				$result = true;
			}
		}
		return $result;
	}

	function server_initializeModels($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->initializeModels();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->initializeModels();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function initializeModels()
	{
		$result = false;
		if ($this->connected) {
			$models_list = $this->models->getAllModels();
			if ($models_list && is_array($models_list)) {
				$models_db_key = $this->modelsDBKey();
				$pipeline = $this->redis->multi(Redis::PIPELINE);
				$pipeline->del($models_db_key);
				$md5_array = array();
				foreach ($models_list as $model) {
					$url_search_term = trim(preg_replace("#([\s*])#im", " ", $model['name']));
					$url_search_term = preg_replace("#([^0-9a-z\s])#im", "", $url_search_term);
					$url_search_term = strtolower(str_replace(" ", "-", $url_search_term));
					$url_name_md5 = md5($url_search_term);
					if (!in_array($url_name_md5, $md5_array)) $md5_array[] = $url_name_md5;
					else $log = new Logger("Дублируется платник: #" . $model['id'] . ", имя" . $model['name'], true);
					$model_key = $this->modelGlobalKey($model['id']);
					$pipeline->hSet($models_db_key, $model['id'], $model['name']);
					$pipeline->hSet($models_db_key, $url_name_md5, $model['id']);
					$pipeline->hMset($model_key, $model);
				}
				$execu = $pipeline->exec();
				$result = true;
			}
		}
		return $result;
	}

	function fixOKgalleries()
	{
		$sql = "SELECT gal_id FROM galleries WHERE gal_status='OK' ORDER BY gal_id ASC";
		$this->_db->debug = true;
		$rs = $this->_db->Execute($sql);
		if ($rs) {
			$rows = $rs->GetRows();
			if ($rows) {
				foreach ($rows as $row) {
					$gal_id = $row['gal_id'];
					if (!$this->gallery_cached($gal_id)) {
						$this->cacheGallery($gal_id);
						echo "Fixed OK " . $gal_id . "<br>";
					}
				}
			}
		}
	}


	function queryOKGalleriesToCache()
	{
		$sql = "SELECT gal_id FROM galleries WHERE gal_status='OK' AND 
					gal_id NOT IN (SELECT gal_id FROM cache_galleries_query) ORDER BY gal_id";
		$rs = $this->_db->Execute($sql);
		if ($rs) {
			$rows = $rs->GetRows();
			if ($rows) {
				$first = true;
				$record = "";
				$update = false;
				foreach ($rows as $row) {
					$gal_id = $row['gal_id'];
					if (!$this->gallery_cached($gal_id)) {
						if ($first) {
							$record = " values ";
							$first = false;
						} else $record .= ",";
						$record .= "('" . $gal_id . "', '" . time() . "')";
						$update = true;
					} else echo $gal_id . " set!<br>\n";
				}

				if ($update) {
					$sql = "insert into cache_galleries_query (gal_id, added_on) " . $record . ";";
					$rs = $this->_db->Execute($sql);
				}
			}
		}
	}

	function initializeGalleriesQuery()
	{
		$sql = "select gal_id from cache_galleries_query limit 0, 4000";
		$rs = $this->_db->Execute($sql);
		if ($rs) {
			$rows = $rs->GetRows();
			if ($rows) {
				foreach ($rows as $row) {
					$gal_id = $row['gal_id'];
					if ($this->cacheGallery($gal_id)) {
						$sql = "delete from cache_galleries_query where gal_id = '" . $gal_id . "'";
						if ($rs = $this->_db->Execute($sql)) echo $gal_id . " закэширована<br>";
					}
				}
			}
		}
	}

	function server_initializeGalleries($server)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->new_initGalleries();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->new_initGalleries();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function new_initGalleries()
	{
		$result = false;
		$galleries = $this->gallery->getGalleriesToCache();
		foreach ($galleries as $gallery) {
			echo "init gallery #" . $gallery;
			$r = $this->cacheGallery($gallery);
			echo ", result:";
			var_dump($r);
			if ($r) {
				$this->gallery->addCachedGallery($gallery);
			}
			echo "<br>";
		}

		return $result;
	}

	function initializeGalleries($force_init = false)
	{
		$result = false;
		if ($this->connected) {
			$start_value = $this->query_checkCacheWork();
			$galleries_total_count = $this->gallery->countGalleries();
			// var_dump($force_init ,$galleries_total_count, $start_value);
			if ($start_value > $galleries_total_count) {
				$this->query_deleteGalleryInit();
				//var_dump($galleries_total_count);
				return true;
			}
			if ($force_init || !$start_value) $start_value = 0;
			$finish_value = $start_value + 100;
			$query_id = $this->query_addGalleryInit($start_value, $finish_value);
			if ($galleries_total_count) {
				if ($start_value == 0) $this->redis->del($this->galleriesDBKey());
				$galleries = $this->gallery->getOKGalleries($start_value, 100);
				//var_dump($galleries);
				if ($galleries && is_array($galleries)) {
					foreach ($galleries as $gal_id) {
						$this->cacheGallery($gal_id);
					}
					$result = true;
				}
				if ($finish_value > $galleries_total_count) $this->query_deleteGalleryInit();
				else $this->query_updateGalleryInit($gal_id);
				//var_dump($finish_value);
			}
		}
		return $result;
	}

	function query_addGalleryInit($start_value)
	{
		$start_value = intval($start_value);
		//$this->_db->debug = true;
		$sql = "insert into cache_rebuild_query (query_type, start_value, added_on, item_id, end_value)
					values ('ok_galleries', '" . $start_value . "', '" . time() . "', '0', '0');";
		$rs = $this->_db->Execute($sql);
	}

	function query_updateGalleryInit($start_value = false)
	{
		$start_value = intval($start_value);
		//$this->_db->debug = true;
		$sql = "update cache_rebuild_query set start_value = '" . $start_value . "' where query_type = 'ok_galleries';";
		$rs = $this->_db->Execute($sql);
	}

	function query_deleteGalleryInit($start_value = false)
	{
		$start_value = intval($start_value);
		//$this->_db->debug = true;
		$sql = "delete from cache_rebuild_query where query_type = 'ok_galleries';";
		$rs = $this->_db->Execute($sql);
	}

	function query_checkCacheWork($type = 'ok_galleries')
	{
		if (preg_match("#^(ok_galleries|models|tags|sources|site_galleries)$#", $type) && $this->_db) {
			//$this->_db->debug = true;
			$sql = "select id,start_value from cache_rebuild_query where query_type = '" . $type . "'";
			$rs = $this->_db->Execute($sql);
			if ($rs) {
				$rows = $rs->GetRows();
				if ($rows) return $rows[0]['start_value'];
			}
		}
		return false;
	}

	function gallery_cached($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = false;
		if ($this->connected) {
			$gallery_global_key = $this->galleryGlobalKey($gal_id);
			if ($this->redis->exists($gallery_global_key)) $result = true;
			//var_dump($this->redis->hGetAll($gallery_global_key));
		}
		return $result;
	}

	function server_tagsCount($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->tagsCount();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->tagsCount();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function tagsCount()
	{
		$result = false;
		if ($this->connected) $result = $this->redis->hLen($this->tagsDBKey());
		// var_dump($this->redis->hGetAll($this->tagsDBKey()));
		if ($result) $result = intval(ceil($result / 2));
		return $result;
	}

	function server_sourcesCount($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->sourcesCount();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->sourcesCount();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function sourcesCount()
	{
		$result = false;
		if ($this->connected) {
			$result = $this->redis->hLen($this->sourcesDBKey());
			if ($result) $result = intval(ceil($result / 2));
		}
		return $result;
	}

	function server_modelsCount($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->modelsCount();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->modelsCount();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function modelsCount()
	{
		$result = false;
		if ($this->connected) {
			$result = $this->redis->hLen($this->modelsDBKey());
			if ($result) $result = intval(ceil($result / 2));
		}
		return $result;
	}

	function server_galleriesCount($server = 0)
	{
		$result = false;
		$server = intval($server);
		if ($this->current_server == $server) $result = $this->galleriesCount();
		else {
			$previous_server = $this->current_server;
			if ($this->redis_connection($server)) {
				$result = $this->galleriesCount();
				$this->redis_connection($previous_server);
			} else $log = new Logger(__FUNCTION__ . ", #" . $server, true);
		}
		return $result;
	}

	function galleriesCount()
	{
		$result = false;
		if ($this->connected) $result = $this->redis->hLen($this->galleriesDBKey());
		return $result;
	}

	function siteGalleriesCountAdditionalServer($site_id)
	{
		return $this->siteGalleriesCount($site_id, true);
	} // additional_server который уже не нужен

	function siteGalleriesCount($site_id, $additional_cache_server = false)
	{
		$result = false;
		$server_changed = false;
		$site_id = intval($site_id);

		$site_switched = $this->sites->switchSite($site_id);

		if ($site_switched) {

			if (!$additional_cache_server) $site_redis_server = $this->sites->redisServer();
			else $site_redis_server = $this->sites->redisServerAdditional();

			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}

			if ($this->connected && $site_id) {
				$key_prefix = $this->default_prefix . $site_id . ":";
				if ($this->sites->ifLocalId()) {
					$galleries_db_key = $key_prefix . "galleries";
					$local_id_first = true;
				} else {
					$galleries_db_key = $key_prefix . "galleries:no_local";
					$local_id_first = false;
				}
				$result = $this->redis->hLen($galleries_db_key);
			} else {
				$log = new Logger(__METHOD__ . ": #" . $site_redis_server, true);
			}

			if ($server_changed) $this->redis_connection($previous_server);
		}
		return $result;
	}

	function server_cacheGlobalGallery($gal_id)
	{

		$result = false;
		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			if ($cache_server_id != $previous_server) $this->redis_connection($cache_server_id);
			if ($this->connected) {
				try {
					$gallery_cached = $this->cacheGallery($gal_id);
				} catch (Exception $e) {
					echo 'Caught Redis exception: ' . $e->getMessage() . '<br>';
				}
				if ($gallery_cached) {
					// echo "Закешировано на сервере #".$cache_server_id.", ".$cache_server_name."<br>";
					$log = new Logger(__METHOD__ . ": GID#" . $gal_id . " закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name);
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ": Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", галера: " . $gal_id, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id . ", " . $cache_server_name, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}



	function cacheGallery($gal_id, $caching_server_id = false)
	{

		$gal_id = intval($gal_id);
		$result = false;
		$previous_server = false;

		if ($caching_server_id !== false) {

			if ($this->current_server != $caching_server_id) {
				$previous_server = $this->current_server;
				$this->redis_connection($caching_server_id);
			}
		}

		if ($this->connected) {

			if (!is_object($this->gallery)) {
				$this->gallery = new Galleries;
			}


			$gallery_global_key = $this->galleryGlobalKey($gal_id);
			$galleries_db_key = $this->galleriesDBKey();
			$gallery_info = $this->gallery->getMainGalleryInfo($gal_id);
			if ($gallery_info && is_array($gallery_info) && $gallery_info['status'] == 'OK') {
				$gallery_info['models'] = serialize($this->gallery->getGalleryModels($gal_id));
				$gallery_info['tags'] = serialize($this->gallery->getGalleryTags($gal_id));
				if (isset($gallery_info['paysite']['id'])) $gallery_info['paysite'] = $gallery_info['paysite']['id'];
				if ($gallery_info['type'] == 'Pics' || $gallery_info['type'] == 'gif') {
					$images = $this->gallery->getAllImages($gal_id);
					if ($images && is_array($images)) {
						$gallery_info['images'] = serialize($images);
						$images_ratio = $this->gallery->getImagesRatio($gal_id);

						if ($images_ratio) {
							$gallery_info['images_ratio'] = serialize($images_ratio);
						}

						$pipeline = $this->redis->multi(Redis::PIPELINE);
						$pipeline->hMset($gallery_global_key, $gallery_info);
						$pipeline->hSet($galleries_db_key, $gal_id, 'OK');
						$pipeline->exec();

						$result = true;
						// var_dump($this->redis->hGetAll($gallery_global_key));
						// echo "cache OK #".$gal_id."<br>";
					} else {
						// echo "cache not OK #".$gal_id."<br>";
						// var_dump($gallery_info);
						$log = new Logger("Кэш: Галера " . $gal_id . " не инициализирована - нет изображений, но статус ок", true);
					}
				} elseif ($gallery_info['type'] == 'Movies' && (isset($gallery_info['video_url']) || isset($gallery_info['video_embed']))) {
					$images = $this->gallery->getAllImages($gal_id);
					if ($images && is_array($images)) {
						$gallery_info['images'] = serialize($images);
						$pipeline = $this->redis->multi(Redis::PIPELINE);
						$pipeline->hMset($gallery_global_key, $gallery_info);
						$pipeline->hSet($galleries_db_key, $gal_id, 'OK');
						$pipeline->exec();
						$result = true;
						// echo " ".__METHOD__.":".$gal_id." Cached!";
					} else {
						$log = new Logger("Кэш: Галера " . $gal_id . " не инициализирована - нет изображений, но статус ок", true);
					}
				}
			} else $log = new Logger("Кэш: Галера " . $gal_id . " не инициализирована - не найдена в базе", true);

			if ($previous_server !== false) {
				$this->redis_connection($previous_server);
			}
		}


		return $result;
	}

	function cacheGalleryCheck($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = false;
		if ($this->connected) {
			$gallery_global_key = $this->galleryGlobalKey($gal_id);
			$this->redis->hGetAll($gallery_global_key);
		}
		return $result;
	}

	function server_cacheModel($model_id)
	{

		$result = false;
		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			if ($cache_server_id != $previous_server) $this->redis_connection($cache_server_id);
			if ($this->connected) {
				if ($this->cacheModel($model_id)) {
					echo "Закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ":Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", модель: " . $model_id, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id . ", " . $cache_server_name, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	function cacheModel($model_id, $caching_server_id = false)
	{
		$model_id = intval($model_id);
		$result = false;
		$previous_server = false;

		if ($caching_server_id !== false) {

			if ($this->current_server != $caching_server_id) {
				$previous_server = $this->current_server;
				$this->redis_connection($caching_server_id);
			}
		}

		if ($this->connected) {
			if ($this->models === false) {
				$this->models = new CModels;
			}
			if ($model_info = $this->models->getModel($model_id)) {
				$models_db_key = $this->modelsDBKey();
				$model_key = $this->modelGlobalKey($model_id);
				$this->redis->del($model_key);
				$this->redis->hMset($model_key, $model_info);

				if ($this->redis->exists($model_key)) {
					$this->redis->hSet($models_db_key, $model_info['id'], $model_info['name']);

					$url_search_term = trim(preg_replace("#([\s*])#im", " ", $model_info['name']));
					$url_search_term = preg_replace("#([^0-9a-z\s])#im", "", $url_search_term);
					$url_search_term = strtolower(str_replace(" ", "-", $url_search_term));
					$url_name_md5 = md5($url_search_term);
					$this->redis->hSet($models_db_key, $url_name_md5, $model_info['id']);

					$result = true;
				} else {
					$log = new Logger("Кэш: Ошибка добавления в кэш Redis, модель " . $model_id, true);
				}
			}
		} else {
			$log = new Logger("Кэш: Модель " . $model_id . " не инициализирована - сбой Redis", true);
		}
		if ($previous_server !== false) {
			$this->redis_connection($previous_server);
		}
		return $result;
	}

	function server_cacheTag($tag_id)
	{

		$result = false;
		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			if ($cache_server_id != $previous_server) $this->redis_connection($cache_server_id);
			if ($this->connected) {
				if ($this->cacheTag($tag_id)) {
					echo "Закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ":Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", тег: " . $tag_id, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id . ", " . $cache_server_name, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	function cacheTag($tag_id)
	{
		$tag_id = intval($tag_id);
		$result = false;
		if ($this->connected) {
			if ($tag_info = $this->tags->getTag($tag_id)) {
				$tags_db_key = $this->tagsDBKey();
				$tag_key = $this->tagGlobalKey($tag_id);
				$this->redis->del($tag_key);
				$this->redis->hMset($tag_key, $tag_info);
				if ($this->redis->exists($tag_key)) {
					$this->redis->hSet($this->tagsDBKey(), $tag_info['id'], $tag_info['name']);
					$this->redis->hSet($this->tagsDBKey(), $tag_info['id'], $tag_info['name']);
					$result = true;
				} else $log = new Logger("Кэш: Ошибка добавления в кэш Redis, тег " . $tag_id, true);
			}
		} else $log = new Logger("Кэш: Тег " . $tag_id . " не инициализирована - сбой Redis", true);
		return $result;
	}

	function server_cacheTagByName($tag_name)
	{
		$result = false;
		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			if ($cache_server_id != $previous_server) $this->redis_connection($cache_server_id);
			if ($this->connected) {
				if ($this->cacheTagByName($tag_name)) {
					echo "Закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ":Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", cacheTagByName - тег: " . $tag_name, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id . ", " . $cache_server_name, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	function cacheTagByName($tag_name)
	{
		$result = false;
		if ($this->connected) {
			$tag_name = strtolower(trim($_GET['tag_name']));
			$tag_name = preg_replace("/[^a-z0-9-]/", "", $tag_name);
			if ($tag_name != 'old-young') $tag_name = preg_replace("/[-]/", " ", $tag_name);
			if ($tag_info = $this->tags->getTag($tag_name)) {
				$tags_db_key = $this->tagsDBKey();
				$tag_key = $this->tagGlobalKey($tag_name);
				$this->redis->del($tag_key);
				$this->redis->hMset($tag_key, $tag_info);

				$url_search_term = trim(preg_replace("#([\s*])#im", " ", $tag_name));
				$url_search_term = preg_replace("#([^0-9a-z\s])#im", "", $url_search_term);
				$url_search_term = strtolower(str_replace(" ", "-", $url_search_term));
				$url_name_md5 = md5($url_search_term);

				if (!in_array($url_name_md5, $md5_array)) $md5_array[] = $url_name_md5;
				else $log = new Logger("Дублируется тег: #" . $tag['id'] . ", имя: " . $tag['name'], true);

				$this->redis->hSet($this->tagsDBKey(), $url_name_md5, $tag_id);

				if ($this->redis->exists($tag_key)) $result = true;
				else $log = new Logger("Кэш: Ошибка добавления в кэш Redis, тег " . $tag_name, true);
			}
		} else $log = new Logger("Кэш: Не инициализирован - сбой Redis", true);
		return $result;
	}

	function server_cacheSource($source_id)
	{
		$result = false;
		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			if ($cache_server_id != $previous_server) $this->redis_connection($cache_server_id);
			if ($this->connected) {
				if ($this->cacheSource($source_id)) {
					//echo "Закешировано на сервере #".$i."<br>";
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ": Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", cacheSource: " . $source_id, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	function cacheSource($source_id)
	{
		$source_id = intval($source_id);
		$result = false;
		if ($this->connected) {
			$sources_db_key = $this->sourcesDBKey();
			$source_key = $this->sourceGlobalKey($source_id);
			if ($source_info = $this->sources->getSource($source_id)) {
				$this->redis->hSet($sources_db_key, $source_info['id'], $source_info['name']);
				$this->redis->del($source_key);
				$this->redis->hMset($source_key, $source_info);
				if ($this->redis->exists($source_key)) $result = true;
				else $log = new Logger("Кэш: Ошибка добавления в кэш Redis, источник " . $source_id, true);
			}
			//var_dump($source_info);
		} else $log = new Logger("Кэш: Источник " . $source_id . " не инициализирована - сбой Redis", true);
		return $result;
	}

	function server_cacheSiteInfo($site_id, $alter_server = false)
	{
		$result = false;
		$cache_server_id = false;

		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			$this->redis_connection($cache_server_id);

			if ($this->connected) {
				if ($this->cacheSiteInfo($site_id)) {
					echo "Закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ": Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", cacheSiteInfo: " . $site_id, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	function cacheSiteInfo($site_id)
	{
		$site_id = intval($site_id);
		$result = false;
		if ($this->connected) {
			$site_info = $this->sites->getSite($site_id, true);
			if ($site_info) {
				$site_key = $this->siteGlobalKey($site_id);
				echo "Key: " . $site_key . "<br>";
				$this->redis->del($site_key);
				$this->redis->hMset($site_key, $site_info);
				if ($this->redis->exists($site_key)) $result = true;
				else $log = new Logger("Кэш: Ошибка добавления в кэш Redis, сайт " . $site_id, true);
			}
		} else $log = new Logger("Кtagsэш: Сайт " . $site_id . " не инициализирован - сбой Redis", true);
		return $result;
	}

	function siteCached($site_id)
	{
		$site_id = intval($site_id);
		$result = false;
		if ($this->connected) {
			$site_key = $this->siteGlobalKey($site_id);
			$result = $this->redis->exists($site_key);
		} else $log = new Logger(__METHOD__ . ": Нет коннекта к редису", true);
		return $result;
	}

	function server_updateGalleryModels($gal_id)
	{
		$result = false;
		$previous_server = $this->current_server;

		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			$this->redis_connection($cache_server_id);

			if ($this->connected) {
				if ($this->updateGalleryModels($gal_id)) {
					// echo "Закешировано на сервере #".$cache_server_id."<br>";
					$log = new Logger("PID #" . getmypid() . ", " . __METHOD__ . ", Current server: " . $this->current_server . " Init OK", true);
					$result = true;
				} else {
					$log = new Logger(__METHOD__ . ":Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", updateGalleryModels: " . $gal_id, true);
				}
			} else $log = new Logger("Нет коннекта к редису #" . $cache_server_id, true);
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	function updateGalleryModels($gal_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		if ($this->connected) {
			$gallery_global_key = $this->galleryGlobalKey($gal_id);
			$status = $this->gallery->getStatus($gal_id);
			if ($status && $status == 'OK') {
				$gallery_global_key = $this->galleryGlobalKey($gal_id);
				if (!$this->redis->exists($gallery_global_key)) $result = $this->cacheGallery($gal_id);
				else {
					$models = $this->gallery->getModels($gal_id);
					$models = serialize($models);
					$this->redis->hSet($gallery_global_key, 'models', $models);
					$result = true;
				}
			}
		} else $log = new Logger("Кэш: Галера " . $gal_id . ", ошибка апдейта моделей. Нет коннекта к Redis", true);
		return $result;
	}

	function server_updateGalleryTags($gal_id)
	{
		$result = false;
		$previous_server = $this->current_server;
		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			$this->redis_connection($cache_server_id);
			if ($this->connected) {
				if ($this->updateGalleryTags($gal_id)) {
					//echo "Закешировано на сервере #".$i."<br>";
					$result = true;
				} else {
					$log = new Logger(__METHOD__ . ": Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", updateGalleryTags: " . $gal_id, true);
				}
			} else {
				$log = new Logger("Нет коннекта к редису #" . $cache_server_id, true);
			}
		}

		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);

		return $result;
	}

	private function updateGalleryTags($gal_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		if ($this->connected) {
			$status = $this->getStatus($gal_id);
			if ($status && $status == 'OK') {
				$gallery_global_key = $this->galleryGlobalKey($gal_id);
				if (!$this->redis->exists($gallery_global_key)) $result = $this->cacheGallery($gal_id);
				else {
					$tags = $this->gallery->getTags($gal_id);
					$tags = serialize($tags);
					$this->redis->hSet($gallery_global_key, 'tags', $tags);
					$result = true;
				}
			}
		} else $log = new Logger("Кэш: Галера " . $gal_id . ", ошибка апдейта тегов. Нет коннекта к Redis", true);
		return $result;
	}

	function server_updateGalleryImages($gal_id)
	{
		$result = false;
		$previous_server = $this->current_server;
		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			$this->redis_connection($cache_server_id);
			if ($this->connected) {
				if ($this->updateGalleryImages($gal_id)) {
					//echo "Закешировано на сервере #".$cache_server_id."<br>";
					$result = true;
				} else {
					echo "Ошибка! Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ": Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", updateGalleryImages: " . $gal_id, true);
				}
			} else $log = new Logger(__METHOD__ . ": Нет коннекта к редису #" . $cache_server_id, true);
		}
		if ($cache_server_id != $previous_server) $this->redis_connection($previous_server);
		return $result;
	}

	private function updateGalleryImages($gal_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		if ($this->connected) {
			$status = $this->getStatus($gal_id);
			if ($status && $status == 'OK') {
				$gallery_global_key = $this->galleryGlobalKey($gal_id);
				if (!$this->redis->exists($gallery_global_key)) $result = $this->cacheGallery($gal_id);
				else {
					$images = $this->gallery->getAllImages($gal_id);
					$images = serialize($images);
					$this->redis->hSet($gallery_global_key, 'images', $images);
					$result = true;
				}
			}
		} else $log = new Logger("Кэш: Галера " . $gal_id . ", ошибка апдейта тегов. Нет коннекта к Redis", true);
		return $result;
	}

	function server_deleteGalleryCache($gal_id)
	{
		$result = false;
		$previous_server = $this->current_server;
		CachingServers::reset();

		while (CachingServers::next()) {

			$cache_server_id = CachingServers::currentID();
			$cache_server_name = CachingServers::currentName();

			$this->redis_connection($cache_server_id);

			if ($this->connected) {
				if ($this->deleteGalleryCache($gal_id)) {
					echo "Удалено из кэша на сервере #" . $cache_server_id . "<br>";
					$result = true;
				} else {
					echo "Ошибка! Не удалено из кэша на сервере #" . $cache_server_id . ", " . $cache_server_name . "<br>";
					$log = new Logger(__METHOD__ . ": Не закешировано на сервере #" . $cache_server_id . ", " . $cache_server_name . ", deleteGalleryCache: " . $gal_id, true);
				}
			} else {
				$log = new Logger(__METHOD__ . "Нет коннекта к редису #" . $cache_server_id, true);
			}
		}

		if ($cache_server_id != $previous_server) {
			$this->redis_connection($previous_server);
		}

		return $result;
	}

	function deleteGalleryCache($gal_id)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($this->connected && $gal_id > 0) {
			$gallery_global_key = $this->galleryGlobalKey($gal_id);

			$pipe = $this->redis->multi(Redis::PIPELINE);
			$pipe->hDel($this->galleriesDBKey(), $gal_id);
			$pipe->del($gallery_global_key);
			$result = $pipe->exec() ? true : false;
		} else {
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
		}

		return $result;
	}

	function deleteGalleryCacheTransaction($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;

		if ($this->pipeline) {
			if ($gal_id > 0) {
				$gallery_global_key = $this->galleryGlobalKey($gal_id);
				$this->pipeline->hDel($this->galleriesDBKey(), $gal_id);
				$this->pipeline->del($gallery_global_key);
				$result = true;
			} else {
				$log = new Logger(__METHOD__ . ": gal_id < 0, транзакция провалена - глобальный кеш галеры удалить не возможно", true);
			}
		} else {
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
		}

		return $result;
	}



	function deleteGalleryFromSite($site_id, $gal_id, $local = true)
	{
		$result = false;
		$gal_id = intval($gal_id);
		$site_id = intval($site_id);
		if ($this->connected && $gal_id && $site_id) {
			$key_prefix = $this->default_prefix . $site_id . ":";
			$galleries_db_key = $key_prefix . "galleries";
			$galleries_sorted_by_date = $key_prefix . "galleries_by_date";
			$gallery_likes_key = $key_prefix . "likes_key";
			$gallery_likes_pics_key = $gallery_likes_key . ":pics";
			$gallery_likes_movies_key = $gallery_likes_key . ":movies";
			$gallery_likes_gifs_key = $gallery_likes_key . ":gif";
			$gallery_pageviews_key = $key_prefix . "galleries:pageviews";
			$gallery_pageviews_pics_key = $gallery_pageviews_key . ":pics";
			$gallery_pageviews_movies_key = $gallery_pageviews_key . ":movies";
			$gallery_pageviews_gifs_key = $gallery_pageviews_key . ":gif";

			$global_gallery_id = $this->redis->hGet($galleries_db_key, $gal_id);
			if ($global_gallery_id) {
				$gallery_global_key = $this->galleryGlobalKey($global_gallery_id);
				$galleries_db_key = $key_prefix . "galleries";
				$galleries_db_no_local_key = $key_prefix . "galleries:no_local";
				$this->redis->hDel($galleries_db_key, $gal_id);
				$this->redis->hDel($galleries_db_no_local_key, $gal_id);
				$this->redis->zRem($gallery_likes_key, $gal_id);
				$this->redis->zRem($gallery_pageviews_key, $gal_id);
				$this->redis->zRem($gallery_likes_pics_key, $gal_id);
				$this->redis->zRem($gallery_pageviews_pics_key, $gal_id);
				$this->redis->zRem($gallery_likes_movies_key, $gal_id);
				$this->redis->zRem($gallery_pageviews_movies_key, $gal_id);
				$this->redis->zRem($gallery_likes_gifs_key, $gal_id);
				$this->redis->zRem($gallery_pageviews_gifs_key, $gal_id);
				$this->redis->zRem($galleries_sorted_by_date, $gal_id);
				$this->redis->zRem($galleries_sorted_by_date . ":pics", $gal_id);
				$this->redis->zRem($galleries_sorted_by_date . ":movies", $gal_id);
				$this->redis->zRem($galleries_sorted_by_date . ":gif", $gal_id);
			} else $log = new Logger("Удаление: не найден глобал айди для " . $gal_id . ", сайта #" . $site_id, true);
			$result = true;
		} else $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
		return $result;
	}

	function deleteGallery($gal_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		if ($this->connected && $gal_id) {
			//
		}
		return $result;
	}

	function putRelatedToStorage($site_id, $gal_id, $galleries_list)
	{
		$result = false;

		if ($site_id > 0 && $gal_id > 0 && $galleries_list && is_array($galleries_list) && count($galleries_list)) {
			$gal_id = intval($gal_id);
			$site_id = intval($site_id);
			$key_prefix = $this->default_prefix . $site_id . ":";
			$gallery_related_key = $key_prefix . "related_for:" . $gal_id;

			$server_changed = false;
			$previous_server = false;

			$site_switched = $this->sites->switchSite($site_id);

			//var_dump($site_switched);
			if ($site_switched) {
				$site_redis_server = $this->sites->redisServer();
				if ($this->current_server != $site_redis_server) {
					$server_changed = true;
					$previous_server = $this->current_server;
					$this->redis_connection($site_redis_server);
					//echo "switch to ".$site_redis_server;
				}
				if ($this->connected) {

					$pipe = $this->redis->multi(Redis::PIPELINE);
					foreach ($galleries_list as $related_gallery_id) {
						$pipe->rpush($gallery_related_key . "_new", $related_gallery_id);
					}
					// по любому берем 80 поледних шалер и впихиваем - для массы
					//echo "Последние: ";
					//var_dump($last_added);
					$pipe->rename($gallery_related_key . "_new", $gallery_related_key);
					$pipe->expire($gallery_related_key, 86400);
					$result = $pipe->exec();
					//var_dump($this->redis->exists($gallery_related_key));
					//var_dump($this->redis->llen($gallery_related_key));
					//echo $gallery_related_key;
					if ($server_changed) $this->redis_connection($previous_server);
				} else {
					$log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ", не возможно переключиться к сайту #" . $site_id . ", ошибка ДБ", true);
			}
		} else {
			echo "list error";
		}

		return $result;
	}

	function getRelatedGalleries($site_id, $gal_id, $gallery_on_globals)
	{
		$result = false;
		$gal_id = intval($gal_id);
		$site_id = intval($site_id);
		$key_prefix = $this->default_prefix . $site_id . ":";
		$gallery_related_key = $key_prefix . "related_for:" . $gal_id;
		$server_changed = false;
		$previous_server = false;
		$site_switched = $this->sites->switchSite($site_id);
		//var_dump($site_switched);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				//echo "switch to ".$site_redis_server;
			}
			if ($this->connected) {
				$galleries_list = $this->gallery->getRelatedGalleries($site_id, $gal_id, $gallery_on_globals);
				if (is_array($galleries_list) && count($galleries_list) > 0) {
					$pipe = $this->redis->multi(Redis::PIPELINE);
					foreach ($galleries_list as $related_gallery_id => $weight) {
						$pipe->rpush($gallery_related_key . "_new", $related_gallery_id);
					}
					// по любому берем 80 поледних шалер и впихиваем - для массы
					//echo "Последние: ";
					//var_dump($last_added);
					$pipe->rename($gallery_related_key . "_new", $gallery_related_key);
					$pipe->expire($gallery_related_key, 36600);
					$result = $pipe->exec();
					//var_dump($this->redis->exists($gallery_related_key));
					//var_dump($this->redis->llen($gallery_related_key));
					//echo $gallery_related_key;
				}
				if ($server_changed) $this->redis_connection($previous_server);
			} else  $log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
		} else  $log = new Logger(__METHOD__ . ", не возможно переключиться к сайту #" . $site_id . ", ошибка ДБ", true);
		return $result;
	}

	function getErrorBanners($page = 0)
	{
		$result = false;
		$banner_fail_key = SCRIPT_PRE . ":banners:error";
		if ($this->connected) {
			$count = 50;
			$page = $count * intval($page);
			$count += $page;
			$error_banners = $this->redis->zRevRange($banner_fail_key, $page, $count + $page);
			if ($error_banners && is_array($error_banners)) {
				foreach ($error_banners as $value) {
					list($paysite_id, $spot_id) = explode("|", $value);
					$result[$value]['paysite'] = $paysite_id;
					$result[$value]['spot'] = $spot_id;
				}
			}
		}
		return $result;
	}

	function countErrorBanners()
	{
		$result = false;
		$banner_fail_key = SCRIPT_PRE . ":banners:error";
		if ($this->connected) $result = $this->redis->zCard($banner_fail_key);
		return $result;
	}

	function removeErrorBanner($paysite_id, $spot_id)
	{
		$result = false;
		$banner_fail_key = SCRIPT_PRE . ":banners:error";
		$paysite_id = intval($paysite_id);
		$spot_id = intval($spot_id);
		if ($this->connected && $spot_id && $paysite_id) $result = $this->redis->zRem($banner_fail_key, $paysite_id . "|" . $spot_id);
		return $result;
	}

	function clearErrorBanners()
	{
		$result = false;
		$banner_fail_key = SCRIPT_PRE . ":banners:error";
		if ($this->connected) $result = $this->redis->del($banner_fail_key);
		return $result;
	}


	function updateTaggedGalleryCounter($user_id)
	{
		$result = false;
		$user_id = intval($user_id);
		if ($user_id && $this->connected) {
			$date = date("Y-m-d", time());
			$worker_tagged_gallery = $this->galleries_tagged_counter_prefix . $user_id . ":" . $date;
			$result = $this->redis->incr($worker_tagged_gallery);
			$this->redis->expire($worker_tagged_gallery, 3024000);
		}
		return $result;
	}

	function getTaggedGalleryCounter($user_id)
	{
		$result = false;
		$user_id = intval($user_id);
		if ($user_id && $this->connected) {
			$date = date("Y-m-d", time());
			$worker_tagged_gallery = $this->galleries_tagged_counter_prefix . $user_id . ":" . $date;
			$result = $this->redis->get($worker_tagged_gallery);
			if (!$result) $result = 0;
		}
		return $result;
	}

	/* операции с поисковыми фразами каждого сайта */

	function unapprovedSearchesCount($site_id)
	{
		$result = false;
		$site_id = (int)$site_id;
		$server_changed = false;
		$previous_server = false;
		$site_switched = $this->sites->switchSite($site_id);

		$site_searches_key = SCRIPT_PRE . ":" . $site_id . ":searches_list";

		//var_dump($site_switched);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$result = $this->redis->llen($site_searches_key);
				// var_dump($result,$site_redis_server );
				if ($server_changed) $this->redis_connection($previous_server);
			} else  $log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
		} else  $log = new Logger(__METHOD__ . ", не возможно переключиться к сайту #" . $site_id . ", ошибка ДБ", true);
		return $result;
	}

	function getUnapprovedSearches($site_id)
	{
		$site_id = (int)$site_id;
		$server_changed = false;
		$previous_server = false;
		$site_switched = $this->sites->switchSite($site_id);

		$site_searches_key = SCRIPT_PRE . ":" . $site_id . ":searches_list";

		//var_dump($site_switched);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$search_worker = new Searches();
				$searches_count = $this->redis->llen($site_searches_key);
				while ($searches_count) {
					$searches_count--;
					$search_term = $this->redis->rpop($site_searches_key);
					if ($search_term) $search_worker->insertSearchUnique($search_term, $site_id);
				}

				if ($server_changed) $this->redis_connection($previous_server);
			} else  $log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
		} else  $log = new Logger(__METHOD__ . ", не возможно переключиться к сайту #" . $site_id . ", ошибка ДБ", true);
	}

	function initApprovedSearch($site_id)
	{
		$result = false;
		$site_id = (int)$site_id;
		$server_changed = false;
		$previous_server = false;
		$site_switched = $this->sites->switchSite($site_id);

		$site_searches_key = SCRIPT_PRE . ":" . $site_id . ":searches_list:approved";

		//var_dump($site_switched);
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
			}
			if ($this->connected) {
				$search_worker = new Searches();

				$searches = $search_worker->getSearches(1000, 0, $site_id, true);
				if ($searches) {
					$pipeline = $this->redis->multi(Redis::PIPELINE);
					foreach ($searches as $search_item) {
						$pipeline->lpush($site_searches_key, $search_item['search_key']);
					}
					$result = $pipeline->exec();
				}
				if ($server_changed) $this->redis_connection($previous_server);
			} else  $log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
		} else  $log = new Logger(__METHOD__ . ", не возможно переключиться к сайту #" . $site_id . ", ошибка ДБ", true);
		return $result;
	}



	function getSiteUsersListCount($site_id, $cache_server_id)
	{
		$result = false;
		$previous_server = false;

		$site_id = (int)$site_id;
		$cache_server_id = (int)$cache_server_id;

		if ($site_id > 0 && $cache_server_id >= 0) {
			if ($this->current_server != $cache_server_id) {
				$previous_server = $this->current_server;
				$this->redis_connection($cache_server_id);
			}

			if ($this->connected) {
				$key_prefix = SCRIPT_PRE . ":" . $site_id . ":";

				$total_users_db_key = $key_prefix . "db:visitors";

				$result = $this->redis->zCard($total_users_db_key);


				if ($previous_server !== false) {
					$this->redis_connection($previous_server);
				}
			} else {
				$log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
			}
		} else {
			$log = new Logger(__METHOD__ . " неверные входящие данные", true);
		}
		return $result;
	}



	function getSiteUsersList($site_id, $cache_server_id)
	{
		$result = false;
		$previous_server = false;

		$site_id = (int)$site_id;
		$cache_server_id = (int)$cache_server_id;

		if ($site_id > 0 && $cache_server_id >= 0) {
			if ($this->current_server != $cache_server_id) {
				$previous_server = $this->current_server;
				$this->redis_connection($cache_server_id);
			}

			if ($this->connected) {
				$key_prefix = SCRIPT_PRE . ":" . $site_id . ":";

				$total_users_db_key = $key_prefix . "db:visitors";

				$result = $this->redis->zRange($total_users_db_key, 1, 10);


				if ($previous_server !== false) {
					$this->redis_connection($previous_server);
				}
			} else {
				$log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
			}
		} else {
			$log = new Logger(__METHOD__ . " неверные входящие данные", true);
		}
		return $result;
	}




	function getUserInfo($site_id, $cache_server_id, $user_id)
	{
		$result = false;
		$previous_server = false;

		$site_id = (int)$site_id;
		$cache_server_id = (int)$cache_server_id;

		if ($site_id > 0 && $cache_server_id >= 0) {
			if ($this->current_server != $cache_server_id) {
				$previous_server = $this->current_server;
				$this->redis_connection($cache_server_id);
			}

			if ($this->connected) {
				$key_prefix = SCRIPT_PRE . ":" . $site_id . ":";

				$total_users_db_key = $key_prefix . "db:visitors";
				// $this->users_db = $this->key_prefix."db:visitors";

				$visitors_db_key_prefix = $key_prefix . "visitor:";
				$users_viewed_pages_db_prefix = $key_prefix . "db:visitor_viewed_pages:";
				$gallery_likes_key = $key_prefix . "likes_key";

				$user_key = $visitors_db_key_prefix . $user_id; // hset

				$user_history_pics_key = $visitors_db_key_prefix . $user_id . ":pics"; //zList
				$user_history_movies_key = $visitors_db_key_prefix . $user_id . ":movies"; //zList

				$user_allowed_to_like_key = $key_prefix . "user_pages_allowed_to_like:" . $user_id; //zList
				$user_viewed_pages_key = $users_viewed_pages_db_prefix . $user_id; // zList
				$user_favourite_galleries_key = $key_prefix . "visitor:favourites:" . $user_id . ":" . "movies"; // zlist
				$user_favourite_galleries_key_pics = $key_prefix . "visitor:favourites:" . $user_id . ":" . "pics"; // zlist

				$result = $this->redis->hGetAll($user_favourite_galleries_key_pics);

				if ($previous_server !== false) {
					$this->redis_connection($previous_server);
				}
			} else {
				$log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
			}
		} else {
			$log = new Logger(__METHOD__ . " неверные входящие данные", true);
		}
		return $result;
	}















	// таблица вероятно не работает
	function getSiteUsersFavouriteTableCount($site_id, $cache_server_id)
	{
		$result = false;
		$previous_server = false;

		$site_id = (int)$site_id;
		$cache_server_id = (int)$cache_server_id;

		if ($site_id > 0 && $cache_server_id >= 0) {
			if ($this->current_server != $cache_server_id) {
				$previous_server = $this->current_server;
				$this->redis_connection($cache_server_id);
			}

			if ($this->connected) {
				$key_prefix = SCRIPT_PRE . ":" . $site_id . ":";

				$gallery_favourites_key = $key_prefix . "db:favourites"; // zlist не помню зачем, проверить таблицы

				$result = $this->redis->zCard($gallery_favourites_key);


				if ($previous_server !== false) {
					$this->redis_connection($previous_server);
				}
			} else {
				$log = new Logger(__METHOD__ . ", не возможно подключиться к редису #" . $site_redis_server, true);
			}
		} else {
			$log = new Logger(__METHOD__ . " неверные входящие данные", true);
		}
		return $result;
	}



	function getShortDailyStats($site_id, $days = 7)
	{
		$result = false;
		$site_id = intval($site_id);
		$site_switched = $this->sites->switchSite($site_id);
		$server_changed = false;
		if ($site_switched) {
			$site_redis_server = $this->sites->redisServer();
			$log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
			if ($this->current_server != $site_redis_server) {
				$server_changed = true;
				$previous_server = $this->current_server;
				$this->redis_connection($site_redis_server);
				if (!$this->connected) $log = new Logger(__FUNCTION__ . ", #" . $site_redis_server, true);
				//echo "switch to ".$site_redis_server;
			}
			for ($i = $days; $i >= 0; $i--) {
				$time = time() - $i * (24 * 60 * 60);
				$today_stats_u_key = SCRIPT_PRE . ":" . $site_id . ":date_unique_stats:" . date("Y-m-d", $time);
				$today_stats_p_key = SCRIPT_PRE . ":" . $site_id . ":date_pageviews_stats:" . date("Y-m-d", $time);
				echo date("Y-m-d", $time) . " - u:" . $this->redis->get($today_stats_u_key) . ", p:" . $this->redis->get($today_stats_p_key) . "<br>";
			}
			if ($server_changed) $this->redis_connection($previous_server);
		}

		return $result;
	}
}
