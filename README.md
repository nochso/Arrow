# Arrow
Mix of active record and fluent query builder that is easily extensible.

Supports:
- MySQL / MariaDB
- SQLite
- PostgreSQL
- MS SQL (missing support for OFFSET)

## Setup / connection
Create a new instance and use the `connect()` method that is compatible with `\PDO`'s constructor:
```php
$orm = new Fastpress\Arrow\ORM();
$orm->connect('sqlite:/db.sqlite');
$orm->connect('mysql:host=localhost;dbname=mydb', 'username', 'password');
$orm->connect('mysql:host=localhost;dbname=mydb', 'username', 'password', [\PDO::ATTR_CASE => \PDO::CASE_LOWER]);
```
You have direct access to the `\PDO` connection:
```php
$orm->pdo->beginTransaction();
```
The newest instance of `ORM` is kept via static property `Fastpress\Arrow\ORM::$instance` 

## Models
### Defining your own models
The fastest way to get started is extending `Fastpress\Arrow\FluentModel`:
```php
class User extends Fastpress\Arrow\FluentModel { ... }
```
By default the class name will be converted to lower-case snake_case, e.g. class `UserRole` becomes table name `user_role`. The default primary key name is `id`. However you can override both when extending any Model:
```php
class User extends Fastpress\Arrow\FluentModel {
    public function getTableName() {
        return 'users';
    }
    public function getPrimaryKeyName() {
        return 'id';
    }
}
```

### Fluent interface
You can already access the database using your model and a fluent query interface:
```php
$userQuery = new User();
# Returns a list of `User` objects whose names start with "Jo"
$userList = $userQuery->like('name', 'Jo%')->all();
foreach ($userList as $user) {
    echo $user->name . "\n";
}
```
For more information about the fluent interface, take a look at `Fastpress\Arrow\Builder\FluentBuilderTrait`.

### Active Record
Each model has ActiveRecord style methods to save, update and delete rows:
```php
$user = new User();
$user->name = 'John';
$user->save();   // INSERT new row
echo $user->id;  // Primary key was set by save()

$user->name = 'Jane';
$user->update(); // UPDATE existing row
$user->delete(); // DELETE existing row
```

### Avoiding models
If you choose not to create or use model classes for each table, you can use the DynamicFluentModel:
```php
// Create a model for any table on the fly:
$user = DynamicFluentModel::create('user');

// which is a shortcut for:
$user = new DynamicFluentModel();
$user->withTable('user', 'id', $columns);
// The last 2 parameters default to 'id' and array()

$user->name = 'John';
$user->save();

// Alternatively:
$user = DynamicFluentModel::create('user', 'id', ['name' => 'Sweet Dee']);
$user->save();

$user->name = 'Jane';
$user->update();

$jane = $user->where('id', $user->id)->one();
// $jane->name == 'Jane'
```

### Structure
The base class for models is `Model`. When extending this, all you get is a model definition and the Active Record functionality.

* **Model** - Base table definition including Active Record methods **without** fluent methods.
  * **FluentModel** - Extends directly from Model, pulling in the FluentBuilderTrait for fluent query building.
    * **DynamicFluentModel** - Can be used to create a `FluentModel` on the fly without actually declaring it. See section "Avoiding models" also.
    ```php
    $simpleModel = new User(); # extends directly from Model
    $simpleModel->is_deleted = true;
    $model = new DynamicFluentModel();
    $model->withModel($simpleModel);
    # Updates is_deleted for all rows where name is 'Anonymous'
    $rowCount = $model->where('name', 'Anonymous')->updateAll();
    ```

It is essentially up to you what features the models support.