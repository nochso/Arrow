## Dummy ORM

# Install
Install via composer 
```bash
$ composer require ... 
```

... 

# Usage
namespace App\Model; 
use SomeNamespace\Dummy; 
class User extends Dummy{
    # If you want, you can use complex queries not 
    # provided in the Dummy class. 
    public function WeirdQuery(){}
}




<?php

class ORM {
    protected $columns = []; 
    protected $tables  = []; 
    protected $database; # current db extracted from PDO
    protected $table; # find parent_class from $tablesList
    protected $lastInsertId; # after INSERT keep the id 
    protected $autoEscape = false; # done :)


   // $user = $orm->tableUser()->findByName('john');
   public function __call($name, $arguments) {
       if (strpos($name, 'table') === 0) {
           $tableName = substr($name, 4);   
           $model = new $tableName($this->pdo);
           return $model;
       }
   } 
   
   // $user->name = 'value' 
   public function __set($column, $value) {
      if(!in_array($column, $this->columns)){
          throw new \Exception(sprintf('Column %s does not exist in table %s.', $column, $this->table));
      }
      $this->columns[$column] = $value;
   }
   
   // echo $user->name; 
   public function __get($column){
     if(!in_array($column, $this->columns)){
          throw new \Exception(sprintf('Column %s does not exist in table %s.', $column, $this->table));
     }
     
     // by default __get should return the raw values, that's the "least surprise"
     // Then you can turn ita into an "escaped object" ready for use in a raw PHP template
     // e.g. $viewUser = $user->autoEscape(); // gets passed to the pure PHP templates, automatically escaped
     // $user->name == '&lt;script&gt;'
     if ($this->autoEscape == true) { 
         return someHtmlEscape($this->columns[$column]);
     }
     return $this->columns[$column]; 
   } 
   
   
   # just optional method to manually set table name $orm->table('foo');
   # '$this' for echo $user->table('foo')->Some()->query(); one-lined query
   public function table($table){
      $tables = $this->getTables(); 
      if (!in_array($table, $this->tables)) {
         throw new \Exception(sprintf('Table %s does not exist in Datavase %s.', $table, $this->database));
      }
      
      $this->table = $value; 
      # calling table is usually a good time to fill $this->columns
      $this->getColumns(); 
      return $this; 
   }
   
   # Another optional database method $orm->database('foo');
   # '$this' so we can use $user->database('foo')->table('bar');
   public function database($value){
      $this->database = $value; 
      return $this; 
   }
   
   # to initialize columns to the class property, we need to find them
   public function getColumns(){
      $table = $this->table; 
      // TODO: Try to cover at least SQLite and MySQL dialects
      $this->columns = $this->pdo->query(" 
         SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = $table
      ")->fetchAll(PDO::FETCH_COLUMN);
      
       return $this->columns; 
   }
   
   # save()
   public function(){}
   
   # query builder private 
   private function builder(){
   
   }
   
   
   public function __construct($pdo){
      $this->pdo = $pdo;
      $this->table = getTableNameFromFQN(get_class($this)); // Something like that. Is used by Model in ORM2.
   }

   public function getDatabase(){
     return $this->database = $this->pdo->query('select database()')->fetchColumn();
   }

   public function getTables(){
      $stmt = $this->pdo->query("SHOW tables FROM " . $this->database)->fetchAll();
      foreach ($stmt as $table) {
         $this->tables[] = $table['Tables_in_' . $this->database];
      }
      return $this->tables;
   }

}

class User extends ORM {
    public function findByName($name) {
        return $this->where('name', $name)->all();
    }
}


$users = new User;
$users = $users->equals('status', 'confirmed')->fetchAll();
foreach ($users as $user) {
    if (checkWhatever($user['email'])) {
        $user['status'] = 5;
        $users->where('id', $user['id'])->update($user);
    }
}

# Intializing 
$blogs = new Blogs; 

# READ / SELECT
$article = $blogs->findBySlug('why-i-love-php'); # maybe implementable in Blogs class. 
echo $article->title; // Why I Love PHP

# UPDATE (auto)
$article->title= 'PHP SUCKS';
$article->update();
echo $article->title; // PHP SUCKS

# INSERT 
$article->title = 'New Blog'; 
$article->save(); # unset primary key, INSERT using remaining columns, set primary key of fresh row

# DELETE
$article->delete(); # DELETE * FROM Blogs (dangerous)

# Ranged DELETE
$article->where('mail', 'foo@test.com')->deleteAll();

; // works because $this->columns['id'] exisfn d,
// so the last query will be 'UPDATE table SET name = jane where id = 42'