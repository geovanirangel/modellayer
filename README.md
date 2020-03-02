# ModelLayer @geovanirangel

O ModelLayer é um componente que abstrai operações de CRUD (Select, Insert, Update e Delete) no seu banco de dados. Baseado em PDO, projetado para um estruturas MVC (Model-View-Controller) com o padrão Active Record, testado com MySQL.

## Instalação

via Composer:

```json
"geovanirangel/modellayer": "1.0"
```

---

## Usando o ModelLayer

Antes de tudo configure a conexão. O ModelLayer procurará por uma constante __DBCONFIG__ com as seguintes configurações para usar em suas rotinas:
```php
// Exemplo de conexão
define("DBCONFIG", [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "charset" => "utf8",
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


Crie um modelo extendendo a classe _Entity_, ela vai te dar acesso a todos os recursos do ModelLayer.
No método construtor informe o **nome da entidade** no banco de dados, o nome da **primary key** e as colunas como uma matriz. Nessa matriz pode conter o índice __"null"__ para informar se o campo poder ficar nullo(o padrão é ```false```), o índice __"updated"__ para colunas do tipo __TIMESTAMP__ que guardaram um valor __DATETIME__ na hora da atualização(o padrão é ```false```) e o índice __"created"__ para colunas do tipo __TIMESTAMP__ que guardaram o valor __DATETIME__ de sua criação(o padrão é ```false```).
Exemplo de Modelo:
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

Depois que configurar a conexão e criar o modelo é só partir pra diversão :) !

__SELECT__:
```php
// Obter todos os _usuários_:
$users = (new User())->getAll();
var_dump($users->data());

// Obter um _usuário_ atrávez de sua primery key:
$user = (new User())->getByPK(1);
var_dump($uses->data());
```

O método ```data()``` vai retorna os dados que geralmento obtemos em consultas.

__DELETE__:
```php
// Deletando usuário atráves da primary_key:
$user = (new User())->getByPK(1)->delByPk();
$user = (new User())->delByPk(1);

// Deletando usuário a partir de uma coluna e um valor:
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

// Editando os dados do _usuário_ (UPDATE):
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
O trait _QueryBuilder_ abstraí a construção de consultas sql tornando possível a escrita de uma, olha só...

```php
// Obter todos registros:
$user = (new User())->find()->fetch();

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

// Tem também o étodos group(), order(), limit() e offset():
$user->find()->group("col_name")->order("col_name DESC")->limit(10)->offset(2));
```

O método ```fetch``` aceita um parâmetro booleano(padrão ```true```) que indica se os dados serão obtidos com o método ```fetchAll``` ou ```fetch()``` do PDO.


A classe __Entity__ tambèm possuí outros métodos úteis como:
| Método | Descrição | Retorno |
| --- | --- | --- |
| data()      | Retorna os dados obtidos na última query    | ```null|stdClass|array```|
| exist()     | Retorna ```true``` caso tenha retornado um registro único e  ```false``` caso contrário   | ```bool```|
| found()     | Igual ao ```exist()``` porém retorna o própio objeto caso exista | ```bool(false)|self```|
| count()     | Retorna número de linhas afetadas/retornadas da última query ou última instrução sql | ```int```|
| sqlState()  | Retorna o código sqlstate da última ```PDOException```| ```null|int```|
| error()  | Retorna um objeto da interface ```Throwable``` correspondente ao último erro ocorrido caso exista | ```null|Throwable```|

---



## Referências

- [PDO](https://www.php.net/manual/pt_BR/book.pdo.php)
- [PDO Class](https://www.php.net/manual/pt_BR/class.pdo.php)
- [PDO Statement Class](https://www.php.net/manual/pt_BR/class.pdostatement.php)
- [PDO Exception Class](https://www.php.net/manual/pt_BR/class.pdoexception.php)
- [PDO Drivers](https://www.php.net/manual/pt_BR/pdo.drivers.php)


## Feedback

 - Entre em contato comigo pelo email dev.geovanirangel@gmail.com


## Credits

- [Geovani Rangel](https://github.com/geovanirangel) (Web Developer)


## License

The MIT License (MIT).
