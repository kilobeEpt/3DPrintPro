
<?php



require_once __DIR__ . '/../vendor/autoload.php';



use Illuminate\Database\Capsule\Manager as Capsule;

use Illuminate\Container\Container;

use Illuminate\Events\Dispatcher;

use Illuminate\Support\Facades\Facade;

use Dotenv\Dotenv;



// Load .env

if (file_exists(__DIR__ . '/../.env')) {

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');

    $dotenv->load();

}



// Create container

$container = new Container();



// Set facade application

Facade::setFacadeApplication($container);



// Initialize Capsule

$capsule = new Capsule($container);

$capsule->addConnection([

    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',

    'host'      => $_ENV['DB_HOST'] ?? 'localhost',

    'port'      => $_ENV['DB_PORT'] ?? 3306,

    'database'  => $_ENV['DB_DATABASE'] ?? 'test',

    'username'  => $_ENV['DB_USERNAME'] ?? 'root',

    'password'  => $_ENV['DB_PASSWORD'] ?? '',

    'charset'   => 'utf8mb4',

    'collation' => 'utf8mb4_unicode_ci',

    'prefix'    => '',

    'strict'    => false,

]);



// Set event dispatcher

$capsule->setEventDispatcher(new Dispatcher($container));



// Register Capsule as global

$capsule->setAsGlobal();

$capsule->bootEloquent();



// 🔑 КРИТИЧЕСКИ ВАЖНО: Зарегистрировать DB и Schema в контейнере для Facades

$connection = $capsule->getConnection();

$container->instance('db', $connection);

$container->singleton('schema', function() use ($connection) {

    return $connection->getSchemaBuilder();

});


