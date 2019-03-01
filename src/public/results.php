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

// TODO: Find a more robust way of handling currency locales!
setlocale(LC_MONETARY, \UCRM\Common\Config::getLanguage());

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
foreach($data as $type => $results)
{
    if($results["counts"]["invoiced"] === 0 && $results["counts"]["paid"] === 0)
        continue;

    ?>



    <div class="row">
        <a name="<?php echo ucfirst($type); ?>"></a>

        <div class="col-12">
            <div class="card mb-1 mb-sm-3">
                <div class="card-body">

                    <div class="d-flex flex-row align-items-center d-sm-none">
                        <div class="w-100 border-bottom mb-1">
                            <h5><?php echo ucfirst($type);?></h5>
                        </div>
                    </div>

                    <div class="d-flex flex-row align-items-center mb-2">
                        <div class="card-title mb-0 w-25">
                            <h5 class="d-none d-sm-block"><?php echo ucfirst($type);?></h5>
                        </div>

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
                    foreach ($results as $name => $result)
                    {
                        if($name === "counts")
                            continue;

                        ?>
                        <div class="card">
                            <div class="card-header p-2">

                                <div class="d-flex flex-row align-items-center d-sm-none">
                                    <div class="w-100 border-bottom mb-1">
                                        <strong><?php echo $name;?></strong>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="w-25">
                                        <div class="d-none d-sm-block">
                                            <strong><?php echo $name;?></strong>
                                        </div>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo "&nbsp"; ?>
                                        </div>
                                    </div>
                                    <?php
                                    $i_tax    = $result["invoiced"]["tax1"] +
                                                $result["invoiced"]["tax2"] +
                                                $result["invoiced"]["tax3"];
                                    $p_tax    = $result["paid"]["tax1"] +
                                                $result["paid"]["tax2"] +
                                                $result["paid"]["tax3"];

                                    $invoiced = $result["invoiced"]["total"];// + $i_tax;
                                    $paid     = $result["paid"]["total"];// + $p_tax;

                                    //$i_tax    = ($i_tax !== 0 ? money_format("%i", $i_tax) : "");
                                    //$p_tax    = ($p_tax !== 0 ? money_format("%i", $p_tax) : "");
                                    $i_tax    = money_format("%i", $i_tax);
                                    $p_tax    = money_format("%i", $p_tax);

                                    $quantity = $result["invoiced"]["quantity"] + $result["paid"]["quantity"];
                                    $invoiced = money_format("%i", $invoiced);
                                    $paid     = money_format("%i", $paid);

                                    ?>
                                    <div class="w-25 text-right">
                                        <strong><?php echo $quantity;?></strong>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo "&nbsp"; ?>
                                        </div>
                                    </div>
                                    <div class="w-25 text-right">
                                        <strong><?php echo $invoiced;?></strong>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo ($i_tax !== "" ? "+$i_tax" : "&nbsp;"); ?>
                                        </div>
                                    </div>
                                    <div class="w-25 text-right">
                                        <strong><?php echo $paid;?></strong>
                                        <div style="font-size:0.75em;margin-top:-4px;">
                                            <?php echo ($p_tax !== "" ? "+$p_tax" : "&nbsp;"); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            foreach($result["items"] as $item)
                            {
                                //var_dump($item);

                                $quantity = $item["quantity"];
                                $unit     = $item["price"];
                                $price    = $item["total"];

                                $tax      = (($item["tax_rate1"] +
                                            $item["tax_rate2"] +
                                            $item["tax_rate3"]) / 100.0) *
                                            $price;

                                //$total    = $price + $tax;


                                $in_tax    = (!$item["paid"] && $tax !== 0.0) ? money_format("%i", $tax) : "";
                                $pd_tax    = ($item["paid"] && $tax !== 0.0) ? money_format("%i", $tax) : "";

                                $invoiced  = !$item["paid"] ? money_format("%i", $price) : "";
                                $paid      =  $item["paid"] ? money_format("%i", $price) : "";

                                ?>

                                <div class="card-body p-2">
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
                                </div>
                                <?php
                            }
                            ?>
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