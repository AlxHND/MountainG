<?php

class Trash{

	public function getTrashImagesCount() {
		$connect = DB::get();
		$result = false;
		if($connect) {
			$sql = "SELECT COUNT(image_id) FROM trash_box_thumbs";
			$stmt = $connect->prepare($sql);
			if ($stmt) {
					if ($stmt->execute()) {
						if ($stmt->bind_result( $result)) {
							$stmt->fetch(); 
						} else { $log = new Logger(__METHOD__.": SQL STMT bind_result failed \n '".$sql."'".$stmt->error, true); }
					} else { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }
			
			} else { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }	
		}
		
		return $result;
	}

	public function getTrashGalleriesCount() {
		$connect = DB::get();
		$result = false;
		if($connect) {
			$sql = "SELECT COUNT(gal_id) FROM galleries WHERE gal_status = 'trash'";
			$stmt = $connect->prepare($sql);
			if ($stmt) {
					if ($stmt->execute()) {
						if ($stmt->bind_result( $result)) {
							$stmt->fetch(); 
						} else { $log = new Logger(__METHOD__.": SQL STMT bind_result failed \n '".$sql."'".$stmt->error, true); }
					} else { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }
			
			} else { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }	
		}
		
		return $result;
	}

	public function getTrashTableGalleriesCount() {
		$connect = DB::get();
		$result = false;
		if($connect) {
			$sql = "SELECT COUNT(gal_id) FROM trash";
			$stmt = $connect->prepare($sql);
			if ($stmt) {
					if ($stmt->execute()) {
						if ($stmt->bind_result( $result)) {
							$stmt->fetch(); 
						} else { $log = new Logger(__METHOD__.": SQL STMT bind_result failed \n '".$sql."'".$stmt->error, true); }
					} else { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }
			
			} else { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }	
		}
		
		return $result;
	}	

	  	/* Cleaning methods */
  	public function clearImagesSources() {
		$db = DB::get();

		$sql = "DELETE A FROM images_sources A
				LEFT JOIN galleries B on A.gal_id = B.gal_id
				WHERE B.gal_id IS NULL";
		$db->query($sql);
	}
}