<?php

class CBannerSpot{
	var $_db;

	function __construct($db_connect)
	{		
		$this->_db = $this->normalizeConnection($db_connect);
		//parent::__construct();
	}

	private function normalizeConnection($db_connect) {
		if ($db_connect instanceof PDO) {
			return $db_connect;
		}

		if (is_object($db_connect) && isset($db_connect->_db) && $db_connect->_db instanceof PDO) {
			return $db_connect->_db;
		}

		return null;
	}

	private function fetchAll($sql) {
		if (!$this->_db) {
			return array();
		}

		$stmt = $this->_db->query($sql);
		return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
	}

	private function executeSql($sql) {
		return $this->_db ? ($this->_db->exec($sql) !== false) : false;
	}

	private function quote($value) {
		return $this->_db ? $this->_db->quote((string)$value) : "''";
	}

	private function lastInsertId() {
		return $this->_db ? (int)$this->_db->lastInsertId() : 0;
	}

	function truncate()
	{
		$this->executeSql('TRUNCATE TABLE `banners_spots`');	
	} 

	function getSpots($id)
	{
		$result = false;

		if($this->_db !== false) {
			$res = $this->fetchAll('select * from `banners_spots` where `id` = "'.(int)$id.'"');

			if(!empty($res)) {
				$result = $res[0];
				$res = $this->fetchAll('select * from `banners_spots_content` where `id_spot` = "'.(int)$id.'"');
				if (empty($res)) {
					$result['banners_fit_spot'] = array();
				} else {
					foreach ($res as $banner) {
						$result['banners_fit_spot'][] = $banner ['id_banner'];
					}
				}
			}
		}
		return $result;
	}
	function getAllSpots()
	{
		if($this->_db === false) return false;
		$res = $this->fetchAll('select * from `banners_spots`');
		if(empty($res))
			return false;
		foreach ($res as $value)
		{
			$result[] = $value['id'];
		}
		return $result;
	}
	function addSpots(
						$name,
						$site_id,
						$paysite_id,
						$category_1 = 0,
						$category_2 = 0,
						$max_width = 0,
						$max_height = 0,
						$min_width = 0,
						$min_height = 0,
						$onsite_position = 'main',
						$onpage_position = 'top',
						$row = 0,
						$column = 0,
						$number = 0,
						$use_if_empty = ""
					)
	{
		if($this->_db === false) return false;
		// Нормализуем все данные
		if (!preg_match('/^(main|category|gallery|archive|other)$/', $onsite_position))
			$onsite_position = 'main';

		if (!preg_match('/^(top|bottom|lsidebar|rsidebar|middle)$/', $onpage_position))
			$onpage_position = 'top';

			$sql = 'INSERT INTO `banners_spots` ( `name`, `site_id`, `paysite_id`, `category_1`, `category_2`, `max_width`, `max_height`, `min_width`, `min_height`, `onsite_position`, `onpage_position`, `row`, `column`, `number`, `use_if_empty` ) ';
			$sql .= ' VALUES (';
			$sql .= $this->quote($name).',';
			$sql .= intval($site_id).',';
			$sql .= intval($paysite_id).',';
			$sql .= intval($category_1).',';
		$sql .= intval($category_2).',';
		$sql .= intval($max_width).',';
		$sql .= intval($max_height).',';
		$sql .= intval($min_width).',';
		$sql .= intval($min_height).',';
		$sql .= '\''.$onsite_position.'\',';
		$sql .= '\''.$onpage_position.'\',';
		$sql .= intval($row).',';
		$sql .= intval($column).',';
		$sql .= intval($number).',';
			$sql .= "'');";

			if (!$this->executeSql($sql)) {
				return false;
			}

			$insert_id = $this->lastInsertId();
			$this->updateHtmlField($use_if_empty, $insert_id);
			$this->selectBanners($insert_id);
			return  $insert_id;
	}

	function updateHtmlField($html_banner, $spot_id) {
		$result = false;
		if ($this->_db && $html_banner && (int)$spot_id > 0) {
			$html_banner = htmlspecialchars($html_banner, ENT_QUOTES);
			$sql = "UPDATE banners_spots SET use_if_empty = :html_banner WHERE id = :spot_id";
			$stmt = $this->_db->prepare($sql);
			if ($stmt) {
				$result = $stmt->execute(array(
					':html_banner' => $html_banner,
					':spot_id' => (int)$spot_id,
				));
			} else {
				$log = new Logger(__METHOD__.": PDO prepare failed", true);
			}
		}

		return $result;
	}

