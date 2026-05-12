<?php

class Images
{
	var $_db;

	// номер модели

	function __construct()
	{
		$this->_db = DB::get();
		//parent::__construct();
	}

	public function getGalleryIdByImageId(int $imageId): ?int
	{
		$sql = "SELECT galleries_pix.gal_id FROM galleries_pix WHERE image_id = :image_id LIMIT 1";

		$sqlVars['image_id'] = $imageId;

		try {
			$db = PDOConnection::get();
			$stmt = $db->prepare($sql);

			foreach ($sqlVars as $key => &$value) {
				$stmt->bindParam(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
			}
			$stmt->execute();

			$output = $stmt->fetch(PDO::FETCH_ASSOC);

			if (empty($output)) {
				return null;
			}

			// var_dump($output); die;

			return $output['gal_id'];
		} catch (Exception $e) {
			$log = new Logger(__METHOD__ . ": Ошибка запроса: " . $e->getMessage());
			return null;
		}
	}

	private function getThumbFolderById(int $imageId): string
	{
		$folderId = (int) ceil($imageId / 1000);
		$mainFolder = ($imageId < 256000) ? 1 : (int) ceil($imageId / 256000);
		return "$mainFolder/$folderId";
	}

	// возвращает массив заполненый данными о модели с id, объект переинициализируется в соответствии с айди.
	function getGalImages(int $galId)
	{
		$sql = "SELECT 
					galleries_pix.image_id, galleries_pix.image
				FROM 
					galleries_pix 
				WHERE galleries_pix.gal_id = :gal_id;";

		$sqlVars['gal_id'] = $galId;

		$images = [];

		try {
			$db = PDOConnection::get();
			$stmt = $db->prepare($sql);
			foreach ($sqlVars as $key => &$value) {
				$stmt->bindParam(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
			}
			$stmt->execute();

			$output = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($output)) {
				return [];
			}

			foreach ($output as $image) {
				$images[$image['image_id']] = $image['image'];
			}

			return $images;
		} catch (Exception $e) {
			$log = new Logger(__METHOD__ . ": Ошибка запроса: " . $e->getMessage());
			return [];
		}



		return $output;
	}

	function getGalImagesWithScale(int $galId)
	{
		$sql = "SELECT 
					image_id, 
					image, 
					ratio_w_h
				FROM galleries_pix
				WHERE gal_id = :gal_id;";

		$sqlVars['gal_id'] = $galId;

		$images = [];

		try {
			$db = PDOConnection::get();
			$stmt = $db->prepare($sql);
			foreach ($sqlVars as $key => &$value) {
				$stmt->bindParam(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
			}
			$stmt->execute();

			$output = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($output)) {
				return [];
			}

			foreach ($output as $image) {
				$images[$image['image_id']]['image'] = $image['image'];
				$images[$image['image_id']]['scale'] = $image['ratio_w_h'];
			}

			return $images;
		} catch (Exception $e) {
			$log = new Logger(__METHOD__ . ": Ошибка запроса: " . $e->getMessage());
			return [];
		}



		return $output;
	}


	function getGalImagesWitCropInfo(int $galId)
	{
		$sql = "SELECT 	galleries_pix.image_id, 
						galleries_pix.image, 
						galleries_pix.ratio_w_h, 
						scr_manual_crop_history.x_coord, 
						scr_manual_crop_history.y_coord, 
						scr_manual_crop_history.width, 
						scr_manual_crop_history.height
				FROM galleries_pix
				LEFT JOIN scr_manual_crop_history ON galleries_pix.image_id = scr_manual_crop_history.image_id				 
				WHERE galleries_pix.gal_id = :gal_id;";

		$sqlVars['gal_id'] = $galId;

		$images = [];

		try {
			$db = PDOConnection::get();
			$stmt = $db->prepare($sql);
			foreach ($sqlVars as $key => &$value) {
				$stmt->bindParam(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
			}
			$stmt->execute();

			$output = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($output)) {
				return [];
			}

			foreach ($output as $image) {
				$cropCoordinates = $image['width'] ? [
					'x' => $image['x_coord'],
					'y' => $image['y_coord'],
					'width' => $image['width'],
					'height' => $image['height'],
				] : [];

				$imageId = $image['image_id'];

				$thumbPath = $this->getThumbFolderById($imageId);

				$images[$imageId] = [
					'image_id' => $imageId,
					'image_url' => $image['image'],
					'image_thumb_path' => "/{$thumbPath}/{$imageId}.jpg",
					'image_crop_info' => $cropCoordinates,
					'scale' => $image['ratio_w_h'],

				];
			}

			return $images;
		} catch (\Exception $e) {
			$log = new Logger(__METHOD__ . ": Ошибка запроса: " . $e->getMessage());
			echo $e->getMessage();
			die;

			return [];
		}



		return $output;
	}

	// смена модели по айди, достает все данные по модели $id из базы, иначе false
	function switchImages($galId)
	{
		$images = $this->getGalImages($galId);
		if (!is_array($images))
			return false;
		return true;
	}

	public function getTrashCount()
	{
		$connect = DB::get();
		$result = false;
		if ($connect) {
			$sql = "SELECT COUNT(image_id) FROM trash_box_thumbs";
			$stmt = $connect->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					if ($stmt->bind_result($result)) {
						$stmt->fetch();
					} else {
						$log = new Logger(__METHOD__ . ": SQL STMT bind_result failed \n '" . $sql . "'" . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL STMT execute failed \n '" . $sql . "'" . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL STMT execute failed \n '" . $sql . "'" . $stmt->error, true);
			}
		}

		return $result;
	}

	public function insertImagesArray($gal_id, $images)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id) {
			if (is_array($images)) {
				foreach ($images as $image) {
					$this->insertImage($gal_id, $image);
				}
			} else {
				$log = new Logger(__METHOD__ . ": images не array", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": gal_id не Integer", true);
		}
		return $result;
	}

