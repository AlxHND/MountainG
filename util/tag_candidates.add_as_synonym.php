<?php
header('Content-type: application/json');

require_once("../config/config.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.tags.php");
require_once("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при добавлении синонима. Нужны права администратора.');

if (isset($_POST['candidate_id'], $_POST['main_tag_id'])) {
	$candidate_id = (int)$_POST['candidate_id'];
	$main_tag_id = $_POST['main_tag_id'];

	$tags_worker = new Tags;
	if ($tags_worker->addCandidateAsSynonym($candidate_id, $main_tag_id)) {
		$string = json_encode(
			array(
				'success' => $candidate_id,
				'tag_id' => $main_tag_id
			)
		);
	} else {
		$string = json_encode(
			array(
				'error' => 'Candidate #' . $candidate_id . ' was not added as tag\'s synonym: #' . $main_tag_id
			)
		);
	}
} else {
	$string = json_encode(
		array(
			'error' => 'Wrong POST'
		)
	);
}

echo $string;
