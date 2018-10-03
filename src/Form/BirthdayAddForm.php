<?php

namespace Drupal\birthday\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\birthday\BirthdayStorage;
use Drupal\birthday\StudentsTool;

/**
 * Form to add a database entry, with all the interesting fields.
 *
 * @ingroup birthday
 */
class BirthdayAddForm extends FormBase{

  protected $repository;

  public function getFormId() {
    return 'birthday_add_form';
  }
  
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('birthday.repository'));
    return $form;
  }
  
  public function __construct(BirthdayStorage $repository) {
   $this->repository = $repository;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    
	$form['title'] = array (
	  '#type' => 'item',
      '#title' => t('Добавление именинников'),
	  '#markup' => t(
	  '<br>Студенты, чьи имя, фамилия, отчество и дата рождения совпадают с уже существующими студентами, будут заменены новыми.<br>'
	  . "Нажмите 'Подробности' для получения информации"),
	);	
	
    // Details.
    $form['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Details'),
      '#description' => $this->t(
	    "Для добавления именинников необходимо у cебя на компьютере
		создать текстовый файл и записать именинников в него.
	    Файл можно создать в любом текстовом редакторе, 
		например Блокнот или Notepad++.<br>
		<br>
		
		Обратите внимание:<br>
		
		1. файл должен иметь расширение '.txt';<br>
		2. содержать в названии только цифры и латинские 
		буквы (например 'myFile.txt' или 'hello1.txt');<br>
		
		<br>
		
		В файле должны находиться строки следующего вида:<br>
		<br>
		
		Фамилия;Имя;Отчесто;Номер_группы;Описание;Дата_рождения<br>
		<br>
		Номер группы записывается, если это студент. 
		Если это преподаватель - ничего не пишется<br>
		По этому полю определяется, студент это или преподаватель.<br>
		
		<br>
		Описание:<br>
		- если это преподовать, то записывается его должность и кафедра;<br>
		- если это студент, то записывается его страна либо оставляется пустым.<br>	
		
		<br>
		Дата_рождения должна быть записана в виде: год-месяц-день 
		(например 1998-01-05 или 1998-3-9)<br>
		Вместо действительного года рождения можно вписать 0000.
		<br>
		
		Пример:<br>
		
		Иванов;Иван;Иванович;10;;0000-01-01<br>
		Петров;Петр;Петрович;11;Республика Беларусь;2000-01-01<br>
		Иванов;Петр;Иванович;;Преподаватель кафедры геометрии и математического анализа;0000-02-02<br>
		
		<br>
		Для добавления именинников ниже нажмите кнопку 'Выбрать файл'  ('Choose File') и выберите нужный файл. Нажмите кнопку 'Добавить'.
		<br>
		<br>
		Возможные ошибки:<br>
		1. Вместо русских букв отображаются непонятные символы.<br>
		Решение: Измените кодировку файла на UTF-8."
	  ),
    ];  
	
	$validators = array(
      'file_validate_extensions' => array('txt'),
    );
  
    $form['file_students'] = array(
      '#type' => 'managed_file',
      '#title' => t('Файл'),
      '#size' => 20,
      '#description' => t('Выберите файл с расширением .txt'),
      '#upload_validators' => $validators,
	  '#required' => TRUE,
	  '#upload_location' => 'public://my_files/',
    );
	
    $form['actions']['add_list'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Добавить'),
      '#button_type' => 'primary',
    );
	
    return $form;
  }
      
  public function validateForm(array &$form, FormStateInterface $form_state) {
	// проверка на то, что пользователь выбрал какой-то файл
    $managedFile = $form_state->getValue('file_students');
	if(empty($managedFile))
		$form_state->setErrorByName('file_students', t('Выберите файл.'));
   
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
	
	$error_message = '';
	
	// получаем выбранный пользователем файл
    $file = \Drupal::entityTypeManager()->getStorage('file')
                    ->load($form_state->getValue('file_students')[0]); 
					
    $uri = $file->getFileUri();
	
	$students = StudentsTool::loadStudentsFromFile($uri);
    
	if(!$students['return']){
		
	  drupal_set_message(t(
	    "При добавлении возникли ошибки. 
		Ни одна запись не была добавлена.<br>"
		. $students['message']
	    ),'error');
		
      return;	  
	}	
	
	/* 
	  добавляем полученный список студентов в базу данных
	*/
	
	$warning_message = '';
	$error_message = '';
	$status_message = '';
	
	$count = 0;

	foreach ($students['entries'] as $student) {
		
	  $studentStr = StudentsTool::toString($student);
    
	  $studentExist = false;
	  
	  //Проверка, существует ли студент с такими данными(Фамилия имя отчество)
	  //Если существует - обновляем его группу, описание и дату рождения
	  foreach ($sameStudents = $this->repository->get($student) as $sameStudent_) {
		
		$sameStudent = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $sameStudent_);
	    //$student['pid'] = $sameStudent['pid'];
		
		$updateStudent = $student;
		$updateStudent['pid'] = $sameStudent['pid'];
		
		$updateResult = $this->repository->update($updateStudent);
		
		if($updateResult){
		  $warning_message.= $studentStr . '. Запись была обновлена.<br>'; 
		} else {
		  $warning_message.= $studentStr . '. Ошибка обновления записи.<br>'; 
		}
		
		$studentExist = true;
		
	    break;
		
      }	  
	  
	  // Если студента с такими данными не существует,
	  // добавляем новую запись
	  
	  if(!$studentExist){
        
        $return = $this->repository->insert($student);
		  
		if($return){
		  $status_message.= $studentStr. '<br>';	
	      $count++;
		} else { 
		  $error_message.= 'Не удалось добавить запись: ' . $studentStr. '<br>';
		}
		
	  }
	  
	  
	   
    }
	
	// выводим сообщения об ошибке
	if(!empty($error_message)){
		drupal_set_message(t($error_message),'error');
	}
	
	// Выводим предупреждения
	if(!empty($warning_message)){
		$warning_message = "Следующие записи были обновлены:<br>" . $warning_message;
		drupal_set_message(t($warning_message),'warning');
	}
	
	drupal_set_message(t(
	  "Успешно добавлено записей: $count<br>" . 
	  $status_message
	  ),'status');
	
  }
  
  
  
}