	public function insertImage($gal_id, $thumb)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($gal_id) {
			$connection = DB::get();
			if ($connection) {
				if (is_string($thumb)) {
					$thumb = trim($thumb);
					if (!preg_match("#^http[s]{0,1}?:\/\/(.*)\.(jpeg|jpg)$#", $thumb)) {
						$log = new Logger(__METHOD__ . ": GID:" . $gal_id . ", тумба '" . $thumb . "' не прошла проверку урла (preg_match)", true);
						$thumb = false;
					}
				} else {
					$log = new Logger(__METHOD__ . ": GID:" . $gal_id . ", тумба не прошла проверку is_string", true);
					$thumb = false;
				}

				if ($thumb) {
					$thumbHash = md5($thumb);
					$connection->debug = true;
					$stmt = $connection->prepare("INSERT INTO images_sources (gal_id, image_source, hash) VALUES (?,?,?)");
					$stmt->bind_param("iss", $gal_id, $thumb, $thumbHash);
					if ($stmt->execute()) $result = true;
					else $log = new Logger(__METHOD__ . ": GID:" . $gal_id . ", Ошибка добавления тумбы для'" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": нет коннекта к базе данных", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": gal_id не Integer", true);
		}

		return $result;
	}

	public function getImagesURLs($gal_id)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($gal_id) {
			$connection = DB::get();
			if ($connection) {
				$sql = "SELECT thumb_id, image_source FROM images_sources WHERE gal_id = ?";
				$stmt = $connection->prepare($sql);
				$stmt->bind_param("i", $gal_id);
				if ($stmt->execute()) {
					$thumb_id = null;
					$image_source = null;
					if ($stmt->bind_result($thumb_id, $image_source)) {
						$stmt->store_result();
						while ($stmt->fetch()) {
							$result[$thumb_id] =  $image_source;
						}
						$stmt->close();
					}
				} else {
					$log = new Logger(__METHOD__ . ": GID:" . $gal_id . ", Ошибка выборки тумб. Ошибка:'" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": нет коннекта к базе данных", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": gal_id не Integer", true);
		}

		return $result;
	}
}
