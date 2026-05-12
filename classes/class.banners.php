<?php
class CBanners{
	var $_db;
	var $add_error = false;

	function __construct($db_connect) {		
		$this->_db = $db_connect;

	}

	protected function fetchAll($sql) {
		if ($this->_db instanceof PDO) {
			$stmt = $this->_db->query($sql);
			return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
		}

		$rs = $this->_db->Execute($sql);
		return $rs ? $rs->GetRows() : array();
	}

	protected function executeSql($sql) {
		if ($this->_db instanceof PDO) {
			return $this->_db->exec($sql) !== false;
		}

		return $this->_db->Execute($sql) !== false;
	}

	protected function quote($value) {
		if ($this->_db instanceof PDO) {
			return $this->_db->quote((string)$value);
		}

		return $this->_db->qstr($value);
	}

	protected function lastInsertId() {
		if ($this->_db instanceof PDO) {
			return (int)$this->_db->lastInsertId();
		}

		return (int)$this->_db->Insert_ID();
	}

	protected function bannerFileName($id, $width, $height, $type) {
		return (int)$id . '_' . (int)$width . 'x' . (int)$height . '.' . $type;
	}

	protected function bannerFolder($id) {
		return UPLOADFOLDER . '/banners/' . ceil((int)$id / 1000);
	}

	protected function bannerFilePath($id, $width, $height, $type) {
		return $this->bannerFolder($id) . '/' . $this->bannerFileName($id, $width, $height, $type);
	}

	protected function prepareBannerImage($url, $local = false) {
		$url = trim((string)$url);
		if ($url === '') {
			$this->add_error = 'empty image source';
			return false;
		}

		if ($local === false) {
			$temp_filename = TMPDIR . '/' . md5($url . microtime(true));

			$result = http_curl_request($url);
			if (!isset($result['request_content']) || empty($result['request_content'])) {
				$this->add_error = 'empty request';
				return false;
			}

			if ($result['http_code'] != 200) {
				$this->add_error = 'http code = ' . $result['http_code'];
				return false;
			}

			file_put_contents($temp_filename, $result['request_content']);
			$remove_temp = true;
		} else {
			$temp_filename = $url;
			$remove_temp = false;
		}

		$size = getimagesize($temp_filename);
		if ($size === false) {
			$this->add_error = 'bad file format';
			if ($remove_temp && is_file($temp_filename)) {
				unlink($temp_filename);
			}
			return false;
		}

		switch ($size['mime']) {
			case 'image/jpeg':
				$extension = 'jpg';
				break;
			case 'image/png':
				$extension = 'png';
				break;
			case 'image/gif':
				$extension = 'gif';
				break;
			default:
				$this->add_error = 'bad file format';
				if ($remove_temp && is_file($temp_filename)) {
					unlink($temp_filename);
				}
				return false;
		}

		return array(
			'temp_filename' => $temp_filename,
			'remove_temp' => $remove_temp,
			'width' => (int)$size[0],
			'height' => (int)$size[1],
			'ratio' => (int)ceil($size[0] / $size[1] * 100),
			'type' => $extension,
		);
	}

	protected function writeBannerImage($banner_id, array $image) {
		$banner_folder = $this->bannerFolder($banner_id);
		if (!is_dir($banner_folder) && !mkdir($banner_folder, 0777, true)) {
			$this->add_error = 'cannot create banner folder';
			return false;
		}

		$result_filename = $this->bannerFileName($banner_id, $image['width'], $image['height'], $image['type']);
		$result_path = $banner_folder . '/' . $result_filename;

		if ($image['remove_temp']) {
			$result = rename($image['temp_filename'], $result_path);
		} else {
			$result = copy($image['temp_filename'], $result_path);
		}

		if ($result) {
			chmod($result_path, 0777);
		} else {
			$this->add_error = 'cannot save banner file';
		}

		return $result;
	}

