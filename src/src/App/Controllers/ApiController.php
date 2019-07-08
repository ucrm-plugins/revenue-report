<?php
declare(strict_types=1);

namespace App\Controllers;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

use MVQN\Data\Database;

use UCRM\Common\Config;
use UCRM\Common\Log;
use UCRM\REST\Endpoints\Client;
use UCRM\REST\Endpoints\Currency;
use UCRM\REST\Endpoints\Organization;
use UCRM\REST\Endpoints\Service;
use UCRM\REST\Endpoints\ServiceSurcharge;
use UCRM\REST\Endpoints\Surcharge;
use UCRM\REST\Endpoints\Tax;


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

                // =====================================================================================================
                // Report Generator
                // =====================================================================================================
                $app->get("/report",

                    function (Request $request, Response $response, array $args) use ($container)
                    {
                        // ---------------------------------------------------------------------------------------------
                        // PARAMETER HANDLING
                        // ---------------------------------------------------------------------------------------------

                        $params = $request->getQueryParams();

                        $organizationId = $params["organizationId"] ?? Organization::getByDefault()->getId();
                        $since          = $params["since"]          ?? (new \DateTime())->format("Y-m-d");
                        $until          = $params["until"]          ?? (new \DateTime())->format("Y-m-d");

                        // ---------------------------------------------------------------------------------------------
                        // DATA INITIALIZATION
                        // ---------------------------------------------------------------------------------------------

                        $data = include(__DIR__ . "/Api/Templates/ReportTemplate.php");

                        // ---------------------------------------------------------------------------------------------
                        // TIMEZONE ADJUSTMENTS
                        // ---------------------------------------------------------------------------------------------

                        $timezone = Config::getTimezone();

                        // Adjust the dates to start/end of the provided dates, and then adjust them to UTC, as that is
                        // how the DB stores them!
                        $since = (new \DateTime($since . " 00:00:00 " . $timezone))
                            ->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d H:i:s");
                        $until = (new \DateTime($until . " 23:59:59 " . $timezone))
                            ->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d H:i:s");

                        // ---------------------------------------------------------------------------------------------
                        // DATABASE CONNECTION
                        // ---------------------------------------------------------------------------------------------

                        $host = getenv("POSTGRES_HOST");
                        $port = getenv("POSTGRES_PORT");
                        $name = getenv("POSTGRES_DB");
                        $user = getenv("POSTGRES_USER");
                        $pass = getenv("POSTGRES_PASSWORD");

                        $db = Database::connect($host, (int)$port, $name, $user, $pass);

                        // ---------------------------------------------------------------------------------------------
                        // UNPAID/PARTIALLY PAID ITEMS
                        // ---------------------------------------------------------------------------------------------

                        $results = $db->query(
                        '
                            SELECT
                                item.item_id,
                                item.invoice_id,
                                item.label,
                                item.quantity,
                                item.price,
                                item.total,
                                item.taxable,
                                item.tax_rate1,
                                item.tax_rate2,
                                item.tax_rate3,
                                item.discr,
                                invoice.invoice_id,
                                invoice.invoice_number,
                                invoice.created_date,
                                invoice.invoice_status,
                                client.client_id,
                                client.client_type,
                                client.company_name,
                                u.first_name,
                                u.last_name
                                
                            FROM invoice_item AS item
                              
                            INNER JOIN invoice ON item.invoice_id = invoice.invoice_id
                            INNER JOIN client ON invoice.client_id = client.client_id
                            INNER JOIN "user" AS u ON client.user_id = u.user_id
                            
                            WHERE
                                invoice.organization_id = '.$organizationId.' AND
                                (invoice.invoice_status = 1 OR invoice.invoice_status = 3) AND
                                invoice.created_date BETWEEN \''.$since.'\' AND \''.$until.'\';
                        '
                        )->fetchAll();

                        // TODO: Determine how we want to handle partially paid invoices and their respective items!

                        // ---------------------------------------------------------------------------------------------
                        // DATA PREPARATION
                        // ---------------------------------------------------------------------------------------------

                        /**
                         * Populates the JSON results object given the provided information.
                         *
                         * @param array $data The data object for which to populate.
                         * @param string $section The section in the data object for which to populate.
                         * @param array $result The result of which to populate the data object.
                         */
                        function populate(array &$data, string $section, array $result)
                        {
                            $name = "";

                            if(isset($result["company_name"]))
                                $name = $result["company_name"];

                            if(isset($result["contact_name"]))
                                $name = $result["contact_name"];

                            $label = $result["label"]; // Always exists?

                            $data[$section]["groups"][$label]["items"][$result["item_id"]] = [
                                "invoice_id" => $result["invoice_id"],
                                "invoice_number" => $result["invoice_number"],
                                "name" => $name,
                                "quantity" => $result["quantity"],
                                "price" => $result["price"],
                                "total" => $result["total"],
                                "taxable" => $result["taxable"],
                                "tax_rate1" => $result["tax_rate1"],
                                "tax_rate2" => $result["tax_rate2"],
                                "tax_rate3" => $result["tax_rate3"],
                                "paid" => $result["invoice_status"] === 3,
                            ];

                            /*
                            if(!array_key_exists("counts", $data[$section]))
                                $data[$section]["counts"] = [
                                    "invoiced" => 0,
                                    "paid" => 0,
                                ];
                            */

                            switch($result["invoice_status"])
                            {
                                case 1:
                                    $status = "invoiced";
                                    //$data[$section]["counts"]["invoiced"] += $result["quantity"];
                                    break;
                                case 3:
                                    $status = "paid";
                                    //$data[$section]["counts"]["paid"] += $result["quantity"];
                                    break;
                                default: // Will NEVER be, per the SQL query!
                                    die("Unsupported Invoice Status: '{$result['discr']}");
                            }


                            // NOTE: Below we add any missing data sets, to ensure consistent JSON results!

                            if(!array_key_exists("subtotals", $data[$section]["groups"][$label]))
                                $data[$section]["groups"][$label]["subtotals"] = [];


                            if(!array_key_exists("invoiced", $data[$section]["groups"][$label]["subtotals"]))
                                $data[$section]["groups"][$label]["subtotals"]["invoiced"] = [];
                            if(!array_key_exists("paid", $data[$section]["groups"][$label]["subtotals"]))
                                $data[$section]["groups"][$label]["subtotals"]["paid"] = [];

                            if(!array_key_exists("quantity", $data[$section]["groups"][$label]["subtotals"]["invoiced"]))
                                $data[$section]["groups"][$label]["subtotals"]["invoiced"]["quantity"] = 0;
                            if(!array_key_exists("quantity", $data[$section]["groups"][$label]["subtotals"]["paid"]))
                                $data[$section]["groups"][$label]["subtotals"]["paid"]["quantity"] = 0;

                            if(!array_key_exists("total", $data[$section]["groups"][$label]["subtotals"]["invoiced"]))
                                $data[$section]["groups"][$label]["subtotals"]["invoiced"]["total"] = 0;
                            if(!array_key_exists("total", $data[$section]["groups"][$label]["subtotals"]["paid"]))
                                $data[$section]["groups"][$label]["subtotals"]["paid"]["total"] = 0;

                            if(!array_key_exists("tax1", $data[$section]["groups"][$label]["subtotals"]["invoiced"]))
                                $data[$section]["groups"][$label]["subtotals"]["invoiced"]["tax1"] = 0;
                            if(!array_key_exists("tax1", $data[$section]["groups"][$label]["subtotals"]["paid"]))
                                $data[$section]["groups"][$label]["subtotals"]["paid"]["tax1"] = 0;

                            if(!array_key_exists("tax2", $data[$section]["groups"][$label]["subtotals"]["invoiced"]))
                                $data[$section]["groups"][$label]["subtotals"]["invoiced"]["tax2"] = 0;
                            if(!array_key_exists("tax2", $data[$section]["groups"][$label]["subtotals"]["paid"]))
                                $data[$section]["groups"][$label]["subtotals"]["paid"]["tax2"] = 0;

                            if(!array_key_exists("tax3", $data[$section]["groups"][$label]["subtotals"]["invoiced"]))
                                $data[$section]["groups"][$label]["subtotals"]["invoiced"]["tax3"] = 0;
                            if(!array_key_exists("tax3", $data[$section]["groups"][$label]["subtotals"]["paid"]))
                                $data[$section]["groups"][$label]["subtotals"]["paid"]["tax3"] = 0;



                            $quantity = $result["quantity"];
                            $total = $result["total"];
                            $tax1 = ($result["total"] * ($result["tax_rate1"] / 100.0));
                            $tax2 = ($result["total"] * ($result["tax_rate2"] / 100.0));
                            $tax3 = ($result["total"] * ($result["tax_rate3"] / 100.0));


                            // Update the "group" totals.
                            $data[$section]["groups"][$label]["subtotals"][$status]["quantity"] += $quantity;
                            $data[$section]["groups"][$label]["subtotals"][$status]["total"] += $total;
                            $data[$section]["groups"][$label]["subtotals"][$status]["tax1"] += $tax1;
                            $data[$section]["groups"][$label]["subtotals"][$status]["tax2"] += $tax2;
                            $data[$section]["groups"][$label]["subtotals"][$status]["tax3"] += $tax3;

                            // Update the "section" totals.

                            /*
                            if(!array_key_exists("quantity", $data[$section]["totals"][$status]))
                                $data[$section]["totals"][$status]["quantity"] = 0;
                            if(!array_key_exists("total", $data[$section]["totals"][$status]))
                                $data[$section]["totals"][$status]["total"] = 0;
                            if(!array_key_exists("tax1", $data[$section]["totals"][$status]))
                                $data[$section]["totals"][$status]["tax1"] = 0;
                            if(!array_key_exists("tax2", $data[$section]["totals"][$status]))
                                $data[$section]["totals"][$status]["tax2"] = 0;
                            if(!array_key_exists("tax3", $data[$section]["totals"][$status]))
                                $data[$section]["totals"][$status]["tax3"] = 0;
                            */

                            $data[$section]["totals"][$status]["quantity"] += $quantity;
                            $data[$section]["totals"][$status]["total"] += $total;
                            $data[$section]["totals"][$status]["tax1"] += $tax1;
                            $data[$section]["totals"][$status]["tax2"] += $tax2;
                            $data[$section]["totals"][$status]["tax3"] += $tax3;

                            // Combined!

                            $data[$section]["totals"]["combined"]["quantity"] += $quantity;
                            $data[$section]["totals"]["combined"]["total"] += $total;
                            $data[$section]["totals"]["combined"]["tax1"] += $tax1;
                            $data[$section]["totals"]["combined"]["tax2"] += $tax2;
                            $data[$section]["totals"]["combined"]["tax3"] += $tax3;

                        };





                        // Loop through each of the matched invoice items...
                        foreach($results as &$result)
                        {

                            // IF the current invoice item belongs to an invoice of a residential client...
                            if($result["client_type"] === Client::CLIENT_TYPE_RESIDENTIAL)
                            {
                                // THEN remove the company name field.
                                if (isset($result["company_name"]) || $result["company_name"] === null)
                                    unset($result["company_name"]);

                                /*
                                // Then query the client contact's for the the one with the lowest id...
                                $contacts = $db->query(
                                "
                                    SELECT c.name
                                    FROM client_contact c
                                    WHERE c.client_id = {$result['client_id']}
                                    ORDER BY c.client_contact_id
                                    LIMIT 1;
                                "
                                )->fetchAll();

                                // There should ALWAYS be at least contact, as the system requires, at minimum, a first and last name.
                                if (count($contacts) < 1)
                                    die();
                                */
                                // Now add the contact name to the current invoice item.
                                //$result["contact_name"] = $contacts[0]["name"];
                                $result["contact_name"] = $result["first_name"] ." ". $result["last_name"];

                                // TODO: This would be a great place to cache the results of the client_id -> contact_name lookup!
                            }

                            // NOTE: Below we handle the different invoice line item types...

                            switch($result["discr"])
                            {
                                case "invoice_item_service":
                                    populate($data, "services", $result);
                                    break;

                                case "invoice_item_product":
                                    populate($data, "products", $result);
                                    break;

                                case "invoice_item_surcharge":
                                    populate($data, "surcharges", $result);
                                    break;

                                case "invoice_item_other":
                                    populate($data, "others", $result);
                                    break;

                                case "invoice_item_fee":
                                    populate($data, "fees", $result);
                                    break;

                                default:
                                    die("Unknown Descriptor: '{$result['discr']}");
                            }

                        }

                        // FINALLY, return the results as a JSON object!
                        return $response->withJson($data);
                    }

                );





                $app->get("/expected",

                    function (Request $request, Response $response, array $args) use ($container)
                    {
                        // ---------------------------------------------------------------------------------------------
                        // PARAMETER HANDLING
                        // ---------------------------------------------------------------------------------------------

                        $today = (new \DateTime())->format("Y-m-d");

                        $params = $request->getQueryParams();

                        $organizationId = $params["organizationId"] ?? Organization::getByDefault()->getId();
                        $since          = $params["since"]          ?? date("Y-m-1", strtotime($today));
                        $until          = $params["until"]          ?? date("Y-m-t", strtotime($since));

                        Log::debug("Organization ID: $organizationId");
                        Log::debug("Since (UTC)    : $since");
                        Log::debug("Until (UTC)    : $until");

                        // ---------------------------------------------------------------------------------------------
                        // TIMEZONE ADJUSTMENTS
                        // ---------------------------------------------------------------------------------------------

                        $timezone = Config::getTimezone();

                        Log::debug("Timezone       : $timezone");

                        // Adjust the dates to start/end of the provided dates, and then adjust them to UTC, as that is
                        // how the DB stores them!
                        $since = (new \DateTime($since." 00:00:00 ".$timezone));
                        $until = (new \DateTime($until." 23:59:59 ".$timezone));
                        $sinceString = $since->format(Service::DATETIME_FORMAT);
                        $untilString = $until->format(Service::DATETIME_FORMAT);

                        $sinceSQL = $since->format("Y-m-d");
                        $untilSQL = $until->format("Y-m-d");

                        Log::debug("Since (SQL)    : $sinceSQL");
                        Log::debug("Until (SQL)    : $untilSQL");

                        /** @var Organization $organization */
                        $organization = Organization::getById($organizationId);
                        Log::debug("Organization   : $organization");

                        $currencyId = $organization->getCurrencyId();
                        Log::debug("Currency ID    : $currencyId");

                        /** @var Currency $currency */
                        $currency = Currency::getById($currencyId);
                        Log::debug("Currency       : $currency");

                        // ---------------------------------------------------------------------------------------------
                        // DATABASE CONNECTION
                        // ---------------------------------------------------------------------------------------------

                        $host = getenv("POSTGRES_HOST");
                        $port = getenv("POSTGRES_PORT");
                        $name = getenv("POSTGRES_DB");
                        $user = getenv("POSTGRES_USER");
                        $pass = getenv("POSTGRES_PASSWORD");

                        $dbString = "pgsql://$user:$pass@$host:$port/$name";

                        Log::debug("DB Connection  : $dbString");

                        $db = Database::connect($host, (int)$port, $name, $user, $pass);

                        Log::debug("DB Status      : " . $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS));

                        Log::debug("UCRM Language  : " . Config::getLanguage());

                        $data = [

                            "locale" => [
                                "language" => str_replace("_", "-", Config::getLanguage()),

                                "currency" => [
                                    "name" => $currency->getName(),
                                    "code" => $currency->getCode(),
                                    "symbol" => $currency->getSymbol(),
                                ],
                            ],




                            "ranges" => [
                                "since" => $sinceString,
                                "until" => $untilString,
                            ],

                            "totals" => [

                                "services" => 0,
                                "surcharges" => 0,
                                "discounts" => 0,
                                "taxes" => 0,
                                "grand" => 0,
                            ],

                            // DEBUG
                            //"results" => [],
                            //"surcharges" => [],

                        ];



                        // ---------------------------------------------------------------------------------------------
                        // SERVICES
                        // ---------------------------------------------------------------------------------------------

                        $services = $db->query(
                            "
                            SELECT
                                service_id,
                                status,
                                active_from,
                                active_to,
                                discount_from,
                                discount_to,
                                discount_type,
                                discount_value,
                                individual_price,
                                tariff_id,
                                period_id,
                                tax_id1,
                                tax_id2,
                                tax_id3
                            FROM
                                 service
                            WHERE
                                (service.status = 1 OR service.status = 5) AND
                                (service.active_from IS NOT NULL AND service.active_from < '$untilSQL'::date) AND
                                (service.active_to IS NULL OR service.active_to > '$sinceSQL'::date);
                            "
                        )->fetchAll();

                        $data["services"] = $services;

                        // ---------------------------------------------------------------------------------------------
                        // SERVICE FIX-UPS
                        // ---------------------------------------------------------------------------------------------

                        $rangeSeconds = ($until->getTimestamp() + 1) - $since->getTimestamp();
                        $rangeDays = $rangeSeconds / 24 / 60 / 60;


                        foreach($services as $service)
                        {
                            $activeFrom = (new \DateTime($service["active_from"]." 00:00:00 ".$timezone));

                            if($activeFrom < $since)
                                $activeFrom = $since;

                            $activeTo =
                                $service["active_to"] !== null
                                    ? (new \DateTime($service["active_to"]." 23:59:59 ".$timezone))
                                    : null;

                            if($activeTo === null || $activeTo > $until)
                                $activeTo = $until;

                            // NOTE: Only care about whole days here!

                            $proratedSeconds = ($activeTo->getTimestamp() + 1) - $activeFrom->getTimestamp();
                            $proratedDays = $proratedSeconds / 24 / 60 / 60;

                            $proratedPercentage = $proratedDays / $rangeDays;

                            //print_r("\n".$proratedDays);
                            //print_r("\n".$proratedPercentage);

                            // -----------------------------------------------------------------------------------------
                            // SERVICES
                            // -----------------------------------------------------------------------------------------

                            // Get price...
                            $servicePrice = 0;

                            if($service["individual_price"] !== null)
                            {
                                $servicePrice = (double)$service["individual_price"];
                            }
                            else
                            {
                                $tariffs = $db->query(
                                    "
                                    SELECT
                                        taxable,
                                        tax_id,
                                        period,
                                        price
                                    FROM
                                        tariff
                                    INNER JOIN
                                        tariff_period ON
                                            (tariff_period.tariff_id = {$service['tariff_id']}) AND
                                            (tariff_period.period_id = {$service['period_id']})
                                    WHERE
                                        tariff.tariff_id = {$service['tariff_id']};
                                        
                                    "
                                )->fetchAll();

                                $servicePrice = $tariffs[0]["price"];
                            }

                            //print_r("\n$price");

                            $serviceAmount = $servicePrice * $proratedPercentage;

                            //print_r("\n$amount");

                            $data["totals"]["services"] += $serviceAmount;



                            // -----------------------------------------------------------------------------------------
                            // SURCHARGES
                            // -----------------------------------------------------------------------------------------

                            $surcharges = $db->query(
                                "
                                SELECT
                                    service_surcharge.price AS individual_price,
                                    service_surcharge.taxable,
                                
                                    surcharge.price,
                                    surcharge.tax_id
                                    
                                FROM
                                    service_surcharge
                                FULL OUTER JOIN
                                    surcharge ON
                                        (service_surcharge.price IS NULL) AND
                                        (service_surcharge.surcharge_id = surcharge.surcharge_id)
                                WHERE
                                    service_surcharge.service_id = {$service['service_id']};
                                "
                            )->fetchAll();

                            //print_r($surcharges);

                            $surchargesPrice = 0;

                            if(count($surcharges) > 0)
                            {
                                foreach($surcharges as $surcharge)
                                {
                                    if($surcharge["price"] === null && $surcharge["individual_price"] !== null)
                                        $surchargesPrice += (double)$surcharge["individual_price"];

                                    if($surcharge["individual_price"] === null && $surcharge["price"] !== null)
                                        $surchargesPrice += (double)$surcharge["price"];
                                }
                            }


                            $data["totals"]["surcharges"] += $surchargesPrice * $proratedPercentage;


                            // -----------------------------------------------------------------------------------------
                            // DISCOUNTS
                            // -----------------------------------------------------------------------------------------

                            $currentDiscounts = 0;

                            if($service["discount_type"] !== Service::DISCOUNT_TYPE_NONE)
                            {

                                $discountFrom =
                                    $service["discount_from"] !== NULL
                                        ? (new \DateTime($service["discount_from"]." 00:00:00 ".$timezone))
                                        : NULL;

                                if($activeTo === NULL || $discountFrom < $since)
                                {
                                    $discountFrom = $since;
                                }

                                $discountTo =
                                    $service["discount_to"] !== NULL
                                        ? (new \DateTime($service["discount_to"]." 23:59:59 ".$timezone))
                                        : NULL;

                                if($discountTo === NULL || $discountTo > $until)
                                {
                                    $discountTo = $until;
                                }

                                // NOTE: Only care about whole days here!

                                $proratedDiscountSeconds = ($discountTo->getTimestamp() + 1) -
                                    $discountFrom->getTimestamp();
                                $proratedDiscountDays = $proratedDiscountSeconds / 24 / 60 / 60;

                                $proratedDiscountPercentage = $proratedDiscountDays / $rangeDays;




                                switch($service["discount_type"])
                                {
                                    case Service::DISCOUNT_TYPE_PERCENTAGE:
                                        $amount = -((double)$service["discount_value"] / 100) * $serviceAmount;
                                        $currentDiscounts += $amount;
                                        break;

                                    case Service::DISCOUNT_TYPE_FIXED:
                                        $amount = -((double)$service["discount_value"]);
                                        $currentDiscounts += $amount;
                                        break;

                                }

                                $data["totals"]["discounts"] += $currentDiscounts * $proratedPercentage;

                            }

                            // -----------------------------------------------------------------------------------------
                            // TAXES
                            // -----------------------------------------------------------------------------------------

                            $taxAmounts = 0;

                            if ($service["tax_id1"] !== null)
                            {
                                $taxRates = $db->query(
                                    "
                                    SELECT
                                        tax_id,
                                        rate
                                    FROM
                                        tax
                                    WHERE
                                        tax.tax_id = {$service['tax_id1']};
                                    "
                                )->fetchAll();

                                $taxRate = (double)$taxRates[0]["rate"] / 100;

                                $taxAmounts += ($serviceAmount + $currentDiscounts) * $taxRate;

                            }

                            // TODO: Figure out how to handle all the other taxes???


                            $data["totals"]["taxes"] += $taxAmounts;


                        }


                        // -----------------------------------------------------------------------------------------
                        // GRAND TOTAL
                        // -----------------------------------------------------------------------------------------

                        $data["totals"]["grand"] =
                            (
                                $data["totals"]["services"] +
                                $data["totals"]["surcharges"] +
                                $data["totals"]["discounts"] +
                                $data["totals"]["taxes"]
                            );





                        return $response->withJson($data);



                        // ---------------------------------------------------------------------------------------------
                        // DATA INITIALIZATION
                        // ---------------------------------------------------------------------------------------------

                        //$data = include(__DIR__ . "/Api/Templates/ReportTemplate.php");

                        $data = [

                            "locale" => [
                                "language" => str_replace("_", "-", Config::getLanguage()),

                                "currency" => [
                                    "name" => $currency->getName(),
                                    "code" => $currency->getCode(),
                                    "symbol" => $currency->getSymbol(),
                                ],
                            ],

                            "ranges" => [
                                "since" => $sinceString,
                                "until" => $untilString,
                            ],

                            "totals" => [

                                "services" => 0,
                                "surcharges" => 0,
                                "discounts" => 0,
                                "taxes" => 0,
                                "grand" => 0,
                            ],

                            // DEBUG
                            //"results" => [],
                            //"surcharges" => [],

                        ];


                        $results = Service::get("", [],
                            [
                                "organizationId" => $organizationId,
                                /*
                                "statuses" => [
                                    Service::STATUS_ACTIVE
                                ]
                                */
                            ]
                        );

                        // DEBUG
                        //$data["results"] = $results;


                        foreach($results as /** @var Service $result */ $result)
                        {
                            // Get the "Active From" date of the current Service.
                            $activeFrom =
                                $result->getActiveFrom() !== null
                                    ? new \DateTime($result->getActiveFrom())
                                    : null;

                            // IF the "Active From" date is set AND after the until date, THEN simply skip this Service!
                            if ($activeFrom !== null && ($activeFrom > $until))
                                continue;

                            // Get the "Active To" date of the current Service.
                            $activeTo =
                                $result->getActiveTo() !== null
                                    ? new \DateTime($result->getActiveTo())
                                    : null;

                            // IF the "Active To" date is set AND before the since date, THEN simply skip this Service!
                            if ($activeTo !== null && ($activeTo < $since))
                                continue;

                            // -----------------------------------------------------------------------------------------
                            // PRICE
                            // -----------------------------------------------------------------------------------------

                            // Add the Price.
                            $currentPrices = (float)$result->getPrice();

                            // -----------------------------------------------------------------------------------------
                            // SURCHARGES
                            // -----------------------------------------------------------------------------------------

                            // TODO: Determine if we should not just get the difference, as below...
                            // $surcharges = $result->getTotalPrice() - $result->getDiscountValue() ???


                            $currentSurcharges = 0.0;

                            $serviceSurcharges = ServiceSurcharge::get(
                                "/clients/services/:id/service-surcharges",
                                [ "id" => $result->getId() ],
                                []
                            );

                            foreach($serviceSurcharges as $serviceSurcharge)
                            {
                                /** @var ServiceSurcharge $serviceSurcharge */

                                // DEBUG:
                                //$data["surcharges"][] = $serviceSurcharge;

                                if($serviceSurcharge->getPrice() === null)
                                {
                                    /** @var Surcharge $surcharge */
                                    $surcharge = Surcharge::getById($serviceSurcharge->getSurchargeId());

                                    $currentSurcharges += $surcharge->getPrice();
                                }
                                else
                                {
                                    $currentSurcharges += $serviceSurcharge->getPrice();
                                }

                            }

                            // -----------------------------------------------------------------------------------------
                            // DISCOUNT
                            // -----------------------------------------------------------------------------------------

                            $currentDiscounts = 0.0;

                            $discountFrom =
                                $result->getDiscountFrom() !== null
                                    ? new \DateTime($result->getDiscountFrom())
                                    : null;

                            $discountTo =
                                $result->getDiscountTo() !== null
                                    ? new \DateTime($result->getDiscountTo())
                                    : null;

                            if (($discountFrom  === null || ($discountFrom  > $until) ||
                                ($discountTo    === null || ($discountTo    < $since))) ||
                                ($result->getDiscountType() === Service::DISCOUNT_TYPE_NONE))
                            {
                                // Do nothing here!
                            }
                            else
                            {
                                $discountAmount = 0;

                                switch($result->getDiscountType())
                                {
                                    case Service::DISCOUNT_TYPE_PERCENTAGE:
                                        $discountAmount = $result->getPrice() * ($result->getDiscountValue() / 100.0);
                                        break;
                                    case Service::DISCOUNT_TYPE_FIXED:
                                        $discountAmount = $result->getDiscountValue();
                                        break;
                                    default:
                                        die("Discount type invalid: '{$result->getDiscountType()}'!");
                                }

                                $currentDiscounts += $discountAmount;
                            }

                            // -----------------------------------------------------------------------------------------
                            // TAXES
                            // -----------------------------------------------------------------------------------------

                            //$currentTaxes = 0.0;

                            $currentTaxes = $result->getTotalPrice() -
                                (
                                    $currentPrices +
                                    $currentSurcharges -
                                    $currentDiscounts
                                );

//                            if($result->getTaxable())
//                            {
//                                /** @var Tax $tax1 */
//                                $tax1 = $result->getTax1Id() ? Tax::getById($result->getTax1Id()) : null;
//                                /** @var Tax $tax2 */
//                                $tax2 = $result->getTax2Id() ? Tax::getById($result->getTax2Id()) : null;
//                                /** @var Tax $tax3 */
//                                $tax3 = $result->getTax3Id() ? Tax::getById($result->getTax3Id()) : null;
//
//                                $tax1Rate = $tax1 ? $tax1->getRate() : 0.0;
//                                $tax2Rate = $tax2 ? $tax2->getRate() : 0.0;
//                                $tax3Rate = $tax3 ? $tax3->getRate() : 0.0;
//
//                                // TODO: Cache the results, as there are likely many reuses of the Tax IDs!
//
//                                $tax1Amount = $result->getPrice() * $tax1Rate;
//                                $tax2Amount = $result->getPrice() * $tax2Rate;
//                                $tax3Amount = $result->getPrice() * $tax3Rate;
//
//                                $taxTotal = $tax1Amount + $tax2Amount + $tax3Amount;
//
//                                $currentTaxes += $taxTotal;
//                            }



                            // -----------------------------------------------------------------------------------------
                            // AGGREGATES
                            // -----------------------------------------------------------------------------------------

                            $data["totals"]["services"] += $currentPrices;
                            $data["totals"]["surcharges"] += $currentSurcharges;
                            $data["totals"]["discounts"] -= $currentDiscounts;
                            $data["totals"]["taxes"] += $currentTaxes;

                            $data["totals"]["grand"] +=
                                (
                                    $currentPrices +
                                    $currentSurcharges -
                                    $currentDiscounts +
                                    $currentTaxes
                                );

                        }

                        // ---------------------------------------------------------------------------------------------
                        // ROUNDING
                        // ---------------------------------------------------------------------------------------------

                        $data["totals"]["services"] = round($data["totals"]["services"], 2);
                        $data["totals"]["surcharges"] = round($data["totals"]["surcharges"], 2);
                        $data["totals"]["discounts"] = round($data["totals"]["discounts"], 2);
                        $data["totals"]["taxes"] = round($data["totals"]["taxes"], 2);
                        $data["totals"]["grand"] = round($data["totals"]["grand"], 2);


                        return $response->withJson($data);
                    }
                );


                // =====================================================================================================
                // DASHBOARD WIDGET
                // =====================================================================================================

                $app->get("/dashboard-widget/state",

                    function (Request $request, Response $response, array $args) use ($container)
                    {
                        $homepageRoot = "/usr/src/ucrm/app/Resources/views/homepage";
                        $componentName = "mvqn__revenue_report_ex__dashboard_widget.html.twig";

                        $homepagePath = "$homepageRoot/index.html.twig";
                        $componentPath = "$homepageRoot/components/$componentName";

                        $enabled = false;

                        if(file_exists($componentPath))
                        {
                            $searchCode = "{% include 'homepage/components/$componentName' %}\n";

                            $contents = file_get_contents($homepagePath);

                            if(strpos($contents, $searchCode) !== false)
                                $enabled = true;
                        }

                        return $response->withJson([ "enabled" => $enabled ]);
                    }
                );



                $app->post("/dashboard-widget/enable",

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



                $app->post("/dashboard-widget/disable",

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
