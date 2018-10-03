<?php

namespace Drupal\birthday;

class StudentsTool {

  public function loadStudentsFromFile($uri) {
	
    $error_message = '';	
	  
    try {  
	
	  // определяем кодировку файла
	  $str = file_get_contents($uri);
	  $isUTF8 = mb_detect_encoding($str, 'UTF-8', true);
	  
	  // открываем файл на чтение
	  $content = fopen($uri, 'r');	
	
	  if($content){
		// для подсчета новера строки, которая в данный момент обрабатывается
		$line = 1;
		
		
	    while (!feof($content)){
		  // построчно считываем файл
          $line_str = fgets($content);
		  //разбиваем строку на массив стров 
  		  $student_str = explode(';',$line_str);
		
		  // если кодировка файла не UTF-8 то строки, содержащие имя, фамилию и отчество
		  // конвентируем в UTF-8
		  if(!$isUTF8){
		    $student_str[0] = mb_convert_encoding($student_str[0],'utf-8', 'windows-1251');
		    $student_str[1] = mb_convert_encoding($student_str[1],'utf-8', 'windows-1251');
		    $student_str[2] = mb_convert_encoding($student_str[2],'utf-8', 'windows-1251');
			$student_str[4] = mb_convert_encoding($student_str[4],'utf-8', 'windows-1251');
		  }
		  
		  // заполняем массив студентов попутно удаляя 
		  // пробелы
		  $entries[$line ] = [
            'name' => preg_replace('/\s+/', '', $student_str[1]),
            'surname' => preg_replace('/\s+/', '', $student_str[0]),
	        'patronymic' => preg_replace('/\s+/', '', $student_str[2]),
	        'group_number' => preg_replace('/\s+/', '', $student_str[3]),
			'description' => $student_str[4],
	        'birthday' => preg_replace('/\s+/', '', $student_str[5]),
          ];
		  
		  //Если есть ошибка в фамилии в первой строке, то без этого
		  // она не обнаруживается
		  //if($line == 1){
		    //$entries[$line ]['surname'] = substr($entries[$line ]['surname'], 3);
		  //}
		  
		  // проверка строк на пустоту и прочее
		  if(empty($entries[$line ]['name'])) {
		    $error_message.= "Строка $line: Отсутствует имя.<br>";
		  }
		  if(empty($entries[$line ]['surname'])){
		    $error_message.= "Строка $line: Отсутствует Фамилия.<br>";
		  }
		  if(empty($entries[$line ]['patronymic'])){
		    $entries[$line ]['patronymic'] = NULL;
		  }
		  
		  if(!empty($entries[$line ]['group_number']) ){
		    if(!is_numeric($entries[$line ]['group_number'])){
				$error_message.= "Строка $line: Некоректный Номер группы.<br>";
			}
		  } else $entries[$line ]['group_number'] = null;
		  
		   if(empty($entries[$line ]['description']) )
			   $entries[$line ]['description'] = NULL;
		  
		  if(empty($entries[$line ]['birthday'])){
		    $error_message.= "Строка $line: Отсутствует Дата рождения.<br>";
		  }else {
		    $birthday = explode('-',$entries[$line]['birthday']);
		    $birthday_is_numeric = is_numeric($birthday[0]) && 
			                       is_numeric($birthday[1]) && 
								   is_numeric($birthday[2]);
           
		   if(count($birthday)!=3 || !$birthday_is_numeric)
			{
			  $error_message.= "Строка $line: Неверный формат даты: " 
			  . $entries[$line ]['birthday'] . "<br>";
			}
		  }
		  
		  $line++;
        }
		
	  } else {
		return array(
		  'return' => 0, 
		  'message' => t('Ошибка открытия файла')
		);
	  }
    
	  fclose($content);
	}
    catch (\Exception $e) {
	  return array(
	    'return' => 0, 
		'message' => $e->getMessage()
	  );
    }
	
	$return = empty($error_message);
	
	return array(
	  'return' => $return,
	  'message' => $error_message,
	  'entries' => $entries,
	);
  }
  
  public function toString($entry){
    return $entry['surname'] . ' ' . 
	    $entry['name'] . ' ' .
		$entry['patronymic'] . ' ' . 
		'(' . $entry['group_number'] . ') ' .
		'( ' . $entry['description'] . ') ' .
		$entry['birthday'];
  }	  
  
  public function isLecturer($person){
    
	$group_number =  intval(strval($person['group_number']));
	
	$isLecturer = strlen($person['group_number']) == 0 || 
		  $group_number == 0;
    
	return $isLecturer;
  }
 
}