	function getBanner($id, $spot = false, $paysiteId = false) {
		$result = false;
		
		$spot = intval($spot);
		$banner = false;
		if ($id === false && $spot) {
			$paysiteInsert = "";
			if ($paysiteId !== false) {
				$paysiteId = intval($paysiteId);
				if ($paysiteId)	$paysiteInsert = ' and `paysite_id` = "'.$paysiteId.'"';
			}
			$res = $this->fetchAll('select id_banner from `banners_spots_content` where `id_spot` = "'.$spot.'" '.$paysiteInsert.' order by ratio asc');
			if(empty($res)) return false;

			$id = $res[0]['id_banner'];
		}
		$id = intval($id);
		$res = $this->fetchAll('select * from `banners` where `id_banner` = "'.$id.'"');
		if($res && is_array($res)) $result = $res[0];
		return $result;
	}

	function getBannerByRules($rules = array())
	{
		$sql = 'select id_banner from `banners`';
		
		$wh = false;
		if( isset($rules['paysite']) && $rules['paysite'] !== false)
		{
			 $sql .= ' where `paysite_id` = "'.$rules['paysite'].'" ';
			 $wh = true;
		}

		if( isset($rules['orientation']))
		{
			if($wh)
				$sql .= ' AND ';
			else
				$sql .= ' WHERE ';
			switch ($rules['orientation'])
			{
				case 'horizontal':
					$sql .= ' `width` > `height` ';
					break;
				case 'vertical':
					$sql .= ' `width` < `height` ';
					break;
				case 'square':
					$sql .= ' `width` = `height` ';
					break;
				default:
					$sql .= ' 1=1 ';
					break;
			}
			$wh = true;
		}
		if( isset($rules['ratio'])) {
			if($wh)
				$sql .= ' AND ';
			else
				$sql .= ' WHERE ';
			$sql .= ' `ratio` ' .$rules['ratio'];
			$wh = true;
		}

		$sql .= ' ORDER BY `id_banner` DESC';

		$res = $this->fetchAll($sql);
		$result = array();
		foreach ($res as $value)
		{
			$result[] = $value['id_banner'];
		}
		return $result;
	}

	function getAllBanners ($paysiteId = false) {
		return $this->getBannerByRules(array('paysite'=>$paysiteId));
	}

	function getHorizontal($paysiteId = false)
	{
		return $this->getBannerByRules(array('paysite'=>$paysiteId,'orientation'=>'horizontal'));
	}
	function getVertical($paysiteId = false)
	{
		return $this->getBannerByRules(array('paysite'=>$paysiteId,'orientation'=>'vertical'));
	}
	function getSquare($paysiteId = false)
	{
		return $this->getBannerByRules(array('paysite'=>$paysiteId,'orientation'=>'square'));
	}

	function getRect($paysiteId = false, $vert = false)
	{
		return $this->getBannerByRules(array('paysite'=>$paysiteId,'ratio'=>$vert?' < 80':' > 120'));
	}

	// возвращает id баннера, внутренние переменные устанавливаются как если бы был сделан switchBanner по айди
	function addBanner($url, $paysite_id, $text, $special_link, $local = false)
	{
		$image = $this->prepareBannerImage($url, $local);
		if ($image === false) {
			return false;
		}

		$sql = 'INSERT INTO `banners` (`paysite_id`, `width`, `height`, `ratio`, `type`, `text`, `special_link`) ';
		$sql .= ' VALUES (';
		$sql .= intval($paysite_id).',';
		$sql .= intval($image['width']).',';
		$sql .= intval($image['height']).',';
		$sql .= intval($image['ratio']).',';
		$sql .= $this->quote($image['type']).',';
		$sql .= $this->quote(clean_string($text)).',';
		$sql .= $this->quote($special_link);
		$sql .= ');';

		if (!$this->executeSql($sql)) {
			print 'error inserting banner<BR>';
			return false;
		}
		$insert_id = $this->lastInsertId();

		if (!$this->writeBannerImage($insert_id, $image)) {
			$this->executeSql('DELETE FROM `banners` WHERE `id_banner` = ' . (int)$insert_id);
			return false;
		}

		$this->selectSpots($insert_id);
		return  $insert_id;
	}

