<?php

namespace Drupal\birthday;

use Drupal\Core\Database\Connection;


class BirthdayStorage {

  protected $connection;
  protected $list_db = 'birthday_list_db';
  protected $group_description_db = 'birthday_group_description_db';
  

  public function __construct(Connection $connection) {
    $this->connection = $connection;	
  }

  public function insert(array $entry) {
    $return_value = NULL;
    try {
      $return_value = $this->connection->insert($this->list_db)
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
	  //echo 'error ' . $e->getMessage() . '<br>';
    }
	
    return $return_value;
  }
  
  public function update(array $entry) {
	$count = $this->connection->update($this->list_db)
        ->fields($entry)
        ->condition('pid', $entry['pid'])
        ->execute();

    return $count;
  }
  
    public function updateGroup(array $entry) {
	$count = $this->connection->update($this->group_description_db)
        ->fields($entry)
        ->condition('group_number', $entry['group_number'])
        ->execute();

    return $count;
  }

  public function load(array $entry = []) {
    $select = $this->connection
      ->select($list_db)
      ->fields($list_db);

    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }
    // Return the result in object format.
    return $select->execute()->fetchAll();
  
  /*
	$query = $this->connection->query("
	SELECT 
	    *
	FROM 
		`birthday_db` 
	");
	
    $result = $query->fetchAll(\PDO::FETCH_ASSOC);
	
	return $result;
	*/
  }
  
  public function loadByMonth($mont_number = 1) {	  
    $query = $this->connection->query("
	SELECT *
	FROM 
		`$this->list_db` 
	WHERE 
		DATE_FORMAT(`birthday`, '%m') = $mont_number
	ORDER BY 
	    `birthday`,
	    `surname`
	");
	
    $result = $query->fetchAll(\PDO::FETCH_ASSOC);
	
    return $result;
  }
  
  public function loadGroupDescription($group_number = 0) {	  
  
    $query = $this->connection->select($this->group_description_db, 'n');
	
	$query->fields('n', ['group_number','description']);

	if($group_number != 0){
	  $query->condition('n.group_number', $group_number, '=');
	}

	$result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
	
    return $result;
  }
  
   public function loadPersonWithSurname($surname) {	  
  
    $query = $this->connection->select($this->list_db, 'n');
	
	$query->fields('n');
	
	$query->condition('n.surname', $surname, '=');

	$result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
	
    return $result;
  }
  
    public function get($stutent) {	 
	
	$query = $this->connection->select($this->list_db, 'n');
	$query->fields('n', ['pid','name', 'surname', 'patronymic']);
	
	$query->condition('n.name', $stutent[name], '=');
	$query->condition('n.surname', $stutent[surname], '=');
	if($stutent[patronymic] != null){
		$query->condition('n.patronymic', $stutent[patronymic], '=');
	}
	//$query->condition('n.group_number', $stutent[group_number], '=');
	//$query->condition('n.birthday', $stutent[birthday], '=');
	
	$result = $query->execute();
	
    return $result;
  }
  
  public function getWithPid($pid) {	 
	$query = $this->connection->select($list_db, 'n');
	$query->fields('n', ['name', 'surname', 'patronymic', 'group_number','birthday']);
	
	$query->condition('n.pid', $pid, '=');
	
	$result = $query->execute();
	
    return $result;
  }
  
  public function nextCours() {	 

	return $this->connection->update($this->list_db)
	 // ->condition('group_number', null, '!=')
      ->fields([
        'group_number' => $value1,
      ])
      ->expression('group_number', 'group_number + :inc', [':inc' => 10])
      ->execute();
  }
  
  public function prevCours() {	 

	return $this->connection->update($this->list_db)
      ->fields([
        'group_number' => $value1,
      ])
	   ->condition('group_number',20, '>=')
      ->expression('group_number', 'group_number - :dec', [':dec' => 10])
      ->execute();
  }
  
  public function sortedLoad() {	  	 
	$query = $this->connection->query("
	
	SELECT 
	   *
	FROM 
		`$this->list_db` 
	ORDER BY
		`group_number`, `surname`, `name`, `patronymic`, `birthday`
	");
	
    $result = $query->fetchAll();
	
    return $result;
  }
  
  public function groupLoad() {	  	 
	$query = $this->connection->query("
	
	SELECT 
	    *
	FROM 
		`$this->group_description_db` 
	ORDER BY
		`group_number`
	");
	
    $result = $query->fetchAll();
	
    return $result;
  }
  
  public function delete(array $entry) {
	return $this->connection->delete($this->list_db)
      ->condition('pid', $entry['pid'])
      ->execute();
  }
  
  public function deleteGroup($group_number) {
	return $this->connection->delete($this->list_db)
      ->condition('group_number', $group_number)
      ->execute();
  }



}
