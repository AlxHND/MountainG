<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Helpers\DB;

$targetUser = 'alexl';
$newPassword = 'CHANGE_ME_NOW_123';

if ($newPassword === '' || $newPassword === 'CHANGE_ME_NOW_123') {
	echo "Открой файл setup/set-user-password-alexl.php и укажи временный пароль в \$newPassword\n";
	exit(1);
}

if (!function_exists('password_hash')) {
	echo "password_hash недоступен в текущем PHP\n";
	exit(1);
}

$db = DB::getInstance();

$selectStmt = $db->prepare("SELECT id, user_name FROM scr_users_list WHERE user_name = :user_name LIMIT 1");
$updateStmt = $db->prepare("UPDATE scr_users_list SET user_pass = :user_pass WHERE id = :id");

if (!$selectStmt || !$updateStmt) {
	echo "Не удалось подготовить SQL statement\n";
	exit(1);
}

$selectStmt->execute(array(':user_name' => $targetUser));
$user = $selectStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
	echo "Пользователь не найден: " . $targetUser . "\n";
	exit(1);
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
if (!is_string($newHash) || $newHash === '') {
	echo "Не удалось создать password_hash\n";
	exit(1);
}

$updateStmt->execute(array(
	':user_pass' => $newHash,
	':id' => (int)$user['id']
));

echo "Пароль обновлен для пользователя " . $targetUser . " (ID " . (int)$user['id'] . ")\n";
echo "Не забудь потом сменить временный пароль на нужный и убрать/очистить этот файл.\n";