	function updateDbBanner( $id_banner, $paysite_id=false, $text=false, $special_link=false ) {
		$id_banner = intval($id_banner);
		if ($id_banner) {
			$update = false;
			$sql = 'UPDATE `banners` SET ';

			if ( $paysite_id !== false)
			{
				$sql .=  '`paysite_id`=' . intval($paysite_id);
				$update = true;
			}
			if ( $text !== false)
			{
				if ($update) $sql .=  ',';
				$sql .=  '`text`='.$this->quote(clean_string($text));
				$update = true;
			}
			if ( $special_link !== false)
			{
				if ($update) $sql .=  ',';
				$sql .=  '`special_link`='.$this->quote($special_link);
				$update = true;
			}

			$sql .= ' WHERE `banners`.`id_banner` = '.$id_banner;

			if ($update) {
				if ($this->executeSql($sql)) {
					$this->unselectSpots($id_banner);
					$this->selectSpots($id_banner);
					return true;
				}
			} else return false;
		} else return false;
			
	}

	function replaceBannerImage($id_banner, $url, $local = false) {
		$id_banner = (int)$id_banner;
		$current = $this->getBanner($id_banner);
		if (!$id_banner || !is_array($current)) {
			$this->add_error = 'banner not found';
			return false;
		}

		$image = $this->prepareBannerImage($url, $local);
		if ($image === false) {
			return false;
		}

		if (!$this->writeBannerImage($id_banner, $image)) {
			return false;
		}

		$sql = 'UPDATE `banners` SET ';
		$sql .= '`width`=' . intval($image['width']) . ',';
		$sql .= '`height`=' . intval($image['height']) . ',';
		$sql .= '`ratio`=' . intval($image['ratio']) . ',';
		$sql .= '`type`=' . $this->quote($image['type']);
		$sql .= ' WHERE `id_banner`=' . $id_banner;

		if (!$this->executeSql($sql)) {
			return false;
		}

		$old_file = $this->bannerFilePath($id_banner, $current['width'], $current['height'], $current['type']);
		$new_file = $this->bannerFilePath($id_banner, $image['width'], $image['height'], $image['type']);
		if ($old_file !== $new_file && is_file($old_file)) {
			unlink($old_file);
		}

		$this->unselectSpots($id_banner);
		$this->selectSpots($id_banner);

		return true;
	}


	function deleteBanner($banner_id) {
		$result = false;
		$banner_id = (int)$banner_id;
		$current = $this->getBanner($banner_id);
		$this->unselectSpots($banner_id);

		$sql = "DELETE FROM banners WHERE id_banner = '".$banner_id."'";

		if($this->executeSql($sql)) {
			if (is_array($current)) {
				$file = $this->bannerFilePath($banner_id, $current['width'], $current['height'], $current['type']);
				if (is_file($file)) {
					unlink($file);
				}
			}
			$result = true;
		}

		return $result;
	}


	function findBannersSize ($paysiteId, $maxWidth = false, $maxHeight = false, $minWidth = false, $minHeight = false) {
		$nel = false;
		if ($paysiteId !== false) $paysiteId = (int)$paysiteId;
		$sql = 'select id_banner from `banners`';

//Если указан платник, то должен соответстовать у баннера
		if ( $paysiteId )
		{
			if (!$nel) $sql .=  '  WHERE ';
			$sql .= '(`paysite_id`='.$paysiteId.')';
			$nel = true;
		}

		if ($maxWidth || $maxHeight || $minWidth || $minHeight) {
			if ($maxWidth) {
				if (!$nel) $sql .=  '  WHERE ';
				if($nel) $sql .= ' AND ';
				$sql .= '(`width` <= '.$maxWidth.')';
				$nel = true;
			}
			if ($maxHeight) {
				if (!$nel) $sql .=  '  WHERE ';
				if($nel) $sql .= ' AND ';
				$sql .= '(`height` <= '.$maxHeight.')';
				$nel = true;
			}
			if ($minWidth) {
				if (!$nel) $sql .=  '  WHERE ';
				if($nel) $sql .= ' AND ';
				$sql .= '(`width` >= '.$minWidth.')';
				$nel = true;
			}
			if ($minHeight) {
				if (!$nel) $sql .=  '  WHERE ';
				if($nel) $sql .= ' AND ';
				$sql .= '(`height` >= '.$minHeight.')';
				$nel = true;
			}
		}

		$sql .= ' ORDER BY `id_banner` ASC';

//		echo "<br>"."<br>"."<br>".$sql."<br>"."<br>"."<br>";

		$spot_res = $this->fetchAll($sql);

//pr($spot_res);
		if( empty($spot_res) )
			return false;
		$spot_ls = array();
		foreach ($spot_res as $value)
		{
			$spot_ls[] = $value['id_banner'];
		}
		return $spot_ls;

//		print_r($spot_res);

	}

