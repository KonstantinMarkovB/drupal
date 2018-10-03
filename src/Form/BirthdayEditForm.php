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
class BirthdayEditForm extends FormBase {

  protected $repository;  

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'birthday_edit_form';
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('birthday.repository'));
    return $form;
  }
  
  public function __construct(BirthdayStorage $repository) {  
	$this->repository = $repository;
  }
  /**
   * Sample UI to update a record.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	$form = array (
	  '#prefix' => '<div id="updateform">',
	  '#suffix' => '</div>',
	);
	
	$form['title'] = array (
	  '#type' => 'item',
      '#title' => t('Редактирование именинников'),
	);	    
	
	foreach ($entries_class = $this->repository->sortedLoad() as $entry) {
      $entries[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }
	
    if (empty($entries)) {
      $form['no_values'] = [
        '#markup' => t('Таблица имкнинников пуста.'),
      ];
      return $form;
    }
	
    $keyed_entries = [];
    foreach ($entries as $entry) {
		if(StudentsTool::isLecturer($entry)){
			$options[$entry['pid'] . ''] = t('@surname @name @patronymic @birthday', [
				'@pid' => $entry['pid'],
				'@name' => $entry['name'],
				'@surname' => $entry['surname'],
				'@patronymic' => $entry['patronymic'],
				'@description' => $empty['description'],
				'@birthday' => $entry['birthday'],
			]);
		} else {
			$options[$entry['pid'] . ''] = t('Группа @group_number: @surname @name @patronymic @birthday', [
				'@pid' => $entry['pid'],
				'@name' => $entry['name'],
				'@surname' => $entry['surname'],
				'@patronymic' => $entry['patronymic'],
				'@description' => $empty['description'],
				'@group_number' => $entry['group_number'],
				'@birthday' => $entry['birthday'],
			]);
		}
	 
      $keyed_entries[$entry['pid'].''] = $entry;
    }

	
    // Grab the pid.
    $pid = $form_state->getValue('pid');
	
    // Use the pid to set the default entry for updating.
    $default_entry = !empty($pid) ? $keyed_entries[$pid] : $entries[0];

    // Save the entries into the $form_state. We do this so the AJAX callback
    // doesn't need to repeat the query.
    $form_state->setValue('entries', $keyed_entries);
	
    $form['pid'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Выберите запись для редактирования'),
      '#default_value' => $default_entry['pid'],
      '#ajax' => [
        'wrapper' => 'updateform',
        'callback' => [$this, 'updateCallback'],
      ],
    ];

	$form['group_number'] = [
      '#type' => 'textfield',
      '#title' => t('Группа'),
      '#size' => 15,
      '#default_value' => $default_entry['group_number'],
    ];
	
	$form['surname'] = [
      '#type' => 'textfield',
      '#title' => t('Фамилия'),
      '#size' => 15,
      '#default_value' => $default_entry['surname'],
    ];
	
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Имя'),
      '#size' => 15,
      '#default_value' => $default_entry['name'],
    ];
	
	$form['patronymic'] = [
      '#type' => 'textfield',
      '#title' => t('Отчество'),
      '#size' => 15,
      '#default_value' => $default_entry['patronymic'],
    ];
	
	$form['description'] = [
      '#type' => 'textfield',
      '#title' => t('Описание'),
      '#size' => 50,
      '#default_value' => $default_entry['description'],
    ];
	
	$form['birthday'] = [
      '#type' => 'textfield',
      '#title' => t('Дата рождения'),
      '#size' => 15,
      '#default_value' => $default_entry['birthday'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
    ];
	
	$form['edit_group'] = array (
	  '#type' => 'fieldset',
      '#title' => $this->t('Перевести студентов на другой курс'),
	);
	$form['edit_group']['action'] = array (
	  '#type' => 'submit',
      '#value' => t('Перевести на следующий'),
	  '#submit' => array('::nextCours'),
	);
	$form['edit_group']['action2'] = array (
	  '#type' => 'submit',
      '#value' => t('Перевести на предыдущий курс'),
	  '#submit' => array('::prevCours'),
	);
	
	$form['#attached']['library'][] = 'birthday/birthday.edit';
    return $form;
  }

  public function nextCours(array $form, FormStateInterface $form_state) {
	$result = $this->repository->nextCours();
	if($result){
      drupal_set_message(t('Все студенты переведены на следующий курс.'),'status');
	} else {
	   drupal_set_message(t('Ошибка при редактировании.'),'error');
	}
  }
  
  public function prevCours(array $form, FormStateInterface $form_state) {
	$result = $this->repository->prevCours();
	if($result){
      drupal_set_message(t('Все студенты переведены на предыдущий курс.'),'status');
	} else {
	   drupal_set_message(t('Ошибка при редактировании.'),'error');
	}
  }
  
  /**
   * AJAX callback handler for the pid select.
   *
   * When the pid changes, populates the defaults from the database in the form.
   */
  public function updateCallback(array $form, FormStateInterface $form_state) {
    // Gather the DB results from $form_state.
    $entries = $form_state->getValue('entries');
    // Use the specific entry for this $form_state.
    $entry = $entries[$form_state->getValue('pid')];
    // Setting the #value of items is the only way I was able to figure out
    // to get replaced defaults on these items. #default_value will not do it
    // and shouldn't.
	//$form['edit_group']= '';
	//$form['title']= '';
	
    foreach (['name', 'surname', 'patronymic', 'group_number', 'description', 'birthday'] as $item) {
      $form[$item]['#value'] = $entry[$item];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	if (empty($form_state->getValue('group_number'))) {
     // $form_state->setErrorByName('group_number', t('Введите группу'));
    }
    if (!intval($form_state->getValue('group_number'))) {
      //$form_state->setErrorByName('group_number', t('Горуппа должна быть числом.'));
      
	}
	
	if (empty($form_state->getValue('surname'))) {
      $form_state->setErrorByName('surname', t('Введите Фамилию.'));
    }
	if (empty($form_state->getValue('name'))) {
      $form_state->setErrorByName('name', t('Введите Имя.'));
    }
	if (empty($form_state->getValue('patronymic'))) {
      //$form_state->setErrorByName('patronymic', t('Введите Отчество.'));
    }
	if (empty($form_state->getValue('birthday'))) {
      $form_state->setErrorByName('birthday', t('Введите дату рождения.'));
    } else {
	  $birthday = explode('-',$form_state->getValue('birthday'));
      if(count($birthday)!=3 || !is_numeric($birthday[0])
	    || !is_numeric($birthday[1]) || !is_numeric($birthday[2]))
		{
		  $form_state->setErrorByName('birthday', t("
		    Неверный формат даты. Дата должна быть в формате: год-месяц-день (например 1999-02-03).
		  "));	
		}	
	}

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the submitted entry.
    $entry = [
      'pid' => $form_state->getValue('pid'),
      'name' => $form_state->getValue('name'),
      'surname' => $form_state->getValue('surname'),
	  'patronymic' => $form_state->getValue('patronymic'),
	  'description' => $form_state->getValue('description'),
	  'group_number' => $form_state->getValue('group_number'),
	  'birthday' => $form_state->getValue('birthday'),
    ];
	
	if(empty($entry['group_number']))
		$entry['group_number'] = null;
	
	if(empty($entry['description']))
		$entry['description'] = null;
	
    $results = $this->repository->update($entry);
	  
	if($results){
	   drupal_set_message(
	     $this->t(
		   $entry['surname'] . ' '
           . $entry['name'] . ' '
           . $entry['patronymic'] . ' ' 
           . $entry['birthday'] . ' ' 
           . '(Группа ' . $entry['group_number'] . ')' 
		   . '(' . $entry['description'] . ')'
           . " - Запись обновлена."		 
	     ),
	   'status');
	}else{
	  drupal_set_message(t('Ошибка при редактировании.'),'error');
	}
  }

}
