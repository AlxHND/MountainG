<?php

header('Content-type: application/json');

require_once("../config/config.php");
require_once("../classes/Logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.tags.php");
require_once("../classes/class.models.php");
require_once("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при аппруве слова как актера. Нужны права администратора.');

if (!isset($_POST['candidate_id']) || !isset($_POST['sex'])) {
	echo json_encode(['error' => 'Wrong POST',]);
	die;
}

$candidate_id = (int)$_POST['candidate_id'];
$sex = $_POST['sex'];

$tags_worker = new Tags($db->_db);

if (!$tags_worker->approveCandidateAsModel($candidate_id, $sex)) {
	echo json_encode(['error' => 'Candidate #' . $candidate_id . ' was not added as blacklisted']);
	die;
}

echo json_encode(['success' => $candidate_id]);
