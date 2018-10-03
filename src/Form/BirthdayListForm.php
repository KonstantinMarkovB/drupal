<?php

namespace Drupal\birthday\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


use Drupal\birthday\BirthdayStorage;
use Drupal\birthday\StudentsTool;

class BirthdayListForm extends FormBase {

  protected $repository;  
  protected $group_description;
  
  protected $_monthsList = array(
	  "01" => "января", "02" => "февраля", "03" => "марта", 
	  "04" => "апреля", "05" => "мая", "06" => "июня", 
	  "07" => "июля", "08" => "августа", "09" => "сентября",
	  "10" => "октября", "11" => "ноября", "12" => "декабря"
	);

  public function getFormId() {
    return 'birthday_list_form';
  }

  public static function create(ContainerInterface $container) {
    $form = new static($container->get('birthday.repository'));
    return $form;
  }
  
  public function __construct(BirthdayStorage $repository) {
	$this->repository = $repository;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
  \Drupal::service('page_cache_kill_switch')->trigger();
	$form = array (
	  '#prefix' => '<div id="updateform">',
	  '#suffix' => '</div>',
	);
	
	$mont_number = date('m');
	
	$mont_name = mb_strtoupper($this->_monthsList[date('m')]);
	
	$form['search']['search_txt'] = array(
      '#type' => 'search',
	  '#attributes' => array(
	    'class' => array('search_text_field'),
	  ),
      '#placeholder' => $this->t('Введите фамилию...'),
    );

    $form['search'][] = array(
      '#type' => 'submit',
      '#value' => $this->t('Найти'),
	  '#attributes' => array(
	    'class' => array('search_btn_submit'),
	  ),
	  '#ajax' => [
        'wrapper' => 'updateform',
        'callback' => [$this, 'findPerson'],
      ],
    );
	
	$form['search_result'] = [
	  '#prefix' => "<div class='search_result'>",
	  '#suffix' => '</div>',
    ];
	
    $form['message'] = [
      '#markup' => $this->t('<h1>Именинники месяца:</h1>'),
    ];

    $rows = [];
	
    foreach ($entries = $this->repository->loadByMonth($mont_number) as $entry) {
      $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }     
	
	$tables = [];
	
	foreach($rows as $person){
		
	  $birthday = explode('-',$person['birthday']);
	  
	  $day = intval(strval($birthday[2]));
	  
	  $tables[$day][] = $person;
	}
	
	$rows = [];
	
    foreach ($entries = $this->repository->loadGroupDescription() as $entry) {
      $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    } 
	
	$this->group_description = [];
	
	foreach($rows as $row){
	  $group_number = intval(strval($row['group_number']));
	  $this->group_description[$group_number] = $row['description'];
	}
	
	for($day = 1; $day < 32; $day++){

	  $persons = $tables[$day];
	  
	  if(empty($persons))
		  continue;
	  
	  $form['table' . $day] = [
        '#type' => 'table',
	    '#caption' => $this->t($day . ' ' . $mont_name),
      ];
	  
	   $form['table' . $day]['#attributes'] = array(
         'class' => array(
           'birthday_table',
         ),
       );
	  
	  $person_list_str = [];
	  
	  foreach ($persons as $person) {
	    
		$group_number =  intval(strval($person['group_number']));
		$group_type = $group_number  % 10;
		
		$mr = "<div class = 'birthday_table_birthday_boy'>";
		
		$mr .=  $person['surname'] . ' ' . $person['name'];
		
		$isLecturer = StudentsTool::isLecturer($person);
		
	    if($isLecturer && strlen($person['patronymic']) > 0){
		  $mr .=  ' ' . $person['patronymic'];
		}
		
		if(!$isLecturer && strlen($person['description']) > 0){
		  $mr .=  ' (' . $person['description'] . ')';
		}
		
		$mr .=  "</div>";
		
		$mr .= "<div class = 'birthday_table_group_number'>";
		
		if($isLecturer && strlen($person['description']) > 0){
		  $mr .= $person['description'];
		}
		
		if(!$isLecturer){
		  $mr .= "Группа $group_number. " . $this->group_description[$group_type];	
		}
		
		$mr .= "</div>";
		
		if($isLecturer){
		  $person_list_str['lecturers'][] = $mr;
		} else {
		  $person_list_str['students'][] = $mr;
		} 
	
	  }
	  
	  foreach ($person_list_str['lecturers'] as $lecturer) {
	    $form['table' . $day][][] = array(
		  '#markup' => $lecturer,
		);
	  }
	  
	  foreach ($person_list_str['students'] as $student) {
	    $form['table' . $day][][] = array(
		  '#markup' => $student,
		);
	  }
	  
	}
	
	$form['#attached']['library'][] = 'birthday/birthday.list';

    // Don't cache this page.
    $form['#cache']['max-age'] = 0;
	//$form = ['#cache' => [ 'max-age' => 0, ], ];
	
    return $form;
  }  

  public function findPerson(array $form, FormStateInterface $form_state) {
    $surname = $form_state->getValue('search_txt');
	
	$rows = [];
	
    foreach ($entries = $this->repository->loadPersonWithSurname($surname) as $entry) {
      $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }     
	
	$mr = '';
	
	foreach($rows as $person){
		
	  $group_number =  intval(strval($person['group_number']));	
	  $group_type = $group_number % 10;
	  $isLecturer = StudentsTool::isLecturer($person);	
	  
	  $birthday = explode('-', $person['birthday']);
	  
	  $month = $this->_monthsList[$birthday[1]];
	  $day = intval(strval($birthday[2]));
	  
      $mr .= "<div class='serch_result_name_birthday'>";
	  
	  $mr .= $person['surname'] . ' ' . $person['name'];
	  
	  if( $isLecturer && strlen($person['patronymic']) > 0){
		  $mr .=  ' ' . $person['patronymic'];
	  }
	  
	  if(!$isLecturer && strlen($person['description']) > 0){
		   $mr .= '(' . $person['description'] . ')';
	  }
	  
	  $mr .= ", $day $month";
	  
	  $mr .= '</div>';
	  
	  $mr .= "<div class='serch_result_description'>";
	  
	  
	  
	  if($isLecturer && strlen($person['description']) > 0){	  
	    $mr .= $person['description'];
	  }
	  
	  if(!$isLecturer){
		 $mr .= t('Группа ') . $person['group_number'];
	  }
	  
	  $description = $this->group_description[$group_type];
	  if(!$isLecturer && !empty($description)){
	    $mr .= ', ' . $description;
	  }
	 
	  $mr .= '</div>';  
	 
	}
	
	if($mr == ''){
	  $mr = 'К сожалению ничего не найдено';
	}
	
	$form['search_result']['#markup'] =  $mr;
	
	 return $form;
  }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('search_txt'))) {
      $form_state->setErrorByName('search_txt', t('Для поиска необходимо ввести фамилию'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }
  
  public function isLecturer($person){
    
	$group_number =  intval(strval($person['group_number']));
	
	$isLecturer = strlen($person['group_number']) == 0 || 
		  $group_number == 0;
    
	return $isLecturer;
  }

}
