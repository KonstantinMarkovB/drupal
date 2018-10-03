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
class GroupDescriptionEditForm extends FormBase {

  protected $repository;  

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_description_edit_form';
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
      '#title' => t('Редактирование групп'),
	);	    
	
	foreach ($entries_class = $this->repository->groupLoad() as $entry) {
      $entries[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }
	
    if (empty($entries)) {
      $form['no_values'] = [
        '#markup' => t('Таблица описания групп.'),
      ];
      return $form;
    }
	
    $keyed_entries = [];
    foreach ($entries as $entry) {
      $options[$entry['group_number'] . ''] = t('Группа @group_number: @description', [
		'@group_number' => $entry['group_number'],
		'@description' => $entry['description'],
		
      ]);
	 
      $keyed_entries[$entry['group_number'].''] = $entry;
    }

	
    // Grab the pid.
    $group_number = $form_state->getValue('group_number');
	
    // Use the pid to set the default entry for updating.
    $default_entry = !empty($group_number) ? $keyed_entries[$group_number] : $entries[0];

    // Save the entries into the $form_state. We do this so the AJAX callback
    // doesn't need to repeat the query.
    $form_state->setValue('entries', $keyed_entries);
	
    $form['group_number'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Выберите группу для редактирования'),
      '#default_value' => $default_entry['group_number'],
      '#ajax' => [
        'wrapper' => 'updateform',
        'callback' => [$this, 'updateCallback'],
      ],
    ];
	
	$form['description'] = [
      '#type' => 'textarea',
      '#title' => t('Название специальности'),
      '#size' => 100,
      '#default_value' => $default_entry['description'],
    ];
	
	 $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
    ];
	
	$form['#attached']['library'][] = 'birthday/birthday.edit';
    return $form;
  }

  public function updateCallback(array $form, FormStateInterface $form_state) {

    $entries = $form_state->getValue('entries');
    $entry = $entries[$form_state->getValue('group_number')];

	
    foreach (['description',] as $item) {
      $form[$item]['#value'] = $entry[$item];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //$s = $form_state->getValue('group_number');
	
	//if ($s!==(string)intval($s)) {
     // $form_state->setErrorByName('group_number', t('Горуппа должна быть числом.'));
      
	//}
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the submitted entry.
    $entry = [
	  'description' => $form_state->getValue('description'),
	  'group_number' => $form_state->getValue('group_number'),
    ];
	if(empty($entry['description']))
		$entry['description'] = null;
	
    $results = $this->repository->updateGroup($entry);
	  
	if($results){
	   drupal_set_message(
	     $this->t(
			'Группа ' . $entry['group_number'] . ': ' 
		   . $entry['description'] 
           . " - Запись обновлена."		 
	     ),
	   'status');
	}else{
	  drupal_set_message(t('Ошибка при редактировании.'),'error');
	}
  }

}
