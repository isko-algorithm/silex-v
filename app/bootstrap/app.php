<?php
/**
 * bootstrap/app.php
 * The main loading file of this P2ME API Middleware
 * @author Gabriel John P. Gagno
 * @author Jose Carlo Macariola
 * @version 1.1
 * @copyright 2016 Stratpoint Technologies, Inc.
 * @date 12/15/16
 */
require_once __DIR__.'/../../vendor/autoload.php';

# use libraries
use App\Libraries\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#retrieve environment
$environment = require_once 'start.php';

#set config path
$config_path = __DIR__."/../../config/{$environment}";
//


# initialize Silex Application Instance
$app = new Silex\Application();
$app->boot();


# register config service provider for entire app (NOTE: THIS HAS TO GO FIRST BEFORE
# THE OTHER CONFIGURABLES)
$app->register(new Igorw\Silex\ConfigServiceProvider($config_path."/app.php"));

// # initialize environment here
// try{
//   $app['env'] = new Dotenv\Dotenv(__DIR__.'/../../', '.env.'.$app['environment']);
//   $app['env']->load();
// }
// catch (\Exception $e) {
//   $app->json(['error' => 500, 'error_description' => 'Environment Not Found'], 500)->send();
//   die();
// }

# Switch $app['debug'] to on or off
$app['debug'] = filter_var(Util::env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

# REGISTER SERVICS

# register logger service provider
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => __DIR__.'/../../logs/log-'.date('Y-m-d').'.log',
  'monolog.name' => $app['name']
));

# register security service provider
$app->register(new Silex\Provider\SecurityServiceProvider());

# register validator provider (optional)
$app->register(new Silex\Provider\ValidatorServiceProvider());

# register config service provider for database
$app->register(new Igorw\Silex\ConfigServiceProvider($config_path."/database.php"));

# register config service provider for constants
$app->register(new \Igorw\Silex\ConfigServiceProvider($config_path."/constants.php"));

# register config service provider for doctrine
$app->register(new Silex\Provider\DoctrineServiceProvider());

# register doctrine ORM
$app->register(new \Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider());

# Register errors
$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    $messageArray = array(
        'developer_message' =>  $e->getMessage(),
        'user_message'      =>  'An error has occurred. Please try again.'
    );
    $message = Util::formatErrorHandler($code, "100", $messageArray);
    return $app->json($message, $code);
});

# ROUTES
$app->mount('/', new \App\Routes());

# initialize app in util library to be accessible everywhere
\App\Libraries\Util::initialize($app);
