<?php
/*
private:



public:
	addModelToSitesByGallery
	- добавление модели во все сайты при известных model_id  и gal_id
*/


// При изменении класса и таблицы вносить изменения в getModelInfo (class.sites.php)

class CModels{
	var $_db;

	// номер модели
	private $id = false;
	// имя, допускаемые символы a-zA-Z,"-","'"
	private $name;
	//  указание активна модель или нет
	private $active;
	// пол
	private $sex;
	//  ориентация относительно партнера, у геев все понятно, у женщин и стрейт моделей это обозначение доминирования в бдсм играх
	private $role;
	// цвет волос
	private $hair;
	// дата рождения
	private $birth;
	// тип тела, толстая, худая, мускулистая, и т.д.
	private $body;
	// айди персонального платника модели (он берется из другой таблицы, можно сделать просто пустышку пока)
	private $personal_site_id;
	// рост (в см)
	private $height;
	// id картинки (они храняться в отдельной таблице)
	private $picture;
	// размер сисек или члена, в зависимости от пола
	private $size;
	// дополнительное поле, просто оставил, мало ли там что хранить можно.. может данные о том любит собачек или дилдо, или хз)
	private $info;
	private $classic;
	private $main_image_url;

	private $main_image;
	private $main_horiz_image;
	private $category_of_age = -1;
	private $zodiac = false;

	private $zodiac_signs = array('aries', 'taurus', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'saggitarius', 'capricorn', 'aquarius', 'pisces');

	private $search_filter_site_id = false;
	private $search_filter_eyes_color = false;
	private $search_filter_body_type = false;
	private $search_filter_hair_color = false;
	private $search_filter_ethnic = false;
	private $search_filter_first_letter = false;

	function __construct(PDO $db_connect = null) {		
		$this->_db = ($db_connect) ? $db_connect :new db_access(); // заглушка для вызовов Galleries без параметров
		$this->boobs = false;
		$this->cock = false;
		$this->pseudonims = array();
		//parent::__construct();
	}

	function getId(){return $this->id;}
	function getName(){return $this->name;}
	function getActive(){return $this->active;}
	function getSex(){return $this->sex;}
	function getRole(){return $this->role;}
	function getHair(){return $this->hair;}
	function getEyes(){return $this->eyes;}
	function getCock(){return $this->cock_boobs;}
	function getBoobs(){return $this->cock_boobs;}
	function getEthnic(){return $this->ethnic;}
	function getPiercing(){return $this->piercing;}
	function getPseudonims(){return $this->pseudonims;}
	function getTattoo(){return $this->tattoo;}
	function getTattooDesc(){return $this->tattooDesc;}
	function getCountry(){return $this->country;}
	function getBirth(){return $this->birth;}
	function getBody(){return $this->body;}
	function getPersonalSite(){return $this->personal_site_id;}
	function getHeight(){return $this->height;}
	function getPicture(){return $this->picture;}
	function getSize(){return $this->size;}
	function getInfo(){return $this->info;}
	function getIfClassic(){return $this->classic;}
	function getTwitter(){return $this->twitter;}
	function getFacebook(){return $this->facebook;}
	function getAllImages(){return $this->all_images;}
	function getMainImageUrl(){return $this->main_image_url;}
	function getCategoryOfAge(){return $this->category_of_age;}
	function getZodiac(){return $this->zodiac;}
	

	function getMainVerticImageUrl($thumb_size = 'medium'){

		$result = array('url' => "images/no-image.png", 'model_image_id' => 0);

		if($this->main_image && $this->main_image != 0) {

			if(!preg_match("#^(big|medium|small)$#", $thumb_size)) $thumb_size = 'medium';
			if ($thumb_size == 'small') $thumb_pre = "/150x200/";
			elseif ($thumb_size == 'medium') $thumb_pre = "/180x240/";
			elseif ($thumb_size == 'big') $thumb_pre = "/240x320/";	

			$result['url'] = HOSTING . "/models".$thumb_pre. folderNameById($this->main_image)."/".$this->main_image.".jpg";
			$result['model_image_id'] = $this->main_image;

		} elseif ($this->picture) {
			$thumbId = $this->picture;
			$folder =folderNameById($this->picture);
			if ($thumb_size == 'small') $thumb_pre = "/thumbs/p/150";
			elseif ($thumb_size == 'medium') $thumb_pre = "/thumbs/p/180";
			elseif ($thumb_size == 'big') $thumb_pre = "/thumbs/p/240";

        	$result['url'] = HOSTING . $thumb_pre ."/". $folder ."/".$thumbId.".jpg";
        	$result['model_image_id'] = false;
		}

		return $result;
	}

	function getMainHorizImageUrl($thumb_size = 'medium'){

		$result = array('url' => "images/no-image.png", 'model_image_id' => 0);

		if($this->main_horiz_image) {
			if(!preg_match("#^(big|medium|small)$#", $thumb_size)) $thumb_size = 'medium';
			if ($thumb_size == 'small') $thumb_pre = "/200x150/";
			elseif ($thumb_size == 'medium') $thumb_pre = "/240x180/";
			elseif ($thumb_size == 'big') $thumb_pre = "/320x240/";	
			$result['url'] = HOSTING . "/models".$thumb_pre. folderNameById($this->main_horiz_image)."/".$this->main_horiz_image.".jpg";
			$result['model_image_id'] = $this->main_horiz_image;
		}
		return $result;
	}

	function getImage($model_id) {
		$result = false;
		$all_images = $this->allImages($model_id);
		if($all_images) {
			$result = array_shift(array_slice($all_images['big'], 0, 1));
		}
		return $result;
	}
	
	

	// возвращает массив заполненый данными о модели с id, объект переинициализируется в соответствии с айди.
	function getModel(int $id, $thumb_size = 'big') {
		$model = false;

		if(!preg_match("#^(big|medium|small)$#", $thumb_size)) {
			$thumb_size = 'big';
		}

		$db = DB::get();

		$sql = "SELECT  id_model, name, active, sex, role, category_of_age, hair, birth, zodiac, eyes, ethnic,
						body_type, cock_n_boobs_type, piercing, piercing_where, tattoo, tattoo_description,
						country, body, personal_site_id, height, size, info, picture, classic,
						twitter, facebook, added_on, main_image, main_horiz_image
				FROM model
				WHERE id_model = ?";

		try {

			$id_model = false;
			$name = false;
			$active = false;
			$sex = false;
			$role = false;
			$category_of_age = false;
			$hair = false;
			$birth = false;
			$zodiac = false;
			$eyes = false;
			$ethnic = false;
			$body_type = false;
			$cock_n_boobs_type = false;
			$piercing = false;
			$piercing_where = false;
			$tattoo = false;
			$tattoo_description = false;
			$country = false;
			$body = false;
			$personal_site_id = false;
			$height = false;
			$size = false;
			$info = false;
			$picture = false;
			$classic = false;
			$twitter = false;
			$facebook = false;
			$added_on = false;
			$main_image = false;
			$main_horiz_image = false;

			$stmt = $db->prepare($sql);
			$stmt->bind_param("i", $id);
			$stmt->execute();

			$stmt->bind_result( $id_model, $name, $active, $sex, $role, $category_of_age, $hair, $birth, $zodiac, $eyes, $ethnic,
								$body_type, $cock_n_boobs_type, $piercing, $piercing_where, $tattoo, $tattoo_description,
								$country, $body, $personal_site_id, $height, $size, $info, $picture, $classic,
								$twitter, $facebook, $added_on, $main_image, $main_horiz_image );

			if ($stmt->fetch()) {

				$this->id = $id_model;
				$this->name = $name;
				$this->active = $active;
				$this->sex = $sex;
				$this->role = $role;
				$this->hair = $hair;
				$this->birth = $birth;
				$this->body = $body_type;
				$this->personal_site_id = $personal_site_id;
				$this->height = $height;
				$this->picture = $picture;
				$this->size = $size;
				$this->info = $info;
				$this->eyes = $eyes;
				$this->ethnic = $ethnic;
				$this->cock_boobs = $cock_n_boobs_type;
				$this->piercing = $piercing;
				$this->tattoo = $tattoo;
				$this->tattooDesc = $tattoo_description;
				$this->country = $country;
				$this->classic = $classic;
				$this->twitter = $twitter;
				$this->facebook = $facebook;
				$this->main_image = $main_image;
				$this->main_horiz_image = $main_horiz_image;
				$this->category_of_age =  $category_of_age;
				$this->zodiac =  $zodiac;
			}

			$stmt->close();

		} catch(Exception $e) {
			$log = new Logger(__METHOD__.": STMT failed: ".$e->getMessage(), true); 
			return false;
		}

		if ($this->id) {
			$this->all_images = $this->allImages($this->id);
			$this->pseudonims = $this->listPseudonims();

			if ($this->main_image) {

				$this->main_image_url = $this->getMainVerticImageUrl($thumb_size);

			} elseif ($this->picture) {

				$thumbId = $this->picture;
				$folder = folderNameById($this->picture);

				if ($thumb_size == 'small') $thumb_pre = "/thumbs/p/150";
				elseif ($thumb_size == 'medium') $thumb_pre = "/thumbs/p/180";
				elseif ($thumb_size == 'big') $thumb_pre = "/thumbs/p/240";

				$this->main_image_url = $thumb_pre . "/" . $folder . "/" . $thumbId . ".jpg";

			} elseif (!$this->main_image && !$this->main_image && isset($this->all_images[$thumb_size])) {

				$this->main_image_url = array_shift(array_slice($this->all_images[$thumb_size], 0, 1));

			} else {

				$this->main_image_url = $this->getMainVerticImageUrl('big');

			}

			$model = compact("id_model", "name", "active", "sex", "role", "category_of_age", "hair", "birth", "zodiac", "eyes", "ethnic", "body_type", "cock_n_boobs_type", "piercing", "piercing_where", "tattoo", "tattoo_description", "country", "body", "personal_site_id", "height", "size", "info", "picture", "classic", "twitter", "facebook", "added_on", "main_image", "main_horiz_image");
			$model['pseudonims'] = serialize($this->pseudonims);
			$model['main_image_url'] = $this->main_image_url;
			$model['id'] = $this->id;
		}
				
		return $model;
	}


