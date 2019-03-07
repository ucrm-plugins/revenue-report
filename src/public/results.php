<?php

// =====================================================================================================================
// SANITY CHECKS
// =====================================================================================================================

if (!$data || $data === [] || (
    $data["services"]["counts"]["invoiced"] === 0 && $data["services"]["counts"]["paid"] === 0 &&
    $data["products"]["counts"]["invoiced"] === 0 && $data["products"]["counts"]["paid"] === 0 &&
    $data["surcharges"]["counts"]["invoiced"] === 0 && $data["surcharges"]["counts"]["paid"] === 0 &&
    $data["others"]["counts"]["invoiced"] === 0 && $data["others"]["counts"]["paid"] === 0 &&
    $data["fees"]["counts"]["invoiced"] === 0 && $data["fees"]["counts"]["paid"] === 0))
    exit();

// =====================================================================================================================
// LOCALIZATION
// =====================================================================================================================

// TODO: Find a more robust way of handling numeric and currency locales???
setlocale(LC_MONETARY, \UCRM\Common\Config::getLanguage());
setlocale(LC_NUMERIC, \UCRM\Common\Config::getLanguage());

// =====================================================================================================================
// DATA PREPARATION
// =====================================================================================================================

$paidServicesCount          = array_key_exists("services", $data)       ? $data["services"]["counts"]["paid"]   : 0;
$paidProductsCount          = array_key_exists("products", $data)       ? $data["products"]["counts"]["paid"]   : 0;
$paidSurchargesCount        = array_key_exists("surcharges", $data)     ? $data["surcharges"]["counts"]["paid"] : 0;
$paidOthersCount            = array_key_exists("others", $data)         ? $data["others"]["counts"]["paid"]     : 0;
$paidFeesCount              = array_key_exists("fees", $data)           ? $data["fees"]["counts"]["paid"]       : 0;

$invoicedServicesCount      = array_key_exists("services", $data)       ?
                              $data["services"]["counts"]["invoiced"]   + $data["services"]["counts"]["paid"]   : 0;
$invoicedProductsCount      = array_key_exists("products", $data)       ?
                              $data["products"]["counts"]["invoiced"]   + $data["products"]["counts"]["paid"]   : 0;
$invoicedSurchargesCount    = array_key_exists("surcharges", $data)     ?
                              $data["surcharges"]["counts"]["invoiced"] + $data["surcharges"]["counts"]["paid"] : 0;
$invoicedOthersCount        = array_key_exists("others", $data)         ?
                              $data["others"]["counts"]["invoiced"]     + $data["others"]["counts"]["paid"]     : 0;
$invoicedFeesCount          = array_key_exists("fees", $data)           ?
                              $data["fees"]["counts"]["invoiced"]       + $data["fees"]["counts"]["paid"]       : 0;

$paidChartData              = implode(",",
                            [
                                $paidServicesCount,
                                $paidProductsCount,
                                $paidSurchargesCount,
                                $paidOthersCount,
                                $paidFeesCount
                            ]);

$invoicedChartData          = implode(",",
                            [
                                $invoicedServicesCount,
                                $invoicedProductsCount,
                                $invoicedSurchargesCount,
                                $invoicedOthersCount,
                                $invoicedFeesCount
                            ]);

?>