	function updateSpots( 
						$spots_id,
						$name = false,
						$site_id = false,
						$paysite_id = false,
						$category_1 = false,
						$category_2 = false,
						$max_width = false,
						$max_height = false,
						$min_width = false,
						$min_height = false,
						$onsite_position = false,
						$onpage_position = false,
						$row = false,
						$column = false,
						$number = false,
						$use_if_empty = false
					)
	{
		if($this->_db === false) return false;
		$update = false;
		$sql = 'UPDATE `banners_spots` SET ';

			if ( $name !== false)
			{
				$sql .=  '`name`='.$this->quote(clean_string($name));
				$update = true;
			}
		if ( $site_id !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`site_id`=\''.intval($site_id).'\'';
			$update = true;
		}
		if ( $paysite_id !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`paysite_id`=\''.intval($paysite_id).'\'';
			$update = true;
		}
		if ( $category_1 !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`category_1`=\''.intval($category_1).'\'';
			$update = true;
		}
		if ( $category_2 !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`category_2`=\''.intval($category_2).'\'';
			$update = true;
		}
		if ( $max_width !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`max_width`=\''.intval($max_width).'\'';
			$update = true;
		}
		if ( $max_height !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`max_height`=\''.intval($max_height).'\'';
			$update = true;
		}
		if ( $min_width !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`min_width`=\''.intval($min_width).'\'';
			$update = true;
		}
			if ( $min_height !== false)
			{
				if ($update) $sql .=  ',';
				$sql .=  '`min_height`=\''.intval($min_height).'\'';
				$update = true;
			}
		if ( $row !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`row`=\''.intval($row).'\'';
			$update = true;
		}
		if ( $column !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`column`=\''.intval($column).'\'';
			$update = true;
		}
		if ( $number !== false)
		{
			if ($update) $sql .=  ',';
			$sql .=  '`number`=\''.intval($number).'\'';
			$update = true;
		}
		if ( $onsite_position !== false)
		{
			if (!preg_match('/^(main|category|gallery|archive|other)$/', $onsite_position))
				$onsite_position = 'main';
			if ($update) $sql .=  ',';
			$sql .=  '`onsite_position`=\''.$onsite_position.'\'';
			$update = true;
		}
		if ( $onpage_position !== false)
		{
			if (!preg_match('/^(top|bottom|lsidebar|rsidebar|middle)$/', $onpage_position))
				$onpage_position = 'top';
			if ($update) $sql .=  ',';
			$sql .=  '`onpage_position`=\''.$onpage_position.'\'';
			$update = true;
		}

		if ( $use_if_empty !== false) {
			$this->updateHtmlField($use_if_empty, $spots_id);
		}		

			$sql .= ' WHERE `banners_spots`.`id` = '.$spots_id;

			if ($update) {
				if ($this->executeSql($sql)) {
					$this->unselectBanners($spots_id);
					$this->selectBanners($spots_id, $site_id);
					return true;
			}
		}
	}

