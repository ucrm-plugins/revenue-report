<?php
declare(strict_types=1);

namespace App\Controllers;

use mysql_xdevapi\Exception;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ApiController
 *
 * An example controller.
 *
 * @package App\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class ApiController
{
    /**
     * ExampleController constructor.
     *
     * @param App $app The Slim Application for which to configure routing.
     */
    public function __construct(App $app)
    {
        $app->group("/api",

            function() use ($app)
            {
                // Get a local reference to the Slim Application's DI Container.
                $container = $app->getContainer();

                $app->post("/report",

                    function (Request $request, Response $response, array $args) use ($container) {
                        return $response->write("This is an example route!");
                    }

                );

                $app->get("/expected",

                    function (Request $request, Response $response, array $args) use ($container) {
                        return $response->withJson([ "test1" => 1, "test2" => 2 ]);
                    }
                );


                // =====================================================================================================
                // DASHBOARD WIDGET
                // =====================================================================================================

                $app->get("/dashboard-widget/enabled",

                    function (Request $request, Response $response, array $args) use ($container)
                    {
                        $homepageRoot = "/usr/src/ucrm/app/Resources/views/homepage";
                        $componentName = "mvqn__revenue_report_ex__dashboard_widget.html.twig";

                        // ---------------------------------------------------------------------------------------------
                        // COMPONENT CREATION
                        // ---------------------------------------------------------------------------------------------

                        $componentPath = "$homepageRoot/components/$componentName";
                        $localContents = file_get_contents( __DIR__ . "/../Views/partials/$componentName");

                        // IF the component template does not exist, THEN create it!
                        if(!file_exists($componentPath) || file_get_contents($componentPath) !== $localContents)
                            file_put_contents($componentPath, $localContents);

                        // IF the component template still does not exist, THEN something went wrong!
                        if(!file_exists($componentPath))
                            return $response->withStatus(400, "Component '$componentName' could not added!");

                        // ---------------------------------------------------------------------------------------------
                        // HOMEPAGE MODIFICATIONS
                        // ---------------------------------------------------------------------------------------------

                        $homepagePath = "$homepageRoot/index.html.twig";

                        // Modify the homepage template...
                        $contents = file_get_contents($homepagePath);

                        $searchCode = "{% include 'homepage/components/overview.html.twig' %}\n";
                        $additionalCode = "{% include 'homepage/components/$componentName' %}\n";

                        if(strpos($contents, $additionalCode) !== false)
                            return $response->withStatus(202, "Component '$componentName' was already added!");

                        $insertPosition = strpos($contents, $searchCode) + strlen($searchCode);
                        $insertCode = "                $additionalCode"; // Indented!

                        $replacedContents = substr_replace($contents, $insertCode, $insertPosition, 0);
                        file_put_contents($homepagePath, $replacedContents);

                        exec("rm -rf /usr/src/ucrm/app/cache/prod/twig");

                        return $response->withStatus(200, "Component '$componentName' was successfully added!");
                    }
                );



                $app->put("/dashboard-widget/enable",

                    function (Request $request, Response $response, array $args) use ($container)
                    {
                        $homepageRoot = "/usr/src/ucrm/app/Resources/views/homepage";
                        $componentName = "mvqn__revenue_report_ex__dashboard_widget.html.twig";

                        // ---------------------------------------------------------------------------------------------
                        // COMPONENT CREATION
                        // ---------------------------------------------------------------------------------------------

                        $componentPath = "$homepageRoot/components/$componentName";
                        $localContents = file_get_contents( __DIR__ . "/../Views/partials/$componentName");

                        // IF the component template does not exist, THEN create it!
                        if(!file_exists($componentPath) || file_get_contents($componentPath) !== $localContents)
                            file_put_contents($componentPath, $localContents);

                        // IF the component template still does not exist, THEN something went wrong!
                        if(!file_exists($componentPath))
                            return $response->withStatus(400, "Component '$componentName' could not added!");

                        // ---------------------------------------------------------------------------------------------
                        // HOMEPAGE MODIFICATIONS
                        // ---------------------------------------------------------------------------------------------

                        $homepagePath = "$homepageRoot/index.html.twig";

                        // Modify the homepage template...
                        $contents = file_get_contents($homepagePath);

                        $searchCode = "{% include 'homepage/components/overview.html.twig' %}\n";
                        $additionalCode = "{% include 'homepage/components/$componentName' %}\n";

                        if(strpos($contents, $additionalCode) !== false)
                            return $response->withStatus(202, "Component '$componentName' was already added!");

                        $insertPosition = strpos($contents, $searchCode) + strlen($searchCode);
                        $insertCode = "                $additionalCode"; // Indented!

                        $replacedContents = substr_replace($contents, $insertCode, $insertPosition, 0);
                        file_put_contents($homepagePath, $replacedContents);

                        exec("rm -rf /usr/src/ucrm/app/cache/prod/twig");

                        return $response->withStatus(200, "Component '$componentName' was successfully added!");
                    }
                );



                $app->put("/dashboard-widget/disable",

                    function (Request $request, Response $response, array $args) use ($container)
                    {
                        $homepageRoot = "/usr/src/ucrm/app/Resources/views/homepage";
                        $componentName = "mvqn__revenue_report_ex__dashboard_widget.html.twig";

                        // ---------------------------------------------------------------------------------------------
                        // COMPONENT DELETION
                        // ---------------------------------------------------------------------------------------------

                        $componentPath = "$homepageRoot/components/$componentName";

                        // IF the component template exists, THEN delete it!
                        if(file_exists($componentPath))
                            exec("rm $componentPath");

                        // IF the component template still exists, THEN something went wrong!
                        if(file_exists($componentPath))
                            return $response->withStatus(400, "Component '$componentName' could not be removed!");

                        // ---------------------------------------------------------------------------------------------
                        // HOMEPAGE MODIFICATIONS
                        // ---------------------------------------------------------------------------------------------

                        $homepagePath = "$homepageRoot/index.html.twig";

                        // Modify the homepage template...
                        $contents = file_get_contents($homepagePath);

                        $existingCode = "{% include 'homepage/components/$componentName' %}\n";

                        if(strpos($contents, $existingCode) === false)
                            return $response->withStatus(202, "Component '$componentName' was already removed!");

                        $removeCode = "                $existingCode"; // Indented!

                        $replacedContents = str_replace($removeCode, "", $contents);
                        file_put_contents($homepagePath, $replacedContents);

                        exec("rm -rf /usr/src/ucrm/app/cache/prod/twig");

                        return $response->withStatus(200, "Component '$componentName' was successfully removed!");
                    }
                );


            }

        );
    }

}