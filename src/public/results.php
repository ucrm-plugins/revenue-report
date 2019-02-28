<?php

if(!$data || $data === [])
    exit();

//if(array_key_exists("services", $data) && $data["services"] !== [])
{
    // TODO: Find a more robust way of handling currency locales!
    setlocale(LC_MONETARY, \UCRM\Common\Config::getLanguage());

    foreach($data as $type => $results)
    {
        ?>
        <div id="results" class="row">

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

                            <div class="w-25 text-right" style="padding-right:0px;">
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
                            //$link = $url_prefix === "" ? "" : $url_prefix . $result["item_"]

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

                                        $i_tax    = ($i_tax !== 0 ? money_format("%i", $i_tax) : "");
                                        $p_tax    = ($p_tax !== 0 ? money_format("%i", $p_tax) : "");

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

                                    $in_tax    = !$item["paid"] ? money_format("%i", $tax) : "";
                                    $pd_tax    =  $item["paid"] ? money_format("%i", $tax) : "";

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
}
?>