	function updateName($spots_id,$update = false)				{$this->updateSpots($spots_id,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false);}
	function updateSite_id($spots_id,$update = false)			{$this->updateSpots($spots_id,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false);}
	function updatePaysite_id($spots_id,$update = false)		{$this->updateSpots($spots_id,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false);}
	function updateCategory_1($spots_id,$update = false)		{$this->updateSpots($spots_id,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false);}
	function updateCategory_2($spots_id,$update = false)		{$this->updateSpots($spots_id,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false);}
	function updateMaxWidth($spots_id,$update = false)			{$this->updateSpots($spots_id,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false);}
	function updateMaxHeight($spots_id,$update = false)			{$this->updateSpots($spots_id,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false);}
	function updateMinWidth($spots_id,$update = false)			{$this->updateSpots($spots_id,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false);}
	function updateMinHeight($spots_id,$update = false)			{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false);}
	function updateOnsitePosition($spots_id,$update = false)	{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false);}
	function updateOnpagePosition($spots_id,$update = false)	{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false);}
	function updateRow($spots_id,$update = false)				{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false);}
	function updateColumn($spots_id,$update = false)			{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false);}
	function updateNumber($spots_id,$update = false)			{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false);}
	function updateUseIfEmpty($spots_id,$update = false)		{$this->updateSpots($spots_id,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update);}

	function unselectBanners ($spotId) {
		$result = false;
		$spotId = intval($spotId);

			if ($this->_db && $spotId) {
				$sql = "delete from `banners_spots_content` where `id_spot` = ".$spotId;
				if ($this->executeSql($sql)) $result = true;
			}
		return $result;
	}

	function selectBanners($spots_id, $site_id = false)
	{
		$spot = $this->getSpots($spots_id);
		if( $spot === false )
			return false;

		$nel = false;
//Из таблицы баннеров выбираются баннеры:
		if (intval($site_id)) {
			$sql = 'select id_banner, paysite_id from `banners` where paysite_id in
					(select paysite_id from paysites where paysite_niche in 
						(select site_niche from sites where site_id = \''.intval($site_id).'\')
					) AND ';			
		} else {
					$sql = 'select id_banner, paysite_id from `banners` WHERE ';
		}


//Если указан платник, то должен соответстовать у баннера
		if ( $spot['paysite_id'] != 0 )
		{
			if ($nel) $sql .=  ',';
			$sql .=  '`paysite_id`=\''.$spot['paysite_id'].'\'';
			$nel = true;
		}
/*
//Если указана категория, то должна соответсововать у баннера, если указаны две категории то проверять через OR
		if ( $spot['category_1'] != 0 ||  $spot['category_2'] != 0  )
		{
			if ($nel) $sql .=  ' AND ';

			if ( $spot['category_1'] != 0 && $spot['category_2'] == 0)
				$sql .=  ' `category`=\''.$spot['category_1'].'\' ';
			if ( $spot['category_1'] == 0 && $spot['category_2'] != 0)
				$sql .=  ' `category`=\''.$spot['category_2'].'\' ';
			if ( $spot['category_1'] != 0 && $spot['category_2'] != 0)
				$sql .=  ' (`category`=\''.$spot['category_1'].'\' OR \''.$spot['category_2'].'\') ';
			$nel = true;
		}
*/
//Если указана минимальная ширина и максимальная ширина, то размеры баннера должны соотвестсовавать
		if ( $spot['max_width'] != 0 ||  $spot['min_width'] != 0  )
		{
			if ($nel) $sql .=  ' AND ';

			if ( $spot['max_width'] != 0 )
				$sql .=  ' `width`<=\''.$spot['max_width'].'\' ';

			if ( $spot['max_width'] != 0 && $spot['min_width'] != 0 )
				$sql .=  ' AND ';

			if ( $spot['min_width'] != 0 )
				$sql .=  ' `width`>=\''.$spot['min_width'].'\' ';
			$nel = true;
		}

//Если указана минимальная высота и максимальная высота, то размеры баннера должны соотвестсовавать
		if ( $spot['max_height'] != 0 ||  $spot['min_width'] != 0  )
		{
			if ($nel) $sql .=  ' AND ';

			if ( $spot['max_height'] != 0 )
				$sql .=  ' `height`<=\''.$spot['max_height'].'\' ';

			if ( $spot['max_height'] != 0 && $spot['min_height'] != 0 )
				$sql .=  ' AND ';

			if ( $spot['min_height'] != 0 )
				$sql .=  ' `height`>=\''.$spot['min_height'].'\' ';
			$nel = true;
		}

		$sql .= "ORDER BY width DESC";
		
		echo "<br>". $sql."<br>";



//Выберается список удовлетворяющих баннеров.
//var_dump($sql);
		// $this->_db->debug = true;
			$banner_res = $this->fetchAll($sql);
			// var_dump($banner_res);
			if( empty($banner_res) )
				return false;
		$banners_ls = array();
		foreach ($banner_res as $value)
		{
			$banners_ls[$value['id_banner']] = $value['paysite_id'];
		}
//var_dump($banners_ls);

//Получается список из таблицы соответствий баннер-спор
			$spots_content_res = $this->fetchAll('select id_banner from `banners_spots_content` WHERE id_spot='.$spots_id);
	//var_dump($spots_content_res);
			if( !empty($spots_content_res) )
			{
				foreach ($spots_content_res as $spots_content)
				{
					$bannerId = (int)$spots_content['id_banner'];
					if (array_key_exists($bannerId, $banners_ls))
					{
						unset($banners_ls[$bannerId]);
					}
				}
			}

//если баннера нет в таблице соответствий он добавляется
		if( !empty($banners_ls) )
		{
			foreach ($banners_ls as $bannerId => $paysiteId)
			{
				$sql = 'INSERT INTO `banners_spots_content` ( `id_spot`, `id_banner`, `paysite_id` ) ';
				$sql .= ' VALUES (';
				$sql .= intval($spots_id).',';
					$sql .= intval($bannerId).',';
					$sql .= intval($paysiteId);
					$sql .= ');';
					$this->executeSql($sql);
				}
			}
		}
}



























