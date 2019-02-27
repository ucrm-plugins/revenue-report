<?php
declare(strict_types=1);

// =====================================================================================================================
// ERROR HANDLING
// =====================================================================================================================

// IF any of the necessary variables have not been passed, simply die()!
if(!isset($_GET) || !isset($_GET["frm-organization"]) || !isset($_GET["frm-since"]) || !isset($_GET["frm-until"]))
    die();

// =====================================================================================================================
// DATA INITIALIZATION
// =====================================================================================================================

$data = [
    "services"      => [],
    "products"      => [],
    "surcharges"    => [],
    "custom"        => [],
];

// =====================================================================================================================
// ORGANIZATION
// =====================================================================================================================

use UCRM\REST\Endpoints\Organization;

$organizationId = $_GET["frm-organization"] ?: Organization::getByDefault()->getId();   // Should NEVER be null!

// =====================================================================================================================
// TIMEZONE ADJUSTMENTS
// =====================================================================================================================

use UCRM\Common\Config;

$timezone = Config::getTimezone();

// Get the starting and ending dates, using today's date if dates were not provided.
$since          = $_GET["frm-since"]        ?: (new \DateTime())->format("Y-m-d");
$until          = $_GET["frm-until"]        ?: (new \DateTime())->format("Y-m-d");

// Adjust the dates to start/end of the provided dates, and then adjust them to UTC, as that is how the DB stores them!
$since = (new DateTime($since . " 00:00:00 " . $timezone))->setTimezone(new DateTimeZone("UTC"))->format("Y-m-d H:i:s");
$until = (new DateTime($until . " 23:59:59 " . $timezone))->setTimezone(new DateTimeZone("UTC"))->format("Y-m-d H:i:s");

// =====================================================================================================================
// DATABASE CONNECTION
// =====================================================================================================================

$host = getenv("POSTGRES_HOST");
$port = getenv("POSTGRES_PORT");
$name = getenv("POSTGRES_DB");
$user = getenv("POSTGRES_USER");
$pass = getenv("POSTGRES_PASSWORD");

//$organizationId = 1;

$db = \MVQN\Data\Database::connect($host, (int)$port, $name, $user, $pass);



// =====================================================================================================================
// UNPAID/PARTIALLY PAID ITEMS
// =====================================================================================================================

$results = $db->query(
"
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
        client.company_name
        
    FROM invoice_item AS item
      
    INNER JOIN invoice ON item.invoice_id = invoice.invoice_id
    INNER JOIN client ON invoice.client_id = client.client_id
    
    WHERE
        invoice.organization_id = $organizationId AND
        (invoice.invoice_status = 1 OR invoice.invoice_status = 2 OR invoice.invoice_status = 3) AND
        invoice.created_date BETWEEN '$since' AND '$until';
"
)->fetchAll();

// TODO: Determine how we want to handle partially paid invoices and their respective items!

// =====================================================================================================================
// RESIDENTIAL CONTACT NAME FIX-UPS
// =====================================================================================================================

use UCRM\REST\Endpoints\Client;

// Loop through each of the matched invoice items...
foreach($results as &$result)
{
    // IF the current invoice item belongs to an invoice of a residential client...
    if($result["client_type"] === Client::CLIENT_TYPE_RESIDENTIAL)
    {
        // THEN remove the company name field.
        if (isset($result["company_name"]) || $result["company_name"] === null)
            unset($result["company_name"]);

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

        // Now add the contact name to the current invoice item.
        $result["contact_name"] = $contacts[0]["name"];

        // TODO: This would be a great place to cache the results of the client_id -> contact_name lookup!
    }
}

// FINALLY, render the results as a JSON object!
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);




