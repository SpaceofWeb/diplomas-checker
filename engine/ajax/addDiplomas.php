<?php

require_once '../data/db.php';
require_once '../data/functions.php';
require_once '../data/docx2text.php';


if (isset($_SESSION['token']) && (int)$_SESSION['token'] > time())
	die(json_encode(['err'=> 'Токен не доступен, подождите '.((int)$_SESSION['token']-time()).' секунды']));

$_SESSION['token'] = time()+3;





// Check for file, student and year
if (!isset($_FILES['file'])) {
	die(json_encode(['err'=> 'Параметр "file" не найден']));
}

if ($_FILES['file']['error']) {
	die(json_encode(['err'=> 'Параметр "file" не найден']));
}


if (!isset($_POST['student'])) {
	die(json_encode(
		['err'=> 'Параметр "student" не найден']
	));
}


if (!isset($_POST['year'])) {
	die(json_encode(
		['err'=> 'Параметр "year" не найден']
	));
}


$year = checkData($_POST['year'], 'year');
$student = checkData($_POST['student'], 'student');

if ($_POST['student'] == 0) {
	die(json_encode(['err'=> 'Параметр "student" не валиден']));
}



// Check if diploma exists in db
$q = "SELECT id FROM {$cfg['dbprefix']}_diplomas WHERE student_id='{$student}' ";
$res = $db->query($q);

if ($res->num_rows > 0) {
	die(json_encode(['err'=> 'В базе уже есть дипломная работа этого студента. Что бы сохранить новую удалите старую']));
}




// Save diploma
$file = 'diplomas/'.$year.'/';

if (!file_exists($cfg['uploadDir'].$file)) mkdir($cfg['uploadDir'].$file, 0777, true);


$addDate = mktime();
$file .= 'id'.$student.'-'.$addDate.'.docx';


if (!move_uploaded_file($_FILES['file']['tmp_name'], $cfg['uploadDir'].$file)) {
	die(json_encode(['err'=> 'Не удалось сохранить файл']));
}


// Parse file
$d2t = new DocumentParser();
$text = $d2t->parseFromFile($cfg['uploadDir'].$file, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

$text = escapeString($text, $db, ['trim', 'stripTags']);


// Add to db
$q = "INSERT INTO {$cfg['dbprefix']}_diplomas (text, year, addDate, file, student_id) 
		VALUES('{$text}', '{$year}', '{$addDate}', '{$file}', '{$student}')";


if (!$db->query($q))
	die(json_encode(['err'=> 'Не удалось сохранить дипломную студента в базе: ' . $db->error]));



die(json_encode(['err'=> 0]));