<div class="row d-none d-sm-flex">
    <div class="col-12">
        <div class="card mb-1 mb-sm-3">
            <div class="card-body">
                <?php
                // =====================================================================================================
                // CHART SECTION
                // =====================================================================================================
                ?>
                <div class="d-flex w-100 justify-content-center">
                    <div class="w-100">
                        <canvas id="chart" width="100" height="35"></canvas>
                    </div>
                </div>

                <script>
                    $(function() {

                        let ctx = document.getElementById("chart").getContext('2d');

                        let chart = new Chart(ctx, {
                            type: "horizontalBar",
                            data: {
                                labels: [
                                    "Services", "Products", "Surcharges", "Others", "Fees"
                                ],
                                datasets: [
                                    {
                                        label: "Paid",
                                        data: [<?php echo $paidChartData; ?>],
                                        backgroundColor: [
                                            "#FF0000",
                                            "#FFFF00",
                                            "#008000",
                                            "#0000FF",
                                            "#FFA500"
                                        ]
                                    },
                                    {
                                        label: "Invoiced",
                                        data: [<?php echo $invoicedChartData; ?>],
                                        backgroundColor: [
                                            "rgba(256, 0, 0, 0.5)",
                                            "rgba(256, 256, 0, 0.5)",
                                            "rgba(0, 128, 0, 0.5)",
                                            "rgba(0, 0, 156, 0.5)",
                                            "rgba(255, 165, 0, 0.5)"
                                        ],
                                        borderColor: [
                                            "rgba(256, 0, 0, 1)",
                                            "rgba(256, 256, 0, 1)",
                                            "rgba(0, 128, 0, 1)",
                                            "rgba(0, 0, 156, 1)",
                                            "rgba(255, 165, 0, 1)"
                                        ],
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                legend: {
                                    display: false,
                                    position: "bottom",
                                    labels: {
                                        boxWidth: 12
                                    },
                                    padding:{
                                        left: 5
                                    }
                                },
                                title: {
                                    display: true,
                                    text: "Revenue (Paid / Invoiced)",
                                    fontSize: 16
                                },
                                scales: {
                                    yAxes: [
                                        {
                                            stacked: true,
                                            barPercentage: 0.8
                                        }
                                    ],
                                    xAxes: [
                                        {
                                            stacked: false,
                                            barPercentage: 0.8
                                        }
                                    ]
                                },
                                onClick: function(e) {

                                    let activeElement = chart.getElementAtEvent(e);

                                    if(activeElement.length > 0) {

                                        let label = activeElement[0]._model.label;
                                        let tag = $("a[name='" + label + "']");

                                        $('html,body').animate({
                                            scrollTop: tag.offset().top
                                        }, "slow");
                                    }
                                }
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>



<?php
/*----------------------------------------------------------------------------------------------------------------------
RESULTS
----------------------------------------------------------------------------------------------------------------------*/

// Loop through each category of results...
foreach($data as $type => $results)
{
    // IF the current category has no results to display, THEN simply continue to the next category...
    if($results["counts"]["invoiced"] === 0 && $results["counts"]["paid"] === 0)
        continue;
    ?>

    <!------------------------------------------------------------------------------------------------------------------
    CATEGORY ROW: Services/Products/Surcharges/Others/Fees
    ------------------------------------------------------------------------------------------------------------------->
    <div class="row">
        <!--------------------------------------------------------------------------------------------------------------
        CATEGORY ANCHOR: Page Navigation
        --------------------------------------------------------------------------------------------------------------->
        <a name="<?php echo ucfirst($type); ?>"></a>

        <!--------------------------------------------------------------------------------------------------------------
        CATEGORY CONTAINER
        --------------------------------------------------------------------------------------------------------------->
        <div class="col-12">
            <div class="card mb-1 mb-sm-3">
                <div class="card-body">
                    <!--------------------------------------------------------------------------------------------------
                    CATEGORY TITLE: Mobile Only (XS)
                    --------------------------------------------------------------------------------------------------->
                    <div class="d-flex flex-row align-items-center d-sm-none">
                        <div class="w-100 border-bottom mb-1">
                            <h5><?php echo ucfirst($type);?></h5>
                        </div>
                    </div>

                    <!--------------------------------------------------------------------------------------------------
                    CATEGORY HEADER
                    --------------------------------------------------------------------------------------------------->
                    <div class="d-flex flex-row align-items-center mb-2">
                        <!----------------------------------------------------------------------------------------------
                        CATEGORY TITLE: All Other Sizes (SM-XL)
                        ----------------------------------------------------------------------------------------------->
                        <div class="card-title mb-0 w-25">
                            <h5 class="d-none d-sm-block"><?php echo ucfirst($type);?></h5>
                        </div>

                        <!----------------------------------------------------------------------------------------------
                        CATEGORY LEGEND
                        ----------------------------------------------------------------------------------------------->
                        <div class="w-25 text-right" style="padding-right:0;">
                            <div>Quantity</div>
                            <div style="font-size:0.75em;margin-top:-4px;">&nbsp;</div>
                        </div>
                        <div class="w-25 text-right" style="padding-right:4px;">
                            <div>Invoiced</div>
                            <div style="font-size:0.75em;margin-top:-4px;">Tax</div>
                        </div>
                        <div class="w-25 text-right" style="padding-right:8px;">
                            <div>Paid</div>
                            <div style="font-size:0.75em;margin-top:-4px;">Tax</div>
                        </div>
                    </div>

                    <?php
                    /*--------------------------------------------------------------------------------------------------
                    GROUP
                    --------------------------------------------------------------------------------------------------*/

                    // Initialize a group counter to name each section accordingly.
                    $groupIndex = 0;

                    // Loop through each group in this category...
                    foreach ($results as $groupName => $result)
                    {
                        // IF the current key is "counts", THEN simply continue to the next group...
                        if($groupName === "counts")
                            continue;

                        // Generate a unique ID for the current group's card, used for the collapse.
                        $section_id = "$type-$groupIndex-card";

                        // TODO: Verify consistency when the new compound taxes arrive in 2.16.0-beta1!

                        // Calculate the accumulated taxes for both the invoiced and paid items in this group.
                        $iTax       = $result["invoiced"]["tax1"] +
                                      $result["invoiced"]["tax2"] +
                                      $result["invoiced"]["tax3"];
                        $pTax       = $result["paid"]["tax1"] +
                                      $result["paid"]["tax2"] +
                                      $result["paid"]["tax3"];

                        // Format the tax amounts per the server's currency locale.
                        $iTax       = money_format("%i", $iTax);
                        $pTax       = money_format("%i", $pTax);

                        // Calculate the accumulated totals for both the invoiced and paid items in this group.
                        $invoiced   = $result["invoiced"]["total"];
                        $paid       = $result["paid"]["total"];

                        // Format the total amounts per the server's currency locale.
                        $invoiced   = money_format("%i", $invoiced);
                        $paid       = money_format("%i", $paid);

                        // Calculate the accumulated quantities for both the invoiced and paid items in this group.
                        $quantity   = $result["invoiced"]["quantity"] + $result["paid"]["quantity"];

                        // Format the quantities per the server's numeric locale.
                        $quantity   = number_format($quantity, 2);
                        ?>

                        <!----------------------------------------------------------------------------------------------
                        GROUP CARD
                        ----------------------------------------------------------------------------------------------->
                        <div class="card">
                            <div class="card-header p-2">
                                <!--------------------------------------------------------------------------------------
                                GROUP TITLE: Mobile Only (XS)
                                --------------------------------------------------------------------------------------->
                                <div class="d-flex flex-row align-items-start d-sm-none">
                                    <div class="w-100 border-bottom mb-1">
                                        <a  id="<?php echo $section_id.'-button'; ?>"
                                            class="toggle-link"
                                            style="text-decoration: none;"
                                            data-toggle="collapse"
                                            href="#<?php echo $section_id; ?>"
                                            aria-expanded="false"
                                            aria-controls="<?php echo $section_id; ?>">

                                            <i class="rotate fas fa-chevron-circle-right mr-2"></i>
                                        </a>

                                        <strong><?php echo $groupName;?></strong>
                                    </div>
                                </div>

                                <!--------------------------------------------------------------------------------------
                                GROUP HEADER
                                --------------------------------------------------------------------------------------->
                                <div class="d-flex flex-row align-items-start">
                                    <!----------------------------------------------------------------------------------
                                    GROUP TITLE: All Other Sizes (SM-XL)
                                    ----------------------------------------------------------------------------------->
                                    <div class="w-25">
                                        <div class="d-none d-sm-block">
                                            <a  id="<?php echo $section_id.'-button'; ?>"
                                                class="toggle-link"
                                                style="text-decoration: none;"
                                                data-toggle="collapse"
                                                href="#<?php echo $section_id; ?>"
                                                aria-expanded="false"
                                                aria-controls="<?php echo $section_id; ?>">

                                                <i class="rotate fas fa-chevron-circle-right mr-2"></i>
                                            </a>

                                            <strong><?php echo $groupName;?></strong>
                                        </div>
                                    </div>

                                    <!----------------------------------------------------------------------------------
                                    GROUP TOTALS
                                    ----------------------------------------------------------------------------------->
                                    <div class="w-25 text-right">
                                        <strong><?php echo $quantity;?></strong>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo "&nbsp"; ?>
                                        </div>
                                    </div>
                                    <div class="w-25 text-right">
                                        <strong><?php echo $invoiced;?></strong>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo ($iTax !== "" ? "+$iTax" : "&nbsp;"); ?>
                                        </div>
                                    </div>
                                    <div class="w-25 text-right">
                                        <strong><?php echo $paid;?></strong>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo ($pTax !== "" ? "+$pTax" : "&nbsp;"); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!------------------------------------------------------------------------------------------
                            GROUP BODY
                            ------------------------------------------------------------------------------------------->
                            <div id="<?php echo $section_id; ?>" class="collapse in">
                                <div class="card-body p-2">

                                    <?php
                                    /*----------------------------------------------------------------------------------
                                    ITEMS
                                    ----------------------------------------------------------------------------------*/

                                    // Loop through each item in this group...
                                    foreach($result["items"] as $item)
                                    {
                                        // Get the quantity for the current item.
                                        $quantity   = $item["quantity"];

                                        // Format the quantity per the server's numeric locale.
                                        $quantity   = number_format($quantity, 2);

                                        // Get the (unit) price for the current item.
                                        $unit     = $item["price"];

                                        // TODO: Should we calculate the $quantity * $unit to verify $price???

                                        // Get the total of for the current item.
                                        $price    = $item["total"];

                                        // TODO: Verify consistency when the new compound taxes arrive in 2.16.0-beta1!

                                        // Calculate the total tax for the current item.
                                        // NOTE: Tax amounts here are as percentages and need to be coerced, as below.
                                        $tax      = (
                                                        (
                                                            $item["tax_rate1"] +
                                                            $item["tax_rate2"] +
                                                            $item["tax_rate3"]
                                                        ) / 100.0
                                                    ) * $price;




                                        $in_tax    = (!$item["paid"] && $tax !== 0.0) ? money_format("%i", $tax) : "";
                                        $pd_tax    = ($item["paid"] && $tax !== 0.0) ? money_format("%i", $tax) : "";

                                        $invoiced  = !$item["paid"] ? money_format("%i", $price) : "";
                                        $paid      =  $item["paid"] ? money_format("%i", $price) : "";

                                        ?>

                                        <!-- <div class="card-body p-2 bg-primary"> -->
                                            <div class="card-text">

                                                <div class="d-flex flex-row align-items-center d-sm-none">
                                                    <div class="w-100 border-bottom mb-1">
                                                        <div><?php echo $item["name"];?></div>
                                                        <div style="font-size:0.75em;margin-top:-4px;">
                                                            <a  href="/billing/invoice/<?php echo $item['invoice_id'] ?>"
                                                                target="_parent">
                                                                Invoice # <?php echo $item["invoice_number"]?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-row justify-content-between align-items-center">
                                                    <div class="w-25">
                                                        <div class="d-none d-sm-block">
                                                            <div><?php echo $item["name"];?></div>
                                                            <div style="font-size:0.75em;margin-top:-4px;">
                                                                <a  href="/billing/invoice/<?php echo $item['invoice_id'] ?>"
                                                                    target="_parent">
                                                                    Invoice # <?php echo $item["invoice_number"]?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-25 text-right">
                                                        <div><?php echo $quantity;?></div>
                                                        <div style="font-size:0.75em;margin-top:-4px;">&nbsp;</div>
                                                    </div>
                                                    <div class="w-25 text-right">
                                                        <div><?php echo $invoiced;?></div>
                                                        <div style="font-size:0.75em;margin-top:-4px;">
                                                            <?php echo ($in_tax !== "" ? "+$in_tax" : "&nbsp;"); ?>
                                                        </div>
                                                    </div>
                                                    <div class="w-25 text-right">
                                                        <div><?php echo $paid;?></div>
                                                        <div style="font-size:0.75em;margin-top:-4px;">
                                                            <?php echo ($pd_tax !== "" ? "+$pd_tax" : "&nbsp;"); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <!-- </div>-->
                                        <?php

                                        $groupIndex++;
                                    }
                                    ?>
                                </div>

                                <?php




                                ?>
                                <!--
                                <div class="card-footer p-2 text-center">
                                    Pagination
                                </div>
                                -->
                            </div>

                        </div>
                        <?php
                    }

                    ?>


                </div>
            </div>
        </div>
    </div>

    <?php
}

?>

<style>
    a.toggle-link, a.toggle-link:active, a.toggle-link:focus {
        text-decoration: none;
        outline: 0;
    }

    .rotate {
        -moz-transition: all 0.1s linear;
        -webkit-transition: all 0.1s linear;
        transition: all 0.1s linear;
    }

    .rotate.down{
        -ms-transform: rotate(90deg);
        -moz-transform: rotate(90deg);
        -webkit-transform: rotate(90deg);
        transform: rotate(90deg);
    }


</style>


<script>

    $("a.toggle-link").on("click", function(e) {

        let $this = $(this);

        let $icon = $this.find("i.rotate");
        $icon.toggleClass("down");

        /*
        if($this.attr("aria-expanded") === "true") {
            // CLOSING: So animate the icon to closed.
            console.log("CLOSE");
        } else {
            // OPENING: So animate the icon to opened.
            console.log("OPEN");
        }
        */



        //let $target = $(e.target);

        //console.log(controls);
        //console.log($target.attr("aria-expanded"));

    });

</script>
