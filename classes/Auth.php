<?php

class Auth {

	private $db;
	private $user;

	function __construct(PDO $db_connect) {
		$this->db = $db_connect;
		$this->start();
		$this->refreshUser();
	}

	public function start() {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}

	public function refreshUser() {
		$this->user = new Users($this->db);
		return $this->user;
	}

	public function user() {
		if (!$this->user instanceof Users) {
			$this->refreshUser();
		}

		return $this->user;
	}

	public function isAuthorized() {
		return $this->user()->isAuthorized();
	}

	public function attemptLogin($login, $password) {
		$result = $this->user()->login($login, $password);
		$this->refreshUser();

		return $result;
	}

	public function logout() {
		$result = $this->user()->logout();
		$this->refreshUser();

		return $result;
	}

	public function requireAuthorizedJson($message = 'Ошибка аутентификации.') {
		if (!$this->user()->isAuthorized()) {
			$this->jsonError($message);
		}

		return $this->user();
	}

	public function requireAdminJson($message = '') {
		$user = $this->requireAuthorizedJson();

		if (!$user->isAdmin()) {
			if ($message === '') {
				$message = "Ошибка аутентификации. Пользователь ".$user->getName()." не имеет прав администратора";
			}

			$this->jsonError($message);
		}

		return $user;
	}

	public function requireTagAccessJson($message = '') {
		$user = $this->requireAuthorizedJson();

		if (!$user->allowedToTag()) {
			if ($message === '') {
				$message = "Ошибка аутентификации при работе с тегами. Пользователь ".$user->getName()." не имеет прав на добавление тегов";
			}

			$this->jsonError($message);
		}

		return $user;
	}

	public function requireUploadJson($message = 'Unauthorized upload.') {
		$user = $this->requireAuthorizedJson();

		if (!$user->allowedToUpload()) {
			$this->jsonError($message);
		}

		return $user;
	}

	public function jsonError($message, $statusCode = 403) {
		http_response_code($statusCode);
		$log = new Logger($message, true);
		echo json_encode(array('error' => $message), JSON_UNESCAPED_UNICODE);
		exit;
	}
}