	function updateText($id_banner,$update = false)			{$this->updateDbBanner($id_banner,false,$update,false);}
	function updateSpecialLink($id_banner,$update = false)	{$this->updateDbBanner($id_banner,false,false,$update);}
	function updatePaysite($id_banner,$update = false)		{$this->updateDbBanner($id_banner,$update,false,false);}

	function unselectSpots ($bannerId) {
		$result = false;
		$spotId = intval($bannerId);

		if ($this->_db && $bannerId) {
			$sql = "delete from `banners_spots_content` where `id_banner` = ".$bannerId;
			if ($this->executeSql($sql)) $result = true;
		}
		return $result;
	}

	function selectSpots($banner_id)
	{
		$banner = $this->getBanner($banner_id);
		if( $banner === false )
			return false;
		$paysiteId = (int)$banner['paysite_id'];

		$nel = false;
//Из таблицы спотов выбираются наиболее подхлдящие споты:
		$sql = 'select * from `banners_spots` WHERE ';

//Если указан платник, то должен соответстовать у баннера
		if ( $banner['paysite_id'] != 0 )
		{
			if ($nel) $sql .=  ',';
			$sql .= '(`paysite_id`='.$banner['paysite_id'].' OR `paysite_id`=0)';
			$nel = true;
		}
/*
//Если указана категория, то должна соответсововать у баннера, если указаны две категории то проверять через OR
		if ( $banner['category'] != 0  )
		{
			if ($nel) $sql .=  ' AND ';
			$sql .=  ' (`category_1`=\''.$banner['category_1'].'\' OR  `category_2`=\''.$banner['category_2'].'\') ';
			$nel = true;
		}
*/

//Если указана минимальная ширина и максимальная ширина, то размеры баннера должны соотвестсовавать
		if ( $banner['width'] != 0 )
		{
			if ($nel) $sql .=  ' AND ';

			$sql .=  '(`max_width`>='.$banner['width'].' OR `max_width`=0)';
			$sql .=  ' AND ';
			$sql .=  '(`min_width`<='.$banner['width'].' OR `min_width`=0)';

			$nel = true;
		}

//Если указана минимальная высота и максимальная высота, то размеры баннера должны соотвестсовавать
		if ( $banner['height'] != 0 )
		{
			if ($nel) $sql .=  ' AND ';

			$sql .=  '(`max_height`>='.$banner['height'].' OR `max_height`=0)';
			$sql .=  ' AND ';
			$sql .=  '(`min_height`<='.$banner['height'].' OR `min_height`=0)';

			$nel = true;
		}

//Выберается список удовлетворяющих баннеров.
//pr($sql);
		$spot_res = $this->fetchAll($sql);
//pr($spot_res);
		if( empty($spot_res) )
			return false;
		$spot_ls = array();
//		var_dump($spot_res);
		foreach ($spot_res as $value)
		{
			$spot_ls[] = $value['id'];
		}
//pr($spot_ls);

//Получается список из таблицы соответствий баннер-спор
		$spots_content_res = $this->fetchAll('select id_spot from `banners_spots_content` WHERE id_banner='.$banner_id);
//pr($spots_content_res);
		if( !empty($spots_content_res) )
		{
			foreach ($spots_content_res as $spots_content)
			{
				if( in_array( $spots_content['id_spot'], $spot_ls ) )
				{
					$bid = array_search( $spots_content['id_spot'], $spot_ls );
					unset( $spot_ls[$bid] );
				}
			}
		}

//pr($spot_ls);
//если баннера нет в таблице соответствий он добавляется
		if( !empty($spot_ls) )
		{
			foreach ($spot_ls as $spot_id)
			{
				$sql = 'INSERT INTO `banners_spots_content` ( `id_spot`, `id_banner`, `paysite_id`) ';
				$sql .= ' VALUES (';
				$sql .= intval($spot_id).',';
				$sql .= intval($banner_id).',';
				$sql .= intval($paysiteId);
				$sql .= ');';
				$this->executeSql($sql);
			}
		}
	}
}






