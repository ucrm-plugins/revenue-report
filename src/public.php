<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once(realpath(dirname(__FILE__) . "/bootstrap.php"));



use App\Settings;
use App\Controllers;
use UCRM\HTTP\Slim\Controllers\Common;

/**
 * Use an immediately invoked function here, to avoid global namespace pollution...
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 */
(function() use ($app)
{



    // -----------------------------------------------------------------------------------------------------------------
    // CONFIGURATION
    // -----------------------------------------------------------------------------------------------------------------

    define("ASSET_PATH", realpath(__DIR__."/public/"));
    define("VIEWS_PATH", realpath(__DIR__."/src/App/Views/"));

    define("BASE_URL", isset($_SERVER["HTTP_REFERER"]) ?
        rtrim(Settings::PLUGIN_PUBLIC_URL, ".php") :    // .../public
        Settings::PLUGIN_PUBLIC_URL."?");               // .../public.php?




    // -----------------------------------------------------------------------------------------------------------------
    // CUSTOM ROUTES
    // -----------------------------------------------------------------------------------------------------------------

    //new Controllers\ExampleController($app);


    new Controllers\ApiController($app);

    // TODO: Add additional custom routes here!
    // ...

    //echo "Test";



    // -----------------------------------------------------------------------------------------------------------------
    // BUILD-IN ROUTES
    // Note: These controllers should be added last, so the above controllers can override routes as needed.
    // -----------------------------------------------------------------------------------------------------------------

    // Append a route handler for static assets.
    new Common\AssetController($app);

    // Append a route handler for Twig templates.
    new Common\TemplateController($app);

    // Append a route handler for PHP scripts.
    new Common\ScriptController($app);

    /** @var \Monolog\Logger $logger */
    $logger = $app->getContainer()["logger"];
    $logger->debug("Test");


    // Run the Slim Framework Application!
    $app->run();

})();

