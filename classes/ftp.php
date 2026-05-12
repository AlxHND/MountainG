<?php
class FTPtools
{
	static $FTP;
	static $login;
	static $pass;
	static $connect;

	public $error;

	function __construct ($ftp, $login, $pass) {
		self::$FTP = $ftp;
		self::$login = $login;
		self::$pass = $pass;
	}

	public function ShowError () {
		
	}
	public function CheckFTP ($dir = "") {
		if ($this->connect = @ftp_connect($this->FTP)) {
			if (@ftp_login($this->connect, $this->login, $this->pass)) {
				if ($dir !== "") {
					return ftp_rawlist($this->connect,$dir);
				} else {
					return ftp_rawlist($this->connect,"");
				}
			} else {
				$this->error = "Ошибка логина.";
				return FALSE;
			}
		} else {
			$this->error = "Не могу подключиться к хосту.";
			return FALSE;
		}
	}

	public function UploadToLocal($file, $uploadToFolder) {
//		echo "Upload to folder: " . $uploadToFolder ."<br />";
		$folderHost = UPLOADFOLDER;
		$uploadFoldersTree = explode("/", $uploadToFolder);
		$tempFolderToMake = "";

		foreach ($uploadFoldersTree as $folder) {
			if ($folder !== "") {
				$tempFolderToMake .= "/" . $folder;
				
				if (!@is_dir($folderHost . $tempFolderToMake)) {
					if(mkdir($folderHost . $tempFolderToMake, 0777)) chmod($folderHost.$tempFolderToMake, 0777);
					else {
						echo "Не могу создать директорию ".$folderHost.$tempFolderToMake;
						$log = new Logger("Не могу создать директорию ".$folderHost.$tempFolderToMake);
						return false;
					}
				}	
			}
		}

		$uploadToFile = $tempFolderToMake ."/" . basename($file);
		copy($file,$folderHost.$uploadToFile) or die ("Can't upload " . $file . " to " .$uploadToFile);
		if (is_file($folderHost.$uploadToFile)) chmod($folderHost.$uploadToFile, 0777);
		return $uploadToFile;
	}

	public function copyFileToLocal ($file, $fileName, $uploadToFolder) {
		//echo "Upload to folder: " . $uploadToFolder ."<br />";
		$uploadToFile = false;
		$uploadFoldersTree = explode("/", $uploadToFolder);
		$tempFolderToMake = "";
		$folderHost = UPLOADFOLDER;

		foreach ($uploadFoldersTree as $folder) {
			if ($folder !== "") {
				$tempFolderToMake .= "/" . $folder;
				
				if (!@is_dir($folderHost . $tempFolderToMake)) {
					if(mkdir($folderHost . $tempFolderToMake, 0777)) chmod($folderHost.$tempFolderToMake, 0777);
					else {
						echo "Не могу создать директорию ".$folderHost.$tempFolderToMake;
						$log = new Logger("Не могу создать директорию ".$folderHost.$tempFolderToMake);
						return false;
					}
				}	
			}
		}

		$uploadToFile = $tempFolderToMake ."/" . basename($fileName);
		copy($file,$folderHost.$uploadToFile) or die ("Can't upload " . $file . " to " .$uploadToFile);
		if (is_file($folderHost.$uploadToFile)) chmod($folderHost.$uploadToFile, 0777);

		return $uploadToFile;
	}

	public function UploadLocalFiles ($files, $folderHost, $uploadToFolder) {

		$uploadFoldersTree = explode("/", $uploadToFolder);
		$tempFolderToMake = "";

		foreach ($uploadFoldersTree as $folder) {
			if ($folder !== "") {
				$tempFolderToMake .= "/" . $folder;
				if (!@is_dir($folderHost . $tempFolderToMake)) {
					if(mkdir($folderHost . $tempFolderToMake, 0777) or die("Не могу создать директорию ".$folderHost.$tempFolderToMake)) chmod($folderHost . $tempFolderToMake, 0777);
				}
			}
		}

		$i=0;
		foreach ($files as $file) {
			$uploadToFile [$i] = $uploadToFolder ."/" . basename($file);
			copy($file,$folderHost.$uploadToFile[$i]) or die ("Can't upload " . $file . " to " .$folderHost.$uploadToFile[$i]);
			if (is_file($folderHost.$uploadToFile[$i])) chmod($folderHost.$uploadToFile[$i], 0777);

			$i++;
		}

		return $uploadToFile;
	}

	public function UploadOneFile ($file, $uploadToFolder, $folderHost = "") {
		$this->connect = ftp_connect($this->FTP) or die ("error ftp connection");
		ftp_login($this->connect, $this->login, $this->pass) or die ("can't login");

		$uploadFoldersTree = explode("/", $uploadToFolder);
		$tempFolderToMake = "";

		foreach ($uploadFoldersTree as $folder) {
			if ($folder !== "") {
				$tempFolderToMake .= "/" . $folder;
			
				if (!@ftp_chdir($this->connect, $folderHost.$tempFolderToMake)) ftp_mkdir($this->connect, $folderHost.$tempFolderToMake) or die("Не могу создать директорию по ФТП {$tempFolderToMake}");
			}
		}

		$uploadToFile = $tempFolderToMake ."/" . basename($file);
		ftp_put($this->connect, $folderHost.$uploadToFile, $file, FTP_BINARY) or die ("Can't upload " . $file . " to " . $folderHost.$uploadToFile);
		ftp_close($this->connect);

		return $uploadToFile;
	}

	public function UploadFiles ($files, $uploadToFolder, $folderHost = "") {
		$this->connect = ftp_connect($this->FTP) or die ("error");
		ftp_login($this->connect, $this->login, $this->pass) or die ("can't login");

		$uploadFoldersTree = explode("/", $uploadToFolder);
		$tempFolderToMake = "";

		foreach ($uploadFoldersTree as $folder) {
			if ($folder !== "") {
				$tempFolderToMake .= "/" . $folder;
				if (!@ftp_chdir($this->connect, $folderHost . $tempFolderToMake)) ftp_mkdir($this->connect, $folderHost . $tempFolderToMake) or die("Не могу создать директорию по ФТП {$tempFolderToMake}");
			}
		}

		$i=0;
		foreach ($files as $file) {
			$uploadToFile [$i] = $tempFolderToMake ."/" . basename($file);
			ftp_put($this->connect, $folderHost . $uploadToFile[$i], $file, FTP_BINARY) or die ("Can't upload " . $file . " to " . $folderHost . $uploadToFile[$i]);
			$i++;
		}
		ftp_close($this->connect);
		return $uploadToFile;
	}
}