class CCurentBannerSpot extends CBannerSpot{
	var $_db;

	private $spots_id = false;
	private $name = false;
	private $site_id = false;
	private $paysite_id = false;
	private $category_1 = false;
	private $category_2 = false;
	private $max_width = false;
	private $max_height = false;
	private $min_width = false;
	private $min_height = false;
	private $onsite_position = false;
	private $onpage_position = false;
	private $row = false;
	private $column = false;
	private $number = false;

	function __construct($db_connect)
	{
		//$this->_db = $db_connect;
		parent::__construct($db_connect);
	}

	function getId(){return $this->spots_id;}
	function getName(){return $this->name;}
	function getSiteId(){return $this->site_id;}
	function getPaysiteId(){return $this->paysite_id;}
	function getCategory1(){return $this->category_1;}
	function getCategory2(){return $this->category_2;}
	function getMaxWidth(){return $this->max_width;}
	function getMaxHeight(){return $this->max_height;}
	function getMinWidth(){return $this->min_width;}
	function getMinHeight(){return $this->min_height;}
	function getOnsitePosition(){return $this->onsite_position;}
	function getOnpagePosition(){return $this->onpage_position;}
	function getRow(){return $this->row;}
	function getColumn(){return $this->column;}
	function getNumber(){return $this->number;}
	function getBannersFitSpot(){return $this->bannersFitSpot;}
	function getIfEmptyBanner(){return $this->use_if_empty;}

	function getSpots($id)
	{
		$curent = parent::getSpots($id);
		//pr($curent);
		if(!is_array($curent))
			return false;

		$this->spots_id = $curent['id'];
		$this->name = $curent['name'];
		$this->site_id = $curent['site_id'];
		$this->paysite_id = $curent['paysite_id'];
		$this->category_1 = $curent['category_1'];
		$this->category_2 = $curent['category_2'];
		$this->max_width = $curent['max_width'];
		$this->max_height = $curent['max_height'];
		$this->min_width = $curent['min_width'];
		$this->min_height = $curent['min_height'];
		$this->onsite_position = $curent['onsite_position'];
		$this->onpage_position = $curent['onpage_position'];
		$this->row = $curent['row'];
		$this->column = $curent['column'];
		$this->number = $curent['number'];
		$this->bannersFitSpot = $curent['banners_fit_spot'];
		$this->use_if_empty = $curent['use_if_empty'];

		return $curent;
	}

	function switchSpots($id)
	{
		$curent = $this->getSpots($id);
		if(!is_array($curent))
			return false;
		return true;
	}

	// изменяет все данные модели (текущей, айди берется из внутренних переменных) меняется только то что не false, само собой. 
	function updateSpots(
						$spots_id,
						$name = false,
						$site_id = false,
						$paysite_id = false,
						$category_1 = false,
						$category_2 = false,
						$max_width = false,
						$max_height = false,
						$min_width = false,
						$min_height = false,
						$onsite_position = false,
						$onpage_position = false,
						$row = false,
						$column = false,
						$number = false,
						$use_if_empty = false
					)
	{
		if ($spots_id === false) {
			$spots_id = $this->spots_id;
		}
		if($spots_id === false)
			return false;
		$result = parent::updateSpots($spots_id, $name, $site_id, $paysite_id, $category_1, $category_2, $max_width, $max_height, $min_width, $min_height, $onsite_position, $onpage_position, $row, $column, $number, $use_if_empty);
		$this->getSpots($spots_id);
		return $result;
	}

	function updateName($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false);
	}
	function updateSite_id($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false);
	}
	function updatePaysite_id($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false);
	}
	function updateCategory_1($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false);
	}
	function updateCategory_2($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false);
	}
	function updateMaxWidth($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false);
	}
	function updateMaxHeight($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false);
	}
	function updateMinWidth($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false);
	}
	function updateMinHeight($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false);
	}
	function updateOnsitePosition($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false);
	}
	function updateOnpagePosition($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false);
	}
	function updateRow($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false);
	}
	function updateColumn($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false);
	}
	function updateNumber($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateSpots($this->spots_id,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false);
	}

}
