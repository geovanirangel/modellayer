# ModelLayer @geovanirangel

O ModelLayer é um componente que abstrai operações de CRUD (Select, Insert, Update e Delete) no seu banco de dados. Baseado em PDO, projetado para um estruturas MVC (Model-View-Controller) com o padrão Active Record, testado com MySQL.

## Novidades da versão 2.1.0!

  - Adicionado suporte a relacionamentos de entidades. Agora entidades estrangeiras poderão ser instanciadas automaticamente após consultas.
  - Um novo método vai te ajudar na construção de consultas. O ```fetchGet()``` permite obter os dados de suas consultas sem sobreescrever os dados já obtidos em consultas anteriores.

---

## Instalação

via Composer:

```json
"geovanirangel/modellayer": "2.1.0"
```

---

## Usando o ModelLayer

Antes de tudo configure a conexão. O ModelLayer procurará por uma constante **DBCONFIG** com as seguintes configurações para usar em suas rotinas:
```php
// Exemplo de conexão
define("DBCONFIG", [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "charset" => "utf8mb4",
    "dbname" => "database_name",
    "users" => [
        "default" => [
            "name" => "root",
            "password" => ""
        ],
        "select" => [
            "name" => "select_user",
            "password" => "select_pass"
        ],
        "insert" => [
            "name" => "insert_user",
            "password" => "insert_pass"
        ],
        "update" => [
            "name" => "update_user",
            "password" => "update_pass"
        ],
        "delete" => [
            "name" => "delete_user",
            "password" => "delete_pass"
        ],
    ],
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
]);
```

Os usuários "select", "insert", "update", "delete" são específicos para o seu tipo operação CRUD mas caso não forem encontrados ou configurados o ModelLayer usará o que está em **default**.

Crie um modelo extendendo a classe **Entity** e informe:
  - O **nome da entidade** do banco de dados;
  - A **primary key**;
  - O terceiro parâmetro consisti em uma matriz para mapear a **estrutura da entidade**. As chaves serão os nomes dos campos e os índices poderam ser:
    - **null** (```boolean```): : indicando se o campo poder ficar vazio. O padrão é ```false```, ou seja, ```NOT_NULL```;
    - **created** (```boolean```): : para campos do tipo ```DATETIME``` que guardará o ```TIMESTAMP``` que o registro foi criado. O padrão é ```false```;
    - **updated** (```boolean```): para campos do tipo ```DATETIME``` que guardaram o ```TIMESTAMP``` da atualização do registro. O padrão é ```false```;
    ---
    Indicando relacionamentos entre entidades:
    - **foreignEntity** (```string|null```): para campos que guardam relacionamento (foreign keys). Nesse índice informe a classe que representa a entidade, cuja, o campo faz referência. O padrão é ```null```;
    - **fkRefer** (```string```): Nome do campo que ele fará referência na entidade estrangeira. Caso não informado ele fará referência a chave primária;
    - **hasMany** (```boolean```): Indica se o campo faz referência a vários registros. O padrão é ```false```, ou seja, relacionamentos **1 para N**;
    - **propertyName** (```string```): Nome da propiedade da classe que guardará os dados obtidos do relacionamento. Caso não informado usará o nome da classe informada em **foreignEntity**;

Exemplos de Modelos:
```php
class User extends Entity
{
    public function __construct()
    {
        parent::__construct(
            "user",
            "id",
            [
                "name" => ["null" => false],
                "email" => ["null" => false],
                "created" => ["created" => true],
                "updated" => ["updated" => true]
            ]
        );
    }
}
```
```php
class Adress extends Entity
{
    public function __construct()
    {
        parent::__construct(
            "adresses",
            "id",
            array(
                "adress" => ["null" => false],
                "user_id" => ["null" => false, "foreignEntity" => "Models\User"]
            )
        );
    }
}
```

Depois que configurar a conexão e criar o modelo é só partir pra diversão :) !

__SELECT__:
```php
// Obter todos os _usuários_:
$users = (new User())->getAll();
var_dump($users->data());

// Obter um _usuário_ atrávez de sua primery key:
$user = (new User())->getByPK(1);
var_dump($user->data());
```

O método ```data()``` vai retornará somente os dados que obtemos nas consultas. Uma instância de ```stdClass```;

__DELETE__:
```php
// Deletando usuário atráves da primary_key:
$user = (new User())->getByPK(1)->delByPk();
$user = (new User())->delByPk(1);

// Deletando usuário a partir de uma coluna(ex: email) e um valor:
$user = (new User())->del("email", "user@mail.com");
```

__INSERT & UPDATE__:
O método ```save()``` criará um registro se o objeto atual não possuir um identificador(primary_key) caso contrário vai executar um atualização no registro atual.
```php
// Criando um registro (INSERT):
$user = (new User());
$user->name = "Name";
$user->email = "user@mail.com";
if ($user->save()){
    echo "success!";
}
else {
    echo $user->error()->getMessage();
}

// Editando os dados (UPDATE):
$user = (new User())->getByPK(1);
$user->name = "New Name";
$user->email = "newuser@mail.com";
if ($user->save()){
    echo "success!";
}
else {
    echo $user->error()->getMessage();
}
```

Construindo consultas com o __QueryBuilder__:
O trait _QueryBuilder_ abstraí a construção de consultas sql tornando possível nunca mais a escrita de uma, olha só alguns exemplos com o modelo ```User```:

```php
// Modelo
$user = (new Models\User());

// Obter todos registros:
$user->find()->fetch();

//  Cláusula WHERE:
$user->find()->where("name = :name", ":name=username")->fetch();
$user->find()->where(
    "id = :id AND email = :email",
    ":id=1&:email=example@mail.com"
)->fetch();

// Combinando colunas com JOIN:
$user->find()->innerJoin(new ForeignEntity())->on("foreign_key_name")->fetch();
$user->find()->leftJoin(new ForeignEntity())->on("foreign_key_name")->fetch();
$user->find()->rightJoin(new ForeignEntity())->on("foreign_key_name")->fetch();

// Tem também o métodos group(), order(), limit() e offset():
$user->find()->group("col_name")->order("col_name DESC")->limit(10)->offset(2));
```

Lembre-se de usar o método ```data()``` para obter somente dados e não o objeto em questão.

Pode usar também o método ```fetchGet()``` que retornará somente os dados ao invés de atribuí-lo os objetos em questão. Útil para consultas únicas na captura de dados secundários. Ambos os métodos de **fetch** recebem um valor ```booleano```  indicando o uso do ```PDOStatement::fetchAll```(true) ou ```PDOStatement::fetch```(false). O padrão é ```true```, ou seja, ele usará ```PDOStatement::fetchAll```.

---



## Referências

- [PDO](https://www.php.net/manual/pt_BR/book.pdo.php)
- [PDO Class](https://www.php.net/manual/pt_BR/class.pdo.php)
- [PDO Statement Class](https://www.php.net/manual/pt_BR/class.pdostatement.php)
- [PDO Exception Class](https://www.php.net/manual/pt_BR/class.pdoexception.php)
- [PDO Drivers](https://www.php.net/manual/pt_BR/pdo.drivers.php)


## Feedback, sugestões:
 - Entre em contato comigo pelo email dev.geovanirangel@gmail.com


## Credits

- [Geovani Rangel](https://github.com/geovanirangel) (Web Developer)


## License

The MIT License (MIT).
