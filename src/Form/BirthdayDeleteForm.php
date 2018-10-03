<?php

namespace Drupal\birthday\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\birthday\BirthdayStorage;
/**
 * Form to add a database entry, with all the interesting fields.
 *
 * @ingroup birthday
 */
class BirthdayDeleteForm extends FormBase {
	
  protected $repository;
 
  public function getFormId() {
    return 'birthday_delete_form';
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

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['group_delete'] = array (
      '#type' => 'fieldset',
	  '#title' => 'Удалить студентов с номером группы ',
    );
	
	$form['delete_list'] = array (
      '#type' => 'fieldset',
	  '#title' =>'Удалить несколько записей',
    );
	
	$form['group_delete']['group_number'] = [
      '#type' => 'textfield',
      '#title' => t('Группа'),
      '#size' => 15,
      '#default_value' => '0',
    ];
	
	$form['group_delete']['actions']['delete_group'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Удалить группу'),
	  '#submit' => array('::deleteGroup'),
	  '#validat' => array('::deleteGroupValidate'),
    );
  
	$header = array(
	  //'pid' => t('Индекс'),
	  'group_number' => t('Группа'),
	  'surname' => t('Фамилия'),
      'name' => t('Имя'),
      'patronymic' => t('Отчество'),
	  'description' => t('Описание'),
	  'birthday' => t('Дата рождения'),
	);
  
	$rows = [];
	
	
    foreach ($entries = $this->repository->sortedLoad() as $entry) {
      $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }
	
	$options = array();

	foreach($rows as $nid){
	  $options[intval($nid['pid'] . '')] = [
	     //'pid' => $nid['pid'],
	     'name' => $nid['name'],
	     'surname' => $nid['surname'],
	     'patronymic' => $nid['patronymic'],
	     'group_number' => $nid['group_number'],
		 'description' => $nid['description'],
	     'birthday' => substr ($nid['birthday'],5),
	   ];
	}

	$form['delete_list']['table'] = array (
	  '#type' => 'tableselect',
	  '#header' => $header,
	  '#options' => $options,
	  '#js_select' => FALSE,
      '#empty' => t('No content available.'),
	);	
	
	
    $form['delete_list']['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Удалить'),
    );
   
	
	$form['#attached']['library'][] = 'birthday/birthday.edit';
	
	return $form;
  }
  
  // дествие кнопки 'Улалить': $form['delete_list']['actions']['delete']
  public function submitForm(array &$form, FormStateInterface $form_state) {
	
    //получаем pid студентов для удаления	
	$delete = array_filter($form_state->getValue('table'));
	$count = 0;
	
	// удаляем студентов
	foreach($delete as $pid){
	  $return = $this->repository->delete(array('pid' => $pid));
	  if($return){
	    $count++;
	  }
	}
	
	drupal_set_message(t("Удалено записей: $count"),'status');
  }

  public function deleteGroupValidate(array &$form, FormStateInterface $form_state) {
	$group_number = $form_state->getValue('group_number');
	if($group_number == '0'|| empty($group_number))
		$form_state->setErrorByName('group_number', t('Введите номером группы.'));
	if(!is_numeric($group_number) || $group_number < 0)
		$form_state->setErrorByName('group_number', t('Неверный формат группы.'));
  }

  function deleteGroup(array &$form, FormStateInterface $form_state) {
	$group_number = $form_state->getValue('group_number');
	
	$return = $this->repository->deleteGroup($group_number);

	if($return){
	  drupal_set_message(t("Все студенты с номером группы $group_number удалены. Всего: $return"),'status');
	} else {
	  drupal_set_message(t("Не удалось удалить 
	  студентов с номером группы $group_number.<br>
	  Возможно, студентов с таким номером группы не существует."
	  ),'error');
	}
  }
  
  
}
  