class CCurentBanner extends CBanners{
	var $_db;
	//номер баннера, автоинкримент
	private $id;
	private $paysite_id;
	private $width;
	private $height;
	private $type; // тип баннера, gif, jpg или скриншот (большое изображение, из которого можно будет вырезать что поменьше
	private $text; //(a-zA-Z0-9\'\"\-)
	private $special_link;

	function __construct($db_connect)
	{
		$this->_db = $db_connect;
		parent::__construct($db_connect);
	}

	function addBanner($url, $paysite_id, $text, $special_link, $local = false)
	{
		$id_banner = parent::addBanner($url, $paysite_id, $text, $special_link, $local);
		$this->getBanner($id_banner);
		return $id_banner;
	}

	function getId(){return $this->id;}
	function getPaysiteId(){return $this->paysite_id;}
	function getWidth(){return $this->width;}
	function getHeight(){return $this->height;}
	function getType(){return $this->type;}
	function getText(){return $this->text;}
	function getSpecialLink(){
		$result = "";
		if ($this->special_link !== "") {
			$result = $this->special_link;
		} else {
			if($this->_db === false) {
				return $result;
			}
			$sql = 'select paysite_link from paysites where paysite_id = "'.$this->paysite_id.'"';
			$res = $this->fetchAll($sql);
			if(!empty($res)) {
				$result = $res[0]['paysite_link'];
			}
		}
		return $result;
	}

	// возвращает массив заполненый данными о модели с id, объект переинициализируется в соответствии с айди.
	function getBanner($id, $spot = false, $paysiteId = false) {
		$curent = parent::getBanner($id);

		if(!is_array($curent))
			return false;

		$this->id = $curent['id_banner'];
		$this->paysite_id = $curent['paysite_id'];
		$this->width = $curent['width'];
		$this->height = $curent['height'];
		$this->type = $curent['type'];
		$this->text = $curent['text'];
		$this->special_link = $curent['special_link'];

		return $curent;
	}

	function getSpotBanner($spot, $paysiteId = false)
	{
		$curent = parent::getBanner(false, $spot, $paysiteId);
		//pr($curent);
		if(!is_array($curent))
			return false;

		$this->id = $curent['id_banner'];
		$this->paysite_id = $curent['paysite_id'];
		$this->width = $curent['width'];
		$this->height = $curent['height'];
		$this->type = $curent['type'];
		$this->text = $curent['text'];
		$this->special_link = $curent['special_link'];

		return $curent;
	}

	// смена модели по айди, достает все данные по модели $id из базы, иначе false
	function switchBanner($id, $spot = false, $paysiteId = false, $debug = false) {
		$result = false;
		if ($debug) {
			echo "Debug<br>";
			$this->_db->debug = true;
		}
		if ($this->_db) {
			if ($debug) echo "DB connect ok<br>";
			if (!$id && $spot) {
				if ($debug) echo "getSpotBanner start<br>";
				$curent = $this->getSpotBanner($spot, $paysiteId);
				if ($debug) var_dump($curent);
				if(is_array($curent)) $result = true;
			} else {
				$curent = $this->getBanner($id);
				if(is_array($curent)) $result = true;
			}
		} else {
			if ($debug) echo "DB connect error<br>";
		}
		return $result;
	}

	function updateBanner( $paysite_id=false, $text=false, $special_link=false )
	{
		if($this->id === false)
			return false;
		$result = parent::updateDbBanner($this->id, $paysite_id, $text, $special_link );
		$this->getBanner($this->id);
		return $result;
	}

	function updatePaysite($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateBanner($update,false,false);
	}
	function updateText($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateBanner(false,$update,false);
	}
	function updateSpecialLink($update, $unused = false) {
		if ($unused !== false) $update = $unused;
		$this->updateBanner(false,false,$update);
	}

}