	public function allImages(int $id, $layout = false) {
		$result = false;
		
		if ($id > 0) {

			
			$sql = "SELECT image_id, layout 
					FROM models_images 
					WHERE model_id = '".$id."' 
					AND (status = 'cropped' OR status = 'uploaded')";
			if ($layout && preg_match("#^(horiz|vertic)$#", $layout)) {
				$sql .= " AND layout = '".$layout."'";
			}

			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if($stmt) {

							if($stmt->execute()) {

								$image_id =false;
								$layout = false;

								$stmt->bind_result($image_id, $layout);

								while($stmt->fetch()) { 
									if ($layout == 'vertic') {
										$result['small'][$image_id] = "/models/150x200/". folderNameById($image_id)."/".$image_id.".jpg";
										$result['medium'][$image_id] = "/models/180x240/". folderNameById($image_id)."/".$image_id.".jpg";
										$result['big'][$image_id] = "/models/240x320/". folderNameById($image_id)."/".$image_id.".jpg";
									} else {
										$result['small'][$image_id] = "/models/200x150/". folderNameById($image_id)."/".$image_id.".jpg";
										$result['medium'][$image_id] = "/models/240x180/". folderNameById($image_id)."/".$image_id.".jpg";
										$result['big'][$image_id] = "/models/320x240/".folderNameById($image_id)."/".$image_id.".jpg";
									}
								}

							} else { $log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error,true); }
						$stmt->close();				
					} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }
				} else { $log = new Logger(__METHOD__.": SQL string is empty", true); }
			} else { $log = new Logger(__METHOD__.": No DB connect", true); }
		}
		return $result;
	}


	public function modelsCount() {
		$rs = $this->_db->query('select count(id_model) from `model`');
		$model = $rs->fetchAll(\PDO::FETCH_ASSOC);
		if(empty($model))
			return false;

		$result = $model[0]['count(id_model)'];
		return $result;
	}

	private function listPseudonims() {
		$this->pseudonims = array();
		$result = array();
		if($this->id === false)
			return $result;

		$db = DB::get();

		if ($db) {
				$sql = 'SELECT id, model_id, name 
						FROM `model_names` 
						WHERE `model_id` = ?';
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("i", $this->id)) {
						if($stmt->execute()) {

							$id = false;
							$model_id = false;
							$name = false;

							$stmt->bind_result( $id, $model_id, $name);
							while($stmt->fetch()) {
								$result[$id] = $name;

							}
							$this->pseudonims = $result;
						} else { 
							$log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error,true); 
						}
					} else { 
						$log = new Logger(__METHOD__.": STMT Bind Param failed: ".$db->error,true); 
					}					
					$stmt->close();
				} else { 
					$log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); 
				}
		} else { 
			$log = new Logger(__METHOD__.": No DB connect", true); 
		}

		return $result;
	}

	function removePseudonim ($id) {
		$id = intval($id);
		if($id == 0)
			return false;

		$sql = "DELETE FROM `model_names`
				WHERE id = '".$id."';";
		if ( $this->_db->query($sql) === false) {
		        return false;
		} else {
			return true;
		}
	}	

	function insertPseudonim ($id, $name) {
		$result = false;
		$this->switchModel($id);

		$name =  ($name = preg_replace('/[^a-zA-Z0-9 \'\.-]/im', "", $name)) ? trim($name) : "";

		if($this->id === false || $name == '' || !preg_match("#^[a-z0-9 '\.-]{1,62}$#im", $name))
			return $result;

		$name = trim($name);
		$name = htmlspecialchars($name);
		
		$sql = 'INSERT INTO `model_names` (`model_id`, `name`) VALUES (:model_id, :model_name);';

		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array(':model_id' => $id, ':model_name' => $name));
		} catch(PDOException $e) {
			print 'error inserting: '.$e->getMessage().'<BR>';
			$log = new Logger (__METHOD__." :: Ошибка добавления в базу данных: ".$e->getMessage(), true);
			return false;
		}
		
		$insert_id = $this->_db->lastInsertId();
		$result =  $insert_id;
		
		return $result;

	}

	function updatePseudonim (int $id, $name) {
		$name =  ($name = preg_replace('/^([^a-zA-Z \'])$/im', "", $name)) ? trim($name) : "";
		$name = htmlspecialchars($name);

		if($name == '' || !preg_match("#^[a-z ']{1,62}$#im", $name)) return false;

		$sql = 'UPDATE `model_names` SET `name` = :model_name WHERE id = :model_id';

		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array(':model_id' => $id, ':model_name' => $name));
		} catch(PDOException $e) {
			print 'error inserting: '.$e->getMessage().'<BR>';
			$log = new Logger (__METHOD__." :: Ошибка добавления в базу данных: ".$e->getMessage(), true);
			return false;
		}

		return true;
	}


	private function addNewPsewdonims ($pseudonimsArray) {
		$result = false;
		if($this->id === false)
			return $result;
		if ($pseudonimsArray && is_array($pseudonimsArray) && count($pseudonimsArray) > 0) {
			foreach ($pseudonimsArray as $pseudonim) {
				if (!in_array($pseudonim, $this->pseudonims)) {
					if ($this->insertPseudonim ($this->id, $pseudonim)) $result = true;
				}

			}
		}
		return $result;

	}

	// смена модели по айди, достает все данные по модели $id из базы, иначе false
	function switchModel($id)
	{
		$model = $this->getModel($id);
		if(!is_array($model)) return false;

		return true;
	}


	// возвращает id модели, внутренние переменные устанавливаются как если бы был сделан switchModel по айди
	function addModel($name, $sex, $hair = 'brown', $body = 'none', $active = 'yes', $birth = '1000-01-01', $height = 0, $size=0, $role='versatile', $info='', $personal_site_id = 0, $picture=0, $eyes = 'none', $cock_boobs = 'none', $ethnic = 'none', $piercing = 'none', $tattoo = 'none', $tattooDesc = "", $country = '', $newPseudonims = array(), $classic = 0, $twitter = "", $facebook = "", $category_of_age = -1, $zodiac =false)
	{
		if (!preg_match('/^(none|skinny|thin|slim|athletic|muscular|bodybuilder|chubby|fat)$/', $body))
			$body = 'none';

		if (!preg_match('/^(natural|mod)$/', $cock_boobs))
			$cock_boobs = 'none';

		if (!preg_match('/^(amber|blue|brown|gray|green|hazel)$/', $eyes))
			$eyes = 'none';		

		if (!preg_match('/^(arab|american|euro|ebony|asian|latin|indian)$/', $ethnic))
			$ethnic = 'none';	

			// var_dump(date_parse($birth));

		$birth = date_parse($birth);

		$year = $birth['year'] ? $birth['year'] : '1000';
		$month = $birth['month'] ? $birth['month'] : '01';
		$day = $birth['day'] ? $birth['day'] :'01';

		$birth = $year."-". $month ."-". $day;


		if ($country = preg_replace('/^([^a-zA-Z ])$/', "", $country)) {
			$country = trim(strtolower($country));
		} else $country = '';

		if ($tattooDesc = preg_replace('/^([^a-zA-Z ])$/', "", $tattooDesc)) {
			$tattooDesc = trim($tattooDesc);
		} else $tattooDesc = '';	

		if ($twitter = preg_replace('/^([^-0-9a-zA-Z ])$/', "", $twitter)) {
			$twitter = trim($twitter);
		} else $twitter = '';	
		if ($facebook = preg_replace('/^([^-0-9a-zA-Z ])$/', "", $facebook)) {
			$facebook = trim($facebook);
		} else $facebook = '';	

		if (!preg_match('/^(yes|no)$/', $tattoo))
			$tattoo = 'none';

		// Нормализуем все данные
		if (!preg_match('/^(yes|no)$/', $active))
			$active = 'yes';

		if (!preg_match('/^(yes|no)$/', $piercing))
			$piercing = 'none';

		if (!preg_match('/^(female|shemale|male)$/', $sex))
			$sex = 'female';

		if (!preg_match('/^(top|bottom|versatile)$/', $role))
			$role = 'versatile';

		if (!preg_match('/^(blond|brunette|red|gray|white|bald|brown)$/', $hair)) {
			$hair = 'brown';
		}

		if(!($category_of_age == -1 || $category_of_age == 20 || $category_of_age == 25 || $category_of_age == 35
		|| $category_of_age == 45 || $category_of_age == 55 || $category_of_age == 65 || $category_of_age == 90)) {
			$category_of_age = -1;
		}

		$classic = intval($classic);
		if ($classic != 0) $classic = 1;

		if(!$zodiac || !in_array($zodiac, $this->zodiac_signs)) {
			$zodiac = 'none';
		}

		$sql = 'INSERT INTO `model` (`name`, `active`, `sex`, `role`, `hair`, `birth`, `body_type`, `personal_site_id`, `height`, `size`, `info`, `picture`, `cock_n_boobs_type`, `country`, `eyes`, `ethnic`, `tattoo`, `tattoo_description`, `piercing`, `classic`, `twitter`, `facebook`, `added_on`, `main_image`, `main_horiz_image`, `category_of_age`, `zodiac`, `body`, `googleplus`) ';
		$sql .= ' VALUES (:model_name,';
		$sql .= '\''.$active.'\',';
		$sql .= '\''.$sex.'\',';
		$sql .= '\''.$role.'\',';
		$sql .= '\''.$hair.'\',';
		$sql .= '\''.$birth.'\',';
		$sql .= '\''.$body.'\',';
		$sql .= intval($personal_site_id).',';
		$sql .= intval($height).',';
		$sql .= intval($size).',';
		$sql .= ':model_info, ';
		$sql .= intval($picture) . ',';
		$sql .= '\''.$cock_boobs.'\',';
		$sql .= '\''.$country.'\',';
		$sql .= '\''.$eyes.'\',';
		$sql .= '\''.$ethnic.'\',';
		$sql .= '\''.$tattoo.'\',';
		$sql .= '\''.$tattooDesc.'\',';
		$sql .= '\''.$piercing.'\',';
		$sql .= '\''.$classic.'\',';
		$sql .= '\''.$twitter.'\',';
		$sql .= '\''.$facebook.'\',';
		$sql .= '\''.time().'\',';
		$sql .= '\'0\',';
		$sql .= '\'0\',';
		$sql .= '\''.$category_of_age.'\',';
		$sql .= '\''.$zodiac.'\',';
		$sql .= '\'\',';
		$sql .= '\'\'';
		$sql .= ');';


		// var_dump($sql);

		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(
						array(
							'model_name' => $name, 
							'model_info' => $info
						)
					);

		} catch(PDOException $e) {
			echo __METHOD__.' :: Ошибка добавления в базу данных: '.$e->getMessage().'<BR>';
			$log = new Logger (__METHOD__." :: Ошибка добавления в базу данных: ".$e->getMessage(), true);
			return false;
		}

		$insert_id = $this->_db->lastInsertId();
		if ($this->getModel($insert_id)) {
			$this->addNewPsewdonims($newPseudonims);
		}
		
		return  $insert_id;
	}

	// добавляется галлерея $id (init в таблицу из getModelGals)
	function deleteModel() {
		if($this->id === false)
			return;
		$galleries = $this->getModelGals();
		//var_dump($galleries);

		$model['model_id'] = $this->id;
		$model['name'] = $this->name;
		$model['active'] = $this->active;
		$model['sex'] = $this->sex;
		$model['role'] = $this->role;
		$model['hair'] = $this->hair;
		$model['birth'] = $this->birth;
		$model['body'] = $this->body;
		$model['personal_site_id'] = $this->personal_site_id;
		$model['height'] = $this->height;
		$model['picture'] = $this->picture;
		$model['size'] = $this->size;
		$model['info'] = $this->info;

		$model['eyes'] = $this->eyes;
		$model['ethnic'] = $this->ethnic;
		$model['cock_n_boobs_type'] = $this->cock_boobs;
		$model['piercing'] = $this->piercing;
		$model['tattoo'] = $this->tattoo;
		$model['tattoo_description'] = $this->tattooDesc;
		$model['country'] = $this->country;
		$model['classic'] = $this->classic;
		$model['twitter'] = $this->twitter;
		$model['facebook'] = $this->facebook;
		$model['category_of_age'] = $this->category_of_age;
		$model['zodiac'] = $this->zodiac;
		

		$modelInfo ['model'] = $model;
		$modelInfo ['galleries'] = $galleries;
		$modelInfo = serialize($modelInfo);

		 if ($rs = $this->_db->query('insert into model_trash (model_id, model_data) values ('.$this->id.', \''.$modelInfo.'\')')) {
		 	if (is_array($galleries) && count($galleries) > 0) {
			 	foreach ($galleries as $gal_id) {
			 		$this->removeFromGallery($gal_id);
			 	}
			} else $galleries = array();
	 		if ($rs = $this->_db->query('delete from model where id_model like "'.$this->id.'"'))	return $galleries;
		 	else return false;
		 } else return false;
	

	}


	// добавляется галлерея $id (init в таблицу из getModelGals)
	// !!!WARNING
	// функция продублирована в классе Galleries, 31.10.2015
	// из класса моделей ее стоит удалить, т.к. она больше подходит к галерам, а не к моделям
	function addGallery($id)
	{
		//$this->_db->debug=true;
		if($this->id === false || (int)$id == 0)
			return false;

		$gals = $this->getModelGals();

		if (in_array($id, $gals)) return false;

		$sql = 'INSERT INTO `galleries_models` (`model_id`, `gallery_id`) ';
		$sql .= ' VALUES (';
		$sql .= intval($this->id).',';
		$sql .= intval($id);
		$sql .= ');';

		if ( $this->_db->query($sql) === false) {
		         print 'error inserting: '.$this->_db->errorInfo().'<BR>';
		} else {
			$this->switchModel($this->id);
			return true;
		}
	}

	function removeFromGallery ($id) {

//		$this->_db->debug = true;

		if($this->id === false || (int)$id == 0)
			return false;

		$sql = "DELETE FROM `galleries_models`
				WHERE model_id = ".intval($this->id)."
				AND gallery_id = ".intval($id);

		if ( $this->_db->query($sql) === false) {
		         print 'error inserting: '.$this->_db->errorInfo().'<BR>';
		} else {
			$this->removeFromGallery_sites($id,$this->id);
			$this->switchModel($this->id);
			return true;
		}
		return $sql;
	}

	function removeFromSite ($id) {

//		$this->_db->debug = true;

		if($this->id === false || (int)$id == 0)
			return false;

		$sql = "DELETE FROM `sites_models`
				WHERE model_id = ".intval($this->id)."
				AND site_id = ".intval($id);

		if ( $this->_db->query($sql) === false) {
		         print 'error inserting: '.$this->_db->errorInfo().'<BR>';
		} else {
			$this->switchModel($this->id);
			return true;
		}
		return $sql;
	}	

	public function addModelToSitesByGallery($model_id, $gal_id) {
		$result = false;
		$sites_w = new Sites($this->_db);
		$sites_list = $sites_w->galleryPostedTo($gal_id);

		if($sites_list) {
			$gallery_w = new Galleries($this->_db);
			$gal_type = $gallery_w->getGalleryType($gal_id);

			foreach($sites_list as $site_id) {
				$local_id = $sites_w->getLocalId($site_id, $gal_id);
				if($local_id) {
					$this->addOneModelGalleryToSite($site_id, $model_id, $local_id, $gal_type);
				}
			}
		}
		return $result;
		
	}

	private function removeFromGallery_sites($gal_id, $model_id) {
		$result = false;

		$gal_id = intval($gal_id);
		$model_id = intval($model_id);

		$sql = "SELECT site_id FROM sites";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$sites = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($sites){
				foreach ($sites as $site) {
					$sql_x = "SELECT site_".$site['site_id'].".id, galleries.gal_type
							  FROM site_".$site['site_id']." 
							  LEFT JOIN galleries ON  site_".$site['site_id'].".gal_id = galleries.gal_id
							  WHERE site_".$site['site_id'].".gal_id = '".$gal_id."'";
					$rs = $this->_db->query($sql_x);
					if ($rs) {
						$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
						if ($gallery) {

							$local_gal_id = $gallery[0]['id'];
							$gal_type = $gallery[0]['gal_type'];
							$affected_rows = false;
							if ($local_gal_id) {
								if($gal_type == 'Pics' || $gal_type == "gif") {
									$result = $this->_db->query('delete from  site_'.$site['site_id'].'_models_pics where local_id = "'.$local_gal_id.'" and id_model = "'.$model_id.'"',$affected_rows);
								} elseif($gal_type == 'Movies' || $gal_type == "embed") {
									$result = $this->_db->query('delete from  site_'.$site['site_id'].'_models_movies where local_id = "'.$local_gal_id.'" and id_model = "'.$model_id.'"',$affected_rows);
								} else {
									$log = new Logger(__METHOD__.": удаление галеры модели из таблицы невозможно, из-за неверного типа галеры: '".$gal_type."'", true);
								}
								if($result) {
									// изменить на mysqli и вставить в удаление affected_rows чтобы быть уверенным что есть что удалять!
									echo "минус одна галера модели";
									var_dump($affected_rows);
									$this->minusOneModelGallery($site['site_id'], $model_id, $gal_type);
								}

								
							}
						}
					}
					
				}
			}
		}
		return $result;
	}

	// проверка если total_count  в таблице sites_models для моделе == 0, чтобы ее удалить
	function checkSitesModelGalCountCell($site_id, $model_id) {
		$result = false;

		$site_id = (int)$site_id;
		$model_id = (int)$model_id;
		if(!$site_id || !$model_id) {
			$log = new Logger(__METHOD__.": неверные входящие данные", true);
			return $result;
		}
			

		$rs = $this->_db->query('select total_count from `sites_models` where `site_id` = "'.$site_id.'" AND `model_id` = "'.$model_id.'"');
		if($rs) {
			$counter_x = $rs->fetchAll(\PDO::FETCH_ASSOC);
			$result = $counter_x[0]['total_count'];
		} else {
			$log = new Logger (__METHOD__.": проблема с выполнением SQL запроса", true);
			echo "Fatal error in SQL checkSitesModelGalCountCell";
			exit;
		}
		

		return $result;
	}


	function minusOneModelGallery($site_id, $model_id, $gal_type) {
		$result = false;
		$site_id = (int)$site_id;
		$model_id = (int)$model_id;
		if(!preg_match("#^(Pics|Movies)$#", $gal_type)) $gal_type = false;
		if($site_id && $model_id) {
			if($gal_type) {
				$db = DB::get();
				if ($db) {

					$sql = "UPDATE sites_models
							SET updated_on = ?";

					if($gal_type == 'Pics') $sql .= ", gals_count = (gals_count - 1) ";
					elseif($gal_type == 'Movies') $sql .= ", video_count = (video_count - 1) ";
					$sql .= ", total_count = (gals_count + video_count)
							WHERE model_id = ? AND site_id = ?";
					
					$updated_on = time();

					$stmt = $db->prepare($sql);
					$stmt->bind_param("iii", $updated_on, $model_id, $site_id);

					if($stmt) {
						if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
						else {
							$result = true;
							$gals_count_left = $this->checkSitesModelGalCountCell($site_id, $model_id);
							var_dump($gals_count_left);
							if($gals_count_left !== false && ($gals_count_left == 0 || $gals_count_left < 0)) {  
								$this->removeModelFromSitesModelsTable($site_id, $model_id);
							}
						}
						$stmt->close();
					} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	

				} else {
					$log = new Logger(__METHOD__.": No SQL connect. ",true);
				}
			} else {
				$log = new Logger(__METHOD__.": Тип галеры указан неверно. ",true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие данные. ",true);
		}
		return $result;
	}

	function removeModelFromSitesModelsTable ($site_id, $model_id) {
		$result = false;
		$site_id = (int)$site_id;
		$model_id = (int)$model_id;
		if(!$site_id || !$model_id) {
			$log = new Logger(__METHOD__.": неверные входящие данные", true);
			return $result;
		}

		$sql = "DELETE FROM `sites_models`
				WHERE site_id = '".$site_id."' AND model_id = '".$model_id."';";
		if ( $this->_db->query($sql) === false) {
		        return false;
		} else {
			return true;
		}
	}


	function plusOneModelGallery($site_id, $model_id, $gal_type) {
		$result = false;
		$site_id = (int)$site_id;
		$model_id = (int)$model_id;
		if(!preg_match("#^(Pics|Movies)$#", $gal_type)) $gal_type = false;
		if($site_id && $model_id) {
			if($gal_type) {
				$db = DB::get();
				if ($db) {

					$sql = "UPDATE sites_models
							SET updated_on = ?";

					if($gal_type == 'Pics') $sql .= ", gals_count = (gals_count + 1) ";
					elseif($gal_type == 'Movies') $sql .= ", video_count = (video_count + 1) ";
					$sql .= ", total_count = (gals_count + video_count)
							WHERE model_id = ? AND site_id = ?";
					
					$updated_on = time();

					$stmt = $db->prepare($sql);
					if($stmt) {
						$stmt->bind_param("iii", $updated_on, $model_id, $site_id);
						if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
						else $result = true;
						$stmt->close();
					} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	

				} else {
					$log = new Logger(__METHOD__.": No SQL connect. ",true);
				}
			} else {
				//
			}
		} else {
			//
		}
		return $result;
	}

	function getModelsLikes($site_id) { // есть в class.models.php
		$result = false;
		$site_id = intval($site_id);
		// $this->_db->debug = true;
		if ($this->id) {
			$sql = "select model_id, likes from sites_models where site_id = '".$site_id."';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
			    	foreach ($rows as $row) {
			    		$result[$row['model_id']] = $row['likes'];				    	
			    	}
				}
			}			
		}
		// var_dump($result);
		return $result;
	}

	function getModelsPageviews($site_id) { // есть в class.models.php
		$result = false;
		$site_id = intval($site_id);
		// $this->_db->debug = true;
		if ($site_id) {
			$sql = "select model_id, pageviews from sites_models where site_id = '".$site_id."';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
			    	foreach ($rows as $row) {
			    		$result[$row['model_id']] = $row['pageviews'];				    	
			    	}
				}
			}			
		}
		return $result;
	}


	// 2017 апдейт

	function getModelDbPageviews($model_id, $site_id) {
		$result = false;
		$site_id = (int)$site_id;
		if ($site_id && (int)$model_id > 0) {
			$sql = "select pageviews from sites_models where site_id = ".$site_id." and model_id = ".(int)$model_id." ;";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows && isset($rows[0])) {
			    		$result = $rows[0]['pageviews'];				    	
				}
			}			
		}
		return $result;
	}

	function setModelPageviews($model_id, $site_id, $pageviews, $force_update = false) {
		$result = false;
		$pageviews = (int)$pageviews;
		$model_id = (int)$model_id;
		$site_id = (int)$site_id;
		// $this->_db->debug = true;
		if ($site_id > 0 && $pageviews >= 0 && (int)$model_id) {

			if ($force_update) {
				$existing_pageviews = null;
			} else {
				$existing_pageviews = $this->getModelDbPageviews($model_id, $site_id);
			}

			if($existing_pageviews !== $pageviews) {

				if($force_update || $pageviews > $existing_pageviews) {
					$sql = "update sites_models 
							set 
								pageviews = '".$pageviews."'
							where site_id = ".$site_id." and model_id = '".$model_id."';";
					$rs = $this->_db->query($sql);
					if ($rs) {
						return true;
					}
				} else {
					// no need to update
				}			
			} else {
				// no need to update
			}

			
		}
		return $result;
	}

	function updateModelPageviews($model_id, $site_id, $pageviews) {
		$result = false;
		$pageviews = (int)$pageviews;
		$model_id = (int)$model_id;
		$site_id = (int)$site_id;
		// $this->_db->debug = true;
		if ($site_id > 0 && $pageviews > 0 && $model_id > 0) {

			$sql = "update
						sites_models 
					set 
						pageviews = pageviews +".$pageviews."
					where site_id = ".$site_id." and model_id = '".$model_id."';";
					$rs = $this->_db->query($sql);
					if ($rs) {
						return true;
					}


			
		}
		return $result;
	}	

	/*
	// old one

	function updateModelPageviews($model_id, $site_id, $pageviews) {
		$result = false;
		$site_id = intval($site_id);
		$pageviews = intval($pageviews);
		// $this->_db->debug = true;
		if ($site_id && $pageviews > 0 && $model_id) {
			// $this->_db->debug = true;
			$updated_on = time();
			$sql = "select pageviews from sites_models where model_id = '".$model_id."' and site_id = '".$site_id."';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) $sql = "update sites_models set pageviews = '".$pageviews."', updated_on = '".$updated_on."' where model_id = '".$model_id."' and site_id = '".$site_id."';";
				else $sql = "insert into sites_models (model_id, site_id, pageviews) values ('".$model_id."', '".$site_id."', '".$pageviews."');";
				$rs = $this->_db->query($sql);
				if ($rs) return true;
			}
			
		}
		return $result;
	}

	*/

	function getModelDbLikes($model_id, $site_id) {
		$result = false;
		$site_id = (int)$site_id;
		if ($site_id && $model_id) {
			$sql = "select likes from sites_models where site_id = ".$site_id." and model_id = ".$model_id." ;";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows && isset($rows[0])) {
			    		$result = $rows[0]['likes'];				    	
				}
			}			
		}
		return $result;
	}

	function setModelLikes($model_id, $site_id, $likes, $force_update = false) {
		$result = false;
		$likes = (int)$likes;
		$model_id = (int)$model_id;
		$site_id = (int)$site_id;
		// $this->_db->debug = true;
		if ($site_id > 0 && $likes >= 0 && $model_id > 0 ) {

			echo $site_id .":". $likes .":". $model_id .":";

			if ($force_update) {
				$existing_likes = null;
			} else {
				$existing_likes = $this->getModelDbLikes($model_id, $site_id);
			}

			echo $existing_likes."<br>";
			if($existing_likes !== $likes) {

				if($force_update || $likes > $existing_likes) {
					$sql = "update sites_models 
							set 
								likes = '".$likes."'
							where site_id = ".$site_id." and model_id = '".$model_id."';";
					$rs = $this->_db->query($sql);
					if ($rs) {
						return true;
					} else {
						echo $this->_db->error;
					}
				} else {
					// no need to update
				}			
			} else {
				// no need to update
			}

			
		}
		return $result;
	}


	function updateModelLikes($model_id, $site_id, $likes) {
		$result = false;
		$likes = (int)$likes;
		$model_id = (int)$model_id;
		$site_id = (int)$site_id;
		// $this->_db->debug = true;
		if ($site_id > 0 && $likes > 0 && $model_id > 0) {

			$sql = "update
						sites_models 
					set 
						likes = likes +".$likes."
					where site_id = ".$site_id." and model_id = '".$model_id."';";
					$rs = $this->_db->query($sql);
					if ($rs) {
						return true;
					}


			
		}
		return $result;
	}	

	/*

	function updateModelLikes($model_id, $site_id, $likes) {
		$result = false;
		$site_id = intval($site_id);
		$likes = intval($likes);
		// $this->_db->debug = true;
		if ($site_id && $likes > 0 && $model_id) {
			// $this->_db->debug = true;
			$sql = "select likes from sites_models where model_id = '".$model_id."' and site_id = '".$site_id."';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) $sql = "update sites_models set likes = '".$likes."', updated_on = '".$updated_on."' where model_id = '".$model_id."' and site_id = '".$site_id."';";
				else $sql = "insert into sites_models (model_id, site_id, likes) values ('".$model_id."', '".$site_id."', '".$likes."');";
				$rs = $this->_db->query($sql);
				if ($rs) return true;
			}
			
		}
		return $result;
	}
*/

	/* галеры на сайте, с лайками и прочим */
	function getModelsGalleries ($model_id, $site_id) { // есть в моделях
		$result = false;
		$model_id = intval($model_id);
		$site_id = (int)$site_id;
		if ($model_id && $site_id && $this->_db) {
			 // $this->_db->debug = true;
			$sql = "select  site_".intval($site_id).".gal_id, site_".intval($site_id).".id, 
							site_".intval($site_id).".time_added, galleries.gal_type,
							sites_models.likes, sites_models.pageviews
					from site_".intval($site_id)."
					left join galleries_models on galleries_models.gallery_id = site_".intval($site_id).".gal_id
					left join sites_models on galleries_models.model_id = sites_models.model_id
					left join galleries on site_".intval($site_id).".gal_id = galleries.gal_id
					where galleries_models.model_id = '".$model_id."'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) $result = $rows;
			}
		}
		return $result;
	}

	//////

	function getSiteModelsList ($site_id, $niche = false, $type = false) {
		$result = false;
		$site_id = intval($site_id);
		$where_used = false;
		if ($niche && preg_match("#(gay|shemale|staright)#im", $niche)) $niche = ucfirst(strtolower($niche));
		else $nice = false;
		if ($type && preg_match("#(pics|movies)#im", $type)) $type = ucfirst(strtolower($type));
		elseif($type == 'gifs' || $type == 'gifs') $type = 'gif';
		else $type = false;
		if ($site_id) {
			if (!$niche && ! $type) {
				$sql = "select  galleries_models.model_id, model.name, model.picture, 
								model.category_of_age,
								count(site_".$site_id.".gal_id) as model_galleries_count,
								sites_models.likes, sites_models.pageviews
						from  site_".$site_id."
						left join galleries_models on site_".$site_id.".gal_id = galleries_models.gallery_id
						left join model on galleries_models.model_id = model.id_model
						left join sites_models on (
													sites_models.model_id = model.id_model 
													AND sites_models.site_id = '".$site_id."'
												  )
						group by galleries_models.model_id
						order by model.name";
			} else {
				$sql = "select  galleries_models.model_id, model.name, model.picture, 
								model.category_of_age, 
								count(site_".$site_id.".gal_id) as model_galleries_count,
								sites_models.likes, sites_models.pageviews
						from  site_".$site_id."
						left join galleries_models on site_".$site_id.".gal_id = galleries_models.gallery_id
						left join model on galleries_models.model_id = model.id_model
						left join sites_models on ( 
													sites_models.model_id = model.id_model 
													AND sites_models.site_id = '".$site_id."'
												  )
						left join galleries on galleries.gal_id = site_".$site_id.".gal_id";

				if ($type) {
					$where_used = true;
					$sql .= " where galleries.gal_type = '".$type."' ";
				}
				if ($niche) {
					if (!$where_used) $sql .= " where ";
					else $sql .= " and ";
					$sql .= " galleries.gal_niche = '".$niche."' ";

				}
				$sql .= "group by galleries_models.model_id
						 order by model.name";

			}
			//echo $sql ."<br>";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery_rs) {
					foreach ($gallery_rs as $model) {
						// var_dump($model);
						if ($model['model_id']) {
							$output['id'] = $model['model_id'];
							$output['count'] = $model['model_galleries_count'];
							$output['name'] = $model['name'];
							$output['likes'] = $model['likes'];
							$output['category_of_age'] = $model['category_of_age'];
							if ($model['likes']) $output['likes'] = intval($model['likes']);
			    			else $output['likes'] = 0;
			    			if ($model['pageviews']) $output['pageviews'] = intval($model['pageviews']);
			    			else $output['pageviews'] = 0;
							$output['picture'] = $model['picture'];
							
							$result[$output['id']] = $output;
						}
					}
				}
			}
		}
		return $result;
	}

	function getSiteModelsIdsList ($site_id) {
		$result = false;
		$site_id = intval($site_id);

		
		if ($site_id) {
				$sql = "select  galleries_models.model_id
						from  site_".$site_id."
						left join galleries_models on site_".$site_id.".gal_id = galleries_models.gallery_id
						group by galleries_models.model_id";



			echo $sql ."<br>";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery_rs) {
					foreach ($gallery_rs as $model) {
						// var_dump($model);
						if ($model['model_id']) {
							$result[$model['model_id']] = $model['model_id'];
						}
					}
				}
			}
		}
		return $result;
	}

	function getSitesModelGalleriesCount($model_id, $site_id, $gal_type) {
		$result = false;
		

		$site_id = (int)$site_id;
		$model_id = (int)$model_id;

		if(!preg_match("#^(Pics|Movies)$#", $gal_type)) {
			$gal_type = false;
			$log = new Logger(__METHOD__.": Тип галеры указан неправильно",true);
			return $result;
		}

		$db = DB::get();

		if ($db) {
			if($model_id && $site_id) {
				$sql = "select  count(site_".$site_id.".gal_id) as model_galleries_count
						from  site_".$site_id."
						inner join galleries_models on site_".$site_id.".gal_id = galleries_models.gallery_id
						inner join galleries on galleries.gal_id = site_".$site_id.".gal_id
						where galleries.gal_type = ? AND galleries_models.model_id = ?";

				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("si", $gal_type, $model_id);
					if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
					else {
						$stmt->bind_result($result);
						$stmt->fetch();
					}
					$stmt->close();
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	
			} else {
				$log = new Logger(__METHOD__.": model_id или site_id неверный, невозможно проапдейтить количество галер модели",true);
			}
		} else {
			$log = new Logger(__METHOD__.": No SQL connect. ",true);
		}
		
		return $result;		
	}


	function fixSitesModelsTable($site_id, $models_list = false) {
		$result = false;
		$db = DB::get();
		$site_id = (int)$site_id;

		$model_id = false;
		$added_on = false;
		$updated_on = false;
		$total_count = false;
		$category_of_age = false;

		if ($db) {
			if(!$models_list) $models_list = $this->getSiteModelsList($site_id);
			if($models_list && is_array($models_list)) {
				$sql = "INSERT INTO sites_models
						(model_id, site_id, added_on, updated_on, total_count, category_of_age, name) ";
				$sql .= " VALUES ";
				$added_on = time();
				$updated_on = time();
				foreach ($models_list as $id => $model) {
						if($model && $model['id']) {
							$model_id = $model['id'];
							$total_count = $model['count'];
							$name = $model['name'];
							$category_of_age = $model['category_of_age'];	
	
							$sql .= "('".$model_id."','".$site_id."','".$added_on."','".$updated_on."','".$total_count."','".$category_of_age."','".$name."'),";
						}						
				}
				$sql = trim($sql,",");
				// var_dump($sql);
				$stmt = $db->prepare($sql);
				if($stmt) {
					if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
					else {
						// echo "Sites' models table inserted!";
						$result = true;
					}
					$stmt->close();
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	

				
			}
		} else {
			$log = new Logger(__METHOD__.": No SQL connect. ",true);
		}
		
		return $result;
	}


	function fixSitesModelsGalsCount($site_id, $model_id = false) {
		$result = false;
		$site_id = (int)$site_id;

		if($site_id) {
			$db = DB::get();
			if ($db) {
				if($model_id !== false) {
					$model_id = (int)$model_id;
					if(!$model_id) {
						$log = new Logger(__METHOD__.": метод вызван с указанием model_id, но model_id провалил перевод в int", true);
						return $result;
					}

					$models_list = array($model_id);
				} else {
					$models_list = $this->getSiteModelsIdsList($site_id);	
				}
				var_dump($models_list);
				if($models_list) {

					foreach($models_list as $model_id) {
						$pics_galleries = $this->getSitesModelGalleriesCount($model_id, $site_id, 'Pics');
						$movies_galleries = $this->getSitesModelGalleriesCount($model_id, $site_id, 'Movies');
						var_dump($pics_galleries, $movies_galleries);
						$this->updateSitesModelGalleriesCountBothTypes($site_id, $model_id, $pics_galleries, $movies_galleries);

					}
				
				} else {
					$log = new Logger(__METHOD__.": Models list is empty. ",true);	
				}
			} else {
				$log = new Logger(__METHOD__.": No SQL connect. ",true);
			}
		} else {
			$log = new Logger(__METHOD__.": site_id NOT INT. ",true);
		}

		return $result;
	}


	// заполнение таблицы site_".$site_id."_models_".$type."
	// все в переделку!!
	function addModelsToSite ($site_id, $type = 'pics') {
		if($this->_db === false)
			return false;

		$site_id = (int)$site_id;

		if ($type == 'pics') {
			$typeInsertion = 'Pics';
		} elseif($type == 'gif' || $type == 'gifs') {
			$typeInsertion = 'gif';
			$type = 'gif';
		} else {
			$typeInsertion = 'Movies';
			$type = 'movies';
		}
		$gal_type = $typeInsertion;

		if ($this->ifTableExist($site_id)) {
			$sql = "SELECT site_".$site_id.".gal_id, site_".$site_id.".id, galleries_models.model_id
					FROM site_".$site_id."
					JOIN galleries_models ON galleries_models.gallery_id = site_".$site_id.".gal_id
					LEFT JOIN galleries ON galleries.gal_id = site_".$site_id.".gal_id
					WHERE site_".$site_id.".gal_id IN 
						(SELECT gallery_id FROM galleries_models WHERE gallery_id = site_".$site_id.".gal_id)
					AND site_".$site_id.".id NOT IN (SELECT local_id FROM site_".$site_id."_models_".$type.")
					AND galleries.gal_type = '".$typeInsertion."'";
					// var_dump($sql);
			$rs = $this->_db->query($sql);
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);

			

			foreach ($gallery_rs as $value)
			{

	  			$local_id = $value['id'];
	  			$model_id = $value['model_id'];
	  			

	  			
	  			$sql = "INSERT INTO site_".$site_id."_models_".$type." 
	  					(id_model, local_id) 
	  					VALUES ('".$model_id."', '".$local_id."')";
				$affected_rows = false;	  			
				if($modelInsert = $this->_db->query($sql)) {
					$this->plusOneModelGallery($site_id, $model_id, $gal_type);
					// echo "Model ". $model_id. " added to Site ".$site_id."<br>";	
				} else echo "Error on model ". $model_id. " when try to addto Site ".$site_id."<br>";

			}
			return true;
		} else {
			echo "Table not exist";
			return false;
		}
	}

	function addOneModelGalleryToSite($site_id, $model_id, $local_gal_id, $gal_type) {
		$result = false;

		$site_id = (int)$site_id;
		$model_id = (int)$model_id;
		$local_gal_id = (int)$local_gal_id;
		$gal_type_ok = preg_match("#^(Pics|Movies)$#", $gal_type);
		if($site_id && $model_id && $local_gal_id && $gal_type_ok) {
			$model_exists = $this->isModelExistsOnSite($site_id, $model_id);
			$db = DB::get();

			if(!$model_exists) {
				// add model to sites_models
				$this->switchModel($model_id);
				$name = $this->getName();
				$category_of_age = $this->getCategoryOfAge();

				$model_info_array = array(
										array(
											"id" => $model_id,
											"name" => $name,
											"category_of_age" => $category_of_age,
											"count" => 0
										)
									);
				$model_exists = $this->fixSitesModelsTable($site_id, $model_info_array);

			}

			if($model_exists) {
				$sql = "INSERT INTO site_".$site_id."_models_".strtolower($gal_type)." 
		  				(id_model, local_id) 
		  				VALUES ('".$model_id."', '".$local_gal_id."')";
				$affected_rows = false;	  			
				// $this->_db->debug = true;
				if($modelInsert = $this->_db->query($sql)) {
					$this->plusOneModelGallery($site_id, $model_id, $gal_type);
					// echo "Model ". $model_id. " added to Site ".$site_id."<br>";	
				} else echo "Error on model ". $model_id. " when try to addto Site ".$site_id."<br>";
			} else {
				$log = new Logger(__METHOD__.": Ошибка инсерта новой модели в базу данных :(",true);	
			}			

		} else {
			$log = new Logger(__METHOD__.": Входящие данные неверные (один из параметров не INT > 0",true);
		}
		

		return $result;
	}

	function isModelExistsOnSite($site_id, $model_id) {
		$result = false;
		$db = DB::get();

		if ($db) {
			if($model_id && $site_id) {
				$sql = "SELECT id FROM sites_models WHERE site_id = ? AND model_id = ?";

				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("ii", $site_id, $model_id);
					if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
					else {
						$stmt->bind_result($result);
						$stmt->fetch();
					}
					$stmt->close();
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	
			} else {
				$log = new Logger(__METHOD__.": model_id или site_id неверный, невозможно проапдейтить количество галер модели",true);
			}
		} else {
			$log = new Logger(__METHOD__.": No SQL connect. ",true);
		}
		return $result;
	}

	/* старая хрень */
	function getSiteModel ($site_id, $model_id, $type = 'pics') {
		if($this->id === false || $this->_db === false)
			return false;
		if ($type !== 'pics') {
			$typeInsertion = 'Movies';
			$type = 'movies';
		}

		$model_id = (int)$model_id;
		$site_id = (int)$site_id;

		$result = array();
		if ($this->ifTableExist($site_id) && $model_id && $site_id) {
			$rs = $this->_db->query("SELECT local_id 
										FROM site_".$site_id."_models_".$type."
										WHERE id_model = '".$model_id."'");
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($gallery_rs as $value) {
				$models[] = $value['local_id'];
			}
			if (isset($models) && is_array($models)) $result = $models;
		} else {
			$log = new Logger ("Ошибка базы данных - нет доступа к таблице site_".$site_id."_models_".$type,true);
		}
		return $result;
	}

	// функция проверки существования таблиц моделей для сайта site_1_models_pics / site_1_models_movies
	function ifTableExist ($site_id, $gals_type = 'pics') {

		$site_id = (int)$site_id;

		if(!$site_id)
			return false;
		if ($gals_type !== 'pics') {
			$typeInsertion = 'Movies';
			$gals_type = 'movies';
		}				
		$rs = $this->_db->query("SHOW TABLES LIKE 'site_".$site_id."_models_".$gals_type."'");
		$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
		if ($gallery_rs) return true;
		else return false;
	}



	//////
	
	// изменяет все данные модели (текущей, айди берется из внутренних переменных) меняется только то что не false, само собой. 
	function updateModel( $name=false, $sex=false, $hair=false, $body=false, $active=false, $birth=false, $height=false, $size=false, $role=false, $info=false, $personal_site_id=false, $picture=false, $eyes = false, $cock_boobs = false, $ethnic = false, $piercing = false, $tattoo = false, $tattooDesc = false, $country = false, $classic = false, $twitter = false, $facebook = false, $main_image = false, $main_horiz_image = false, $category_of_age = false, $zodiac = false )
	{
		if($this->id === false)
			return;

		$db_array = array();
		$update = false;
		$sql = 'UPDATE `model` SET ';

		if ($name){
			$sql .=  '`name`= :model_name';
			$db_array[':model_name'] = $name;
			$update = true;
		}

		if (in_array($active, array('yes', 'no'))) {
			if($update) $sql .=  ',';
			$sql .=  "`active`= '{$active}'";
			$update = true;
		}
		if (in_array($sex, array('female','shemale','male'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`sex`= '{$sex}'";
			$update = true;
		}
		if (in_array($role, array('top','bottom','versatile'))) {

			if ($update) $sql .=  ',';
			$sql .=  "`role`= '{$role}'";
			$update = true;
		}
		if (in_array($hair, array('blond', 'brunette', 'red', 'gray', 'white', 'bald', 'brown'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`hair`= '{$hair}'";
			$update = true;
		}
		if ( $birth !== false)	{
			if ($update) $sql .=  ',';
			$sql .=  '`birth`= :birth_date';
			$db_array[':birth_date'] = $birth;
			$update = true;
		}
		if (in_array($body, array('none', 'skinny', 'thin', 'slim', 'athletic', 'muscular', 'bodybuilder', 'chubby', 'fat'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`body_type`= '{$body}'";
			$update = true;
		}
		if ( $personal_site_id !== false) {
			if ($update) $sql .=  ',';
			$sql .=  "`personal_site_id`= {$personal_site_id}";
			$update = true;
		}
		if ( $height !== false && (int)$height >= 0) {
			if ($update) $sql .=  ',';
			$sql .=  "`height`= {$height}";
			$update = true;
		}
		if ( $size !== false && (int)$size >= 0) {
			if ($update) $sql .=  ',';
			$sql .=  "`size`= {$size}";
			$update = true;
		}
		if ( $info !== false) {
			if ($update) $sql .=  ',';
			$sql .=  '`info`= :model_info';
			$db_array[':model_info'] = $info;
			$update = true;
		}
		if ( $picture !== false && (int)$picture >= 0) {
			if ($update) $sql .=  ',';
			$sql .=  "`picture`= {$picture}";
			$update = true;
		}
		if (in_array($cock_boobs, array('mod', 'natural'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`cock_n_boobs_type`= '{$cock_boobs}'";
			$update = true;
		}
		if (in_array($eyes, array('none', 'amber', 'blue', 'brown', 'gray', 'green', 'hazel'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`eyes`= '{$eyes}'";
			$update = true;
		}
		if (in_array($ethnic, array('none','arab', 'american', 'euro', 'ebony', 'asian', 'latin', 'indian'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`ethnic`= '{$ethnic}'";
			$update = true;
		}
		if ( $country !== false) {
			if ($update) $sql .=  ',';
			$country = ($country = preg_replace('/^([^a-zA-Z- ])$/', "", $country)) ? trim(strtolower($country)) : "";
			$sql .=  '`country`= :country';
			$db_array[':country'] = $country;
			$update = true;
		}
		if (in_array($piercing, array('yes', 'no'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`piercing`= '{$piercing}'";
			$update = true;
		}
		
		if (in_array($tattoo, array('yes', 'no'))) {
			if ($update) $sql .=  ',';
			$sql .=  "`tattoo`= '{$tattoo}'";
			$update = true;
		}
	
		if ( $tattooDesc !== false) {
			if ($update) $sql .=  ',';
			$tattooDesc =  ($tattooDesc = preg_replace('/^([^a-zA-Z ])$/', "", $tattooDesc)) ? trim(strtolower($tattooDesc)) : "";
			$sql .=  '`tattoo_description`= :tattoo_description';
			$db_array[':tattoo_description'] = $tattooDesc;
			$update = true;
		}
		if ( $classic !== false) {
			$classic = ($classic != 0) ? 1 : 0;
			if ($update) $sql .=  ',';
			$sql .=  "`classic`= {$classic}";
			$update = true;
		}
		
		if ( $twitter !== false) {
			if (preg_replace('/^([^-0-9a-zA-Z ])$/', "", $twitter) || $twitter == "") {
				$twitter = trim($twitter);
				if ($update) $sql .=  ',';
				$sql .=  '`twitter`= :twitter';
				$db_array[':twitter'] = $twitter;
				$update = true;
			}			
		}
		if ( $main_image !== false && (int)$main_image >= 0) {
			if ($update) $sql .=  ',';
			$sql .=  "`main_image`= {$main_image}";
			$update = true;
		}	
		if ( $main_horiz_image !== false  && (int)$main_horiz_image >= 0) {
			if ($update) $sql .=  ',';
			$sql .=  "`main_horiz_image`= {$main_horiz_image}";
			$update = true;
		}	

		$category_of_age_updated = false;
		if($category_of_age == -1 || $category_of_age == 20 || $category_of_age == 25 || $category_of_age == 35
		|| $category_of_age == 45 || $category_of_age == 55 || $category_of_age == 65 || $category_of_age == 90) {
			if ($update) $sql .=  ',';
			$sql .=  "`category_of_age`= {$category_of_age}";
			$update = true;
			$category_of_age_updated = true;
		}
		
		if ( $zodiac !== false){
			if(!$zodiac || !in_array($zodiac, $this->zodiac_signs)) {
				$zodiac = 'none';
			}
			if ($update) $sql .=  ',';
			$sql .=  '`zodiac`= :zodiac';
			$db_array[':zodiac'] = $zodiac;
			$update = true;
			}


		$sql .= ' WHERE `model`.`id_model` = '.$this->id;

		// echo $sql;



		if ($update) {

			try {
				$stmt = $this->_db->prepare($sql);
				$stmt->execute($db_array);

			} catch(PDOException $e) {

				echo __METHOD__.' :: Ошибка добавления в базу данных: '.$e->getMessage().'<BR>';
				$log = new Logger (__METHOD__." :: Ошибка добавления в базу данных: ".$e->getMessage(), true);
				
				return false;
			}


			
			if($name || $category_of_age_updated) {
				if (!$category_of_age_updated) $category_of_age = false;
				$this->updateSitesModelInfo($this->id, $name, $category_of_age);
			}

		}
			

		return $this->getModel($this->id);
	}


	/* Апдейт денормализованной таблицы модель-> сайт */
	function updateSitesModelInfo($model_id, $name, $category_of_age) {
		$result = false;
		$db = DB::get();
		if ($db) {

				$sql = "UPDATE sites_models
						SET updated_on = ?";
				if($name) $sql .= ", name = ? ";
				if($category_of_age !== false) $sql .= ", category_of_age = ?";
				$sql .= " WHERE model_id = ?";
				
				$updated_on = time();
				$stmt = $db->prepare($sql);
				// var_dump($sql, $db->error, $model_id, $name, $category_of_age);
				if($name && $category_of_age !== false) {
					$stmt->bind_param("isii", $updated_on, $name, $category_of_age, $model_id);
				} elseif(!$name) {
					$stmt->bind_param("iii", $updated_on, $category_of_age, $model_id);
				} else {
					$stmt->bind_param("isi", $updated_on, $name, $model_id);
				}

				if($stmt) {
					if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
					else $result = true;
					$stmt->close();
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	

		} else {
			$log = new Logger(__METHOD__.": No SQL connect. ",true);
		}
		return $result;
	}



	private function updateSitesModelGalleriesCount($site_id, $model_id, $type, $gals_count) {
		$result = false;
		$db = DB::get();
		$gals_count = (int)$gals_count;
		if ($db) {

				$sql = "UPDATE sites_models
						SET updated_on = ?";

				if($type == 'Pics') $sql .= ", gals_count = ? ";
				elseif($type == 'Movies')  $sql .= ", video_count = ? ";

				$sql .= " WHERE site_id = ? AND model_id = ?";
				
				$updated_on = time();
				$stmt = $db->prepare($sql);

				
				$stmt->bind_param("iiii", $updated_on, $gals_count, $site_id, $model_id);

				if($stmt) {
					if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
					else $result = true;
					$stmt->close();
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	

		} else {
			$log = new Logger(__METHOD__.": No SQL connect. ",true);
		}
		return $result;
	}

	private function updateSitesModelGalleriesCountBothTypes($site_id, $model_id, $gals_count, $movies_count) {
		$result = false;
		$db = DB::get();
		
		if ($db) {

			$updated_on = time();
			$gals_count = (int)$gals_count;
			$movies_count = (int)$movies_count;

			$sql = "UPDATE sites_models
					SET updated_on = ?, gals_count = ? , video_count = ? 
					WHERE site_id = ? AND model_id = ?";
		
			$stmt = $db->prepare($sql);

			$stmt->bind_param("iiiii", $updated_on, $gals_count, $movies_count, $site_id, $model_id);

			if($stmt) {
				if(!$stmt->execute()) $log = new Logger(__METHOD__.": STMT excution failed: ".$db->stmt,true);					
				else $result = true;
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	

		} else {
			$log = new Logger(__METHOD__.": No SQL connect. ",true);
		}

		return $result;
	}

	function updateName($update)			{$this->updateModel($update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateSex($update)				{$this->updateModel(false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateHair($update)			{$this->updateModel(false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateBody($update)			{$this->updateModel(false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateActive($update)			{$this->updateModel(false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateBirth($update)			{$this->updateModel(false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateHeight($update)			{$this->updateModel(false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateSize($update)			{$this->updateModel(false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateRole($update)			{$this->updateModel(false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updateInfo($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false,false );}
	function updatePersonalSite($update)	{$this->updateModel(false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false,false );}
	function updatePicture($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false,false );}
	function updateEyes($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false,false );}
	function updateCockBoobs($update)		{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false,false );}
	function updateEthnic($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false,false );}
	function updatePiercing($update)		{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false,false );}
	function updateTattoo($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false,false );}
	function updateTattooDesc($update)		{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false,false );}
	function updateCountry($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false,false );}
	function updateClassic($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false,false );}
	function updateTwitter($update)			{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false );}
	function updateFacebook($update)		{$this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update );}
	function updateVerticImage($update)		{return $this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update,false );}
	function updateHorizImage($update)		{return $this->updateModel(false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,false,$update );}

	function getModelGalsCount($id = false) {

		$id = ($id) ? (int)$id : $this->id;
		
		if($id === false)
			return false;

		$rs = $this->_db->query('select count(*) from `galleries_models` where `model_id` = "'.$id.'"');
		$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);

		$count =  ($gallery_rs) ? $gallery_rs[0]['count(*)'] : 0;

		return $count;
	}
	// используется вторая таблица, где хранится id модели, id галлереи где есть модель

	function getModelGals() {
		if($this->id === false)
			return false;

		$rs = $this->_db->query('select * from `galleries_models` where `model_id` = "'.$this->id.'"');
		$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
		
		$gallery = array();

		foreach ($gallery_rs as $value) {
			$gallery[] = $value['gallery_id'];
		}
		return $gallery;
	}

	function getGalleryModels(int $gal_id) {
		$result = false;

		if ($gal_id > 0 && $db = DB::get()) {
				
				$sql = 'select model_id from `galleries_models` where `gallery_id` = ?';
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("i", $gal_id)) {
						$model_id = false;
						$stmt->bind_result($model_id);
						if($stmt->execute()) {
							while($stmt->fetch()) {
								$result[] = $model_id;
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);			
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind_param error '".$stmt->error."'", true);	
					}
				} else {
					$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);	
				}
			
		}

		return $result;
	}
	// используется еще одна таблица, где хранятся данные по известным псевдонимам модели. id модели, alias - псевдоним модели
	function getModelAliases(){}

	function setSearchFilter($sex = false, $eyes_color = false, $body_type = false, $hair_color = false, $ethnic =false, $first_letter = false, $site_id = false) {
		$this->search_filter_site_id = ($site_id && (int)$site_id > 0) ? (int)$site_id : false;
		$this->search_filter_sex = ($sex && preg_match('/^(male|female|shemale)$/', $sex)) ? $sex : false;
		$this->search_filter_eyes_color = ($eyes_color && preg_match('/^(amber|blue|brown|gray|green|hazel)$/', $eyes_color)) ? $eyes_color :false;
		$this->search_filter_body_type = ($body_type && preg_match('/^(none|skinny|thin|slim|athletic|muscular|bodybuilder|chubby|fat)$/', $body_type)) ? $body_type :false;
		$this->search_filter_hair_color = ($hair_color && preg_match('/^(blond|brunette|red|gray|white|bald|brown)$/', $hair_color)) ? $hair_color :false;
		$this->search_filter_ethnic = ($ethnic && preg_match('/^(arab|american|euro|ebony|asian|latin|indian)$/', $ethnic)) ? $ethnic :false;
		$this->search_filter_first_letter = ($first_letter && strlen($first_letter) == 1) ? strtolower($first_letter) :false;
	}

	function getModelsList($sex = false, $sort = 'name', $model_ids = false, $thumb_size = 'medium', $order = 'asc'){
		$where = false;
		$result = false;
		$conditions = array();

		$sort = in_array($sort, ['name', 'id']) ? ($sort == 'id' ? 'id_model' : $sort) : 'name';
		$order = in_array($order, ['asc', 'desc']) ? $order : 'asc';

		$sql = "select  `model`.`id_model`,
						`model`.`name`, 
						`model`.`active`, 
						`model`.`sex`, 
						`model`.`role`, 
						`model`.`hair`, 
						`model`.`birth`, 
						`model`.`body_type`, 
						`model`.`personal_site_id`, 
						`model`.`height`, 
						`model`.`size`, 
						`model`.`info`, 
						`model`.`picture`, 
						`model`.`cock_n_boobs_type`, 
						`model`.`country`, 
						`model`.`eyes`, 
						`model`.`ethnic`, 
						`model`.`tattoo`, 
						`model`.`tattoo_description`, 
						`model`.`piercing`, 
						`model`.`classic`, 
						`model`.`googleplus`, 
						`model`.`twitter`, 
						`model`.`facebook`, 
						`model`.`added_on`, 
						`model`.`main_image`, 
						`model`.`main_horiz_image`, 
						`model`.`category_of_age`, 
						`model`.`zodiac`";

		if($this->search_filter_site_id) {
			$sql .= ",
					`sites_models`.`likes`,
					`sites_models`.`pageviews`,
					`sites_models`.`gals_count`,
					`sites_models`.`video_count`,
					`sites_models`.`total_count`
					 from sites_models
					 left join model on `sites_models`.`model_id` = `model`.`id_model`";
			$conditions[] = "`sites_models`.`site_id` = ".$this->search_filter_site_id." and `sites_models`.`total_count` > 0";
		} else {
			$sql .= ",
					0 as likes,
					0 as pageviews,
					0 as gals_count,
					0 as video_count,
					0 as total_count
					from model";	
		}


		if ($sex && preg_match('/^(male|female|shemale)$/', $sex)) {
			$conditions[] = "`model`.`sex` =  '".$sex."'";
		}

		if ($model_ids !== false && is_array($model_ids)) {
				
			$counter = 0;
			$models_count = count($model_ids);
			// если отдан пуцстой массив (не найдено)
			if ($models_count === 0) return array();
			$model_text = "";
			foreach ($model_ids as $model_id) {
				$counter++;
				$model_text .= intval($model_id);
				if ($counter < $models_count) $model_text .= ",";
			}

			$conditions[] = " `model`.`id_model` in (".$model_text.")";
		}

		if($this->search_filter_eyes_color) {
			$conditions[] = " `model`.`eyes` =  '".$this->search_filter_eyes_color."'";
		}
		if($this->search_filter_body_type) {
			$conditions[] = " `model`.`body_type` =  '".$this->search_filter_body_type."'";
		}
		if($this->search_filter_hair_color) {
			$conditions[] = " `model`.`hair` =  '".$this->search_filter_hair_color."'";
		}
		if($this->search_filter_ethnic) {
			$conditions[] = " `model`.`ethnic` =  '".$this->search_filter_ethnic."'";
		}
		if($this->search_filter_first_letter) {
			$conditions[] = " `model`.`name` LIKE '".$this->search_filter_first_letter."%'";
		}

		if($conditions) {
			$sql .= " where ". implode(" and ", $conditions);
		}

		$sql .= " order by `model`.`".$sort."` ". $order;
		// var_dump($sql); die;
		$rs = $this->_db->query($sql);
		// var_dump($rs);
		$models = $rs->fetchAll(\PDO::FETCH_ASSOC);
		if(!preg_match("#^(big|medium|small)$#", $thumb_size)) $thumb_size = 'medium';
			
		foreach($models as $model) {
			$result[$model['id_model']] = $model;
			if ($model['main_image']) {
				if ($thumb_size == 'small') $thumb_pre = "/150x200/";
				elseif ($thumb_size == 'medium') $thumb_pre = "/180x240/";
				elseif ($thumb_size == 'big') $thumb_pre = "/240x320/";	
				$thumbId = $model['main_image'];
				$folder = folderNameById($thumbId);
			    $result[$model['id_model']]['image_url'] = "/models/".$thumb_pre ."/". $folder ."/".$thumbId.".jpg";
			    // echo "Main Image:".$model['id_model']."<br>";
			} elseif ($model['picture']) {
				if ($thumb_size == 'small') $thumb_pre = "/thumbs/p/150";
				elseif ($thumb_size == 'medium') $thumb_pre = "/thumbs/p/180";
				elseif ($thumb_size == 'big') $thumb_pre = "/thumbs/p/240";				
				$thumbId = $model['picture'];
				$folder =folderNameById($thumbId);
			    $result[$model['id_model']]['image_url'] = $thumb_pre ."/". $folder ."/".$thumbId.".jpg";
			} else {
				$all_images = $this->allImages($model['id_model']);
				if ($all_images && is_array($all_images) && isset($all_images[$thumb_size])) {
					$result[$model['id_model']]['image_url']  = array_shift(array_slice($all_images[$thumb_size], 0, 1));	
				} else {
					$result[$model['id_model']]['image_url'] = "#";
				}
			   	
			}
		}
		return $result;
	}

	function getClassicModelsList($sex = false, $sort = false){

		$sql = "select * from model where classic = '1'";
		if ($sex && preg_match('/^(male|female|shemale)$/', $sex)) $sql .= " and `sex` =  '".$sex."'";
		if ($sort) $sql .= " order by name asc";
		$rs = $this->_db->query($sql);
		$model = $rs->fetchAll(\PDO::FETCH_ASSOC);
		return $model;
	}	

	function getModelsPseudoList($sex = false, $sort = false){

		$sql = "select model.id_model, model.name, (
					select replace(group_concat(model_names.name),',',' | ')
					from model_names
					where
					model.id_model = model_names.model_id) as pseudo
				from model";
		if ($sex && preg_match('/^(male|female|shemale)$/', $sex)) $sql .= " where `sex` =  '".$sex."'";
		if ($sort) $sql .= " order by name asc";
		$rs = $this->_db->query($sql);
		$model = $rs->fetchAll(\PDO::FETCH_ASSOC);
		// var_dump($model);
		return $model;
	}

	function getAllModels() {
		$result = false;
		$list = $this->getModelsList();
		if ($list && is_array($list)) {
			foreach ($list as $model) {
				$result[$model['id_model']] = $model;
				$result[$model['id_model']]['id'] = $model['id_model'];
			}
		}
		return $result;
	}

	function find_models_by_string(string $name, string $niche = '') {

		$result = false;

		$name = strtolower(preg_replace("#[^a-z0-9\s]#im", " ", $name));
		$niche_addition = "";
		
		if (preg_match("#^(Gay|Straight|Shemale)$#", $niche)) {
			
			if ($niche == 'Gay') $sex = 'male';
			elseif ($niche == 'Shemale') $sex = 'shemale';
			else $sex = 'female';

			$niche_addition = " and sex = '".$sex."'";
		}

		$name = trim($name);
			
		if (empty($name)) {
			return $result;
		}
				
		$names = explode(" ", $name);				
				
		$sql = "select id_model
						from model
						left join model_names on model_names.model_id = model.id_model
						where (lower(model.name) like '%".$name."%' or lower(model_names.name) like '%".$name."%')
						".$niche_addition;
				
		$rs = $this->_db->query($sql);

		if ($rs) {
			$models = $rs->fetchAll(\PDO::FETCH_ASSOC);

			if ($models) {
						foreach ($models as $model) {

							if ($this->switchModel($model['id_model'])) {
								$result[$this->id]['id'] = $this->id;
								$result[$this->id]['name'] = $this->name;
								$result[$this->id]['thumb'] = $this->picture;
							}

						}
			}
		}

		if ($result) {
			return $result;
		}

		foreach ($names as $name) {

			if ($name != "" && strlen($name) > 3) {

						$sql = "select id_model from model where lower(name) like '%".$name."%'".$niche_addition;
						$rs = $this->_db->query($sql);

						if ($rs) {
							$models = $rs->fetchAll(\PDO::FETCH_ASSOC);
							if ($models) {
								foreach ($models as $model) {
									if ($this->switchModel($model['id_model'])) {
										$result[$this->id]['id'] = $this->id;
										$result[$this->id]['name'] = $this->name;
										$result[$this->id]['thumb'] = $this->picture;
									}
								}
							}
						}						
			}
		}
				
		return $result;
		
	}


	public function findModelByNameOrFail(string $name) {

		$modelsArray = $this->find_models_by_string($name);

		if(empty($modelsArray)) {
			throw new Exception('Модель не найдена, нужно добавить на стороне источника');			
		}
		
		if(count($modelsArray) > 1) {
			throw new Exception('Больше одной модели с этим именем, чек руками');
		}

		$resultModel = array_pop($modelsArray);

		$model = $this->getModel($resultModel['id']);

		if(empty($model)) {
			throw new Exception('Модель не найдена по ID');
		}

		return $model;
	}


	public function findModelByIdOrFail(int $modelId) {

	
		if($modelId < 1) {
			throw new Exception('Неправильный ID модели');
		}

		$model = $this->getModel($modelId);

		if(empty($model)) {
			throw new Exception('Модель не найдена по ID');
		}

		return $model;
	}


	function formattedListing ($format, $niches = false) {
		$result = false;
		if ($this->_db && $format) {
			$tags = $this->getModelsPseudoList($niches);
			foreach ($tags as $id => $tag) {
				$desc = strtolower($tag['name']);
				$desc = trim($desc);
				$desc = preg_replace ("/[^a-z0-9\s]/","",$desc);
				$desc = preg_replace ("/\s+/", " ", $desc);
				$tagUrlName = str_replace (" ", "-", $desc); // тоже самое в informator.php AddGalleryId()
				$result_p = preg_replace('/\#TAG_ID\#/', $tag['id_model'], $format);
				if ($tag['pseudo']) $tag['name'] .= " | ".$tag['pseudo'];
				$result_p = preg_replace('/\#TAG_NAME\#/', $tag['name'], $result_p);
				$result .= preg_replace('/\#TAG_URL_NAME\#/', $tagUrlName, $result_p);
			}
		}
		return $result;
	}

	function noMainImageModels() : array {
		$result = [];
		$sql = "SELECT picture, id_model FROM model WHERE main_image = '0'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$models = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($models) {
				foreach ($models as $model) {
						$result[$model['id_model']]['id'] = $model['id_model'];
						$result[$model['id_model']]['picture'] = $model['picture'];
				}
			}
		}
		return $result;
	}

	public function DB_getSiteModels() {
		$result = false;
			$db = DB::get();
			if ($db) {
				$sql = "SELECT model_id, site_id FROM sites_models";

				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->execute()) {
						$result_1 = null;
						$stmt->bind_result($result, $result_1);
						if($stmt->fetch()) var_dump($result, $result_1);
						else echo "empty!!!!";
						while($stmt->fetch()) {
							var_dump($result, $result_1);
						}
					} else { $log = new Logger(__METHOD__.": DB execute failed: ".$stmt->error,true); }
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }	
			} else {  
				$log = new Logger(__METHOD__.": No SQL connect. ",true);
			}	

		return $result;
	}

	function getModelByName($name, $niche = false) {
		$result = false;
		$name = strtolower($name);
		if ($niche && preg_match("#^(Gay|Straight|Shemale)$#", $niche)) {
			if ($niche == 'Gay') $sex = 'male';
			elseif ($niche == 'Shemale') $sex = 'shemale';
			else $sex = 'female';
			$niche_addition = " AND sex = '".$sex."'";
		} else {
			$niche_addition = "";
		}
		$sql = "SELECT id_model FROM model WHERE LOWER(name) = ?". $niche_addition;
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("s", $name)) {
						if($stmt->execute()) {
							$stmt->bind_result($result);
							$stmt->fetch();
						} else { $log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error,true); }
					} else { $log = new Logger(__METHOD__.": STMT Bind Param failed: ".$db->error,true); }	
					$stmt->close();				
				} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }
			} else { $log = new Logger(__METHOD__.": SQL string is empty", true); }
		} else { $log = new Logger(__METHOD__.": No DB connect", true); }
		
		return $result;
	}

/*
4. Общий метод
titleToModels (принимает тайтл, возвращает массив с айди моделей встреченых в тайтле, можно сделать простой поиск пока, в дальнейшем это надо будет доработать и сделать поиск на основе словаря имен и описок)
Мне кажется имеет смысл добавить  в таблицу поля name_id, surename_id где будут указаны цифровые обозначения имени и фамилии, уоторые будут храниться в отдельной тааблицы для более удобного поиска :-\
*/
}
