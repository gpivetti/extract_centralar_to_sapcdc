<?php
set_time_limit(0);

class Database{

  private $dbh;
  private $error;

  public function __construct(){
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

    $options = array(
      PDO::ATTR_PERSISTENT    => false,
      PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    );

    try{
      $this->dbh = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    catch(PDOException $e){
      $this->error = $e->getMessage();
    }
    
    return $this->dbh;
  }

  public function query($query){
    $this->stmt = $this->dbh->prepare($query);
  }

  public function bind($param, $value, $type = null){
    if(is_null($type)){
      switch(true){
        case is_int($value):
          $type = PDO::PARAM_INT;
          break;
        case is_bool($value):
          $type = PDO::PARAM_BOOL;
          break;
        case is_null($value):
          $type = PDO::PARAM_NULL;
          break;
        default:
          $type = PDO::PARAM_STR;
      }
    }
    $this->stmt->bindValue($param, $value, $type);
  }

  public function execute(){
    try {
      $execution = $this->stmt->execute();
      $this->error = '';
      return $execution;
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  public function multiple(){
    $this->execute();
    return $this->stmt->fetchAll(PDO::FETCH_OBJ);
  }

  public function single(){
    $this->execute();
    return $this->stmt->fetch(PDO::FETCH_OBJ);
  }

  public function error(){
    if (!empty($this->error)) {
      return $this->error;
    } else {
      $implodeStr = implode(' ',$this->stmt->errorInfo());
      if (trim($implodeStr) != '00000') {
        return $implodeStr;
      }
    }
    return '';
  }

  public function rowCount(){
    return $this->stmt->rowCount();
  }

  public function lastInsertId(){
    return $this->dbh->lastInsertId();
  }

  public function beginTransaction(){
    $this->dbh->beginTransaction();
  }
  
  public function commit(){
    $this->dbh->commit();
  }

  public function rollBack(){
    $this->dbh->rollBack();
  }
}
?>
