<?php
declare(strict_types=1);
require_once __DIR__."/vendor/autoload.php";

use MVQN\Localization\Translator;
use MVQN\Localization\Exceptions\TranslatorException;
use MVQN\REST\RestClient;
use MVQN\Twig\Extensions\SwitchExtension;

use UCRM\Common\Config;
use UCRM\Common\Log;
use UCRM\Common\Plugin;
use UCRM\HTTP\Twig\Extensions\PluginExtension;
use UCRM\HTTP\Slim\Middleware\QueryStringRouter;

use UCRM\REST\Endpoints\Version;

use App\Settings;

use Slim\Container;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Views\TwigExtension;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


/**
 * bootstrap.php
 *
 * A common configuration and initialization file.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */

// =====================================================================================================================
// PLUGIN ECOSYSTEM
// =====================================================================================================================

// Initialize the Plugin libraries using this directory as the plugin root!

Plugin::initialize(__DIR__, [
    "modules" => [
        Plugin::MODULE_DATA,
        Plugin::MODULE_HTTP,
        Plugin::MODULE_REST,
        //Plugin::MODULE_SMTP,
    ]
]);


// Regenerate the Settings class, in case anything has changed in the manifest.json file.
Plugin::createSettings("App", "Settings");

// =====================================================================================================================
// ENVIRONMENT
// =====================================================================================================================

// IF an .env file exists in the project, THEN load it!
if(file_exists(__DIR__."/../.env"))
{
    (new \Dotenv\Dotenv(__DIR__."/../"))->load();
}

// =====================================================================================================================
// REST CLIENT
// =====================================================================================================================

// Generate the REST API URL from either an ENV variable (including from .env file),  or fallback to localhost.

$restUrl =
    rtrim(
        getenv("REST_URL") ?:                                                           // .env (or ENV variable)
            Settings::UCRM_LOCAL_URL ?:                                                     // ucrm.json
                (isset($_SERVER['HTTPS']) ? "https://localhost/" : "http://localhost/"),        // By initial request
        "/")."/api/v1.0";


// OVERRIDE WITH KNOWN GOOD VALUES!!!
// TODO: Using to debug some "unable to connect" errors by cURL!
//$restUrl = (isset($_SERVER['HTTPS']) ? "https://localhost" : "http://localhost")."/api/v1.0";
//$restUrl = "https://ucrm.dev.mvqn.net/api/v1.0";
//$restUrl = Settings::UCRM_PUBLIC_URL . "/api/v1.0";

// Configure the REST Client...
RestClient::setBaseUrl($restUrl);
RestClient::setHeaders([
    "Content-Type: application/json",
    "X-Auth-App-Key: ".Settings::PLUGIN_APP_KEY
]);


/*
$logger = new Logger("plugin");
$fileHandler = new StreamHandler(__DIR__."/data/plugin.log", Logger::DEBUG);
//$fileHandler->setFormatter(new \Monolog\Formatter\JsonFormatter);
$logger->pushHandler($fileHandler);
//$logger->debug("INFO message");
*/


try
{
    // TODO: Add code to only perform these checks when in DEBUG mode!

    Log::clear();
    Log::write("----------------------------------------");

    // "https://billing.ridgecomms.com/api/v1.0"

    //$hostname = parse_url("https://billing.ridgecomms.com/api/v1.0")["host"];
    $hostname = parse_url($restUrl)["host"];

    $ip = gethostbyname($hostname);
    if($ip === $hostname)
    {
        Log::debug("Hostname '$hostname' could not be resolved!");
        //$logger->debug("Hostname '$hostname' could not be resolved!");
    }
    else
    {
        $ping = shell_exec("ping -c 1 $ip");
        $status = $ping !== NULL ? "SUCCEEDED!" : "FAILED!";

        Log::debug("Pinging: $hostname ($ip)...$status");
        //$logger->debug("Pinging: $hostname ($ip)...$status");

    }

    $version = Version::get();
    Log::debug("$restUrl/version => $version");
    //$logger->debug("$restUrl/version => $version");
    //Log::writeArray(debug_backtrace());
} catch(\Exception $e)
{
    Log::error($e->getMessage());
}

// =====================================================================================================================
// LOCALIZATION
// =====================================================================================================================

// Set the dictionary directory and "default" locale.
try
{
    Translator::setDictionaryDirectory(__DIR__."/translations/");
    Translator::setCurrentLocale(str_replace("_", "-", Config::getLanguage()) ?: "en-US", TRUE);
} catch(TranslatorException $e)
{
    Log::http("No dictionary could be found!", 500);
}

// =====================================================================================================================
// ROUTING (SLIM)
// =====================================================================================================================

// Create Slim Framework Application, given the provided settings.
$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => TRUE,
        "addContentLengthHeader" => FALSE,
        "determineRouteBeforeAppMiddleware" => TRUE,
    ],
]);

// Get a reference to the DI Container included with the Slim Framework.
$container = $app->getContainer();

// =====================================================================================================================
// RENDERING (TWIG)
// =====================================================================================================================

// Configure Twig Renderer
$container["twig"] = function(Container $container)
{
    $twig = new \Slim\Views\Twig(
        [
            __DIR__."/src/App/Views/",
        ],
        [
            //'cache' => 'path/to/cache'
            "debug" => TRUE,
        ]
    );

    // Instantiate and add Slim specific extension
    $router = $container->get("router");
    $uri = Uri::createFromEnvironment(new Environment($_SERVER));

    $twig->addExtension(new TwigExtension($router, $uri));
    $twig->addExtension(new Twig_Extension_Debug());

    $twig->addExtension(new SwitchExtension());
    $twig->addExtension(new PluginExtension(Settings::class));

    return $twig;
};

// ---------------------------------------------------------------------------------------------------------------------

// Override the default 404 Page!
$container['notFoundHandler'] = function(Container $container)
{
    return function(Request $request, Response $response) use ($container): Response
    {
        /** @var \Slim\Router $router */
        $router = $container->get("router");

        $data = [
            "vRoute" => $request->getAttribute("vRoute"),
            "router" => $router,
        ];

        return $container->twig->render($response, "404.html.twig", $data);
    };
};

// =====================================================================================================================
// LOGGING (MONOLOG)
// =====================================================================================================================

// Configure MonoLog
$container['logger'] = function(\Slim\Container $container)
{
    $logger = new Monolog\Logger("template-plugin");
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler(
        PHP_SAPI === "cli-server" ? "php://stdout" : __DIR__."/logs/www.log",
        \Monolog\Logger::DEBUG
    ));
    return $logger;
};


// Applied in Ascending order, bottom up!
$app->add(new \UCRM\HTTP\Slim\Middleware\PluginAuthentication($container, function(\UCRM\Sessions\SessionUser $user)
{
    return ($user->getUserGroup() === "Admin Group");
}));
$app->add(new QueryStringRouter("/index.php"));
//$app->add(new QueryStringRouter("/test.html"));

//define("BOOTSTRAPPED", TRUE);
//echo "TEST";
