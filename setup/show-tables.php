<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);


use App\Helpers\DB;

$db = DB::getInstance();


$sql[] = "SHOW CREATE TABLE sites;";
$sql[] = "SHOW CREATE TABLE galleries;";
$sql[] = "SHOW CREATE TABLE paysites;";
$sql[] = "SHOW CREATE TABLE model;";
$sql[] = "SHOW CREATE TABLE galleries_models;";
$sql[] = "SHOW CREATE TABLE model_names;";
$sql[] = "SHOW CREATE TABLE processing_galleries;";

$sql[] = "SELECT model_id 
FROM model_names 
WHERE model_id NOT IN (SELECT id_model FROM model);";


$sql[] = "SELECT gallery_id 
FROM galleries_models 
WHERE gallery_id NOT IN (SELECT gal_id FROM galleries);";

$sql[] = "SELECT gallery_id 
FROM galleries_models 
WHERE model_id NOT IN (SELECT id_model FROM model);";

try {
	$db = DB::getInstance();
	foreach($sql as $sql_q) {
		$x = $db->query($sql_q)->fetchAll();

        print_r($x);

        
	}
} catch(Exception $e) {
    echo $sql_q;
	echo "Ошибка: ". $e->getMessage();
}
