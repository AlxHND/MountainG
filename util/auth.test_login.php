<?php

header('Content-type: application/json; charset=utf-8');

require_once ("_auth.php");

$viewer = $auth->user();

if (!$viewer->isAuthorized() || !$viewer->isAdmin()) {
	echo json_encode(array(
		'error' => 'Тест входа доступен только авторизованному администратору.'
	), JSON_UNESCAPED_UNICODE);
	exit;
}

$login = isset($_POST['login']) ? $_POST['login'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (trim((string)$login) === '' || (string)$password === '') {
	echo json_encode(array(
		'error' => 'Нужно указать логин и пароль.'
	), JSON_UNESCAPED_UNICODE);
	exit;
}

$result = $viewer->debugLoginAttempt($login, $password);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
