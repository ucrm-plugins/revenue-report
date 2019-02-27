<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revenue Report</title>

    <link rel="stylesheet" href="/assets/fonts/lato/lato.css?v=2.15.0-beta5">
    <link rel="stylesheet" href="/assets/fonts/ubnt-icon/ubnt-icon.css?v=2.15.0-beta5">
    <link rel="stylesheet" href="/assets/fonts/ucrm-icon/style.css?v=2.15.0-beta5">
    <link rel="stylesheet" href="/assets/vendor/jquery-ui/themes/smoothness/jquery-ui.min.css?v=2.15.0-beta5">
    <link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css?v=2.15.0-beta5">
    <link rel="stylesheet" href="/dist/main.min.css?v=2.15.0-beta5">

    <link href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
          rel="stylesheet"
          integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
          crossorigin="anonymous">

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS"
          crossorigin="anonymous">


    <link rel="stylesheet" href="public/css/main.css">

    <script type="text/javascript" src="/dist/jquery.min.js?v=2.15.0-beta5"></script>

</head>

<body>
<div id="header">
    <h1 class="float-left mr-3">Revenue Report</h1>
    <a href="https://github.com/ucrm-plugins/revenue-report" target="_blank" class="button button--icon-only">
        <img src="?/images/github/logo-32px.png" alt="GitHub" height=16>
    </a>
</div>

<div id="content" class="container-fluid ml-0 mr-0">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <form id="report-form">
                        <div class="form-row mb-3">
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                                <label class="mb-0" for="frm-organization">Organization:</label>
                                <select name="organization" id="frm-organization" class="form-control form-control-sm">
                                    <?php
                                        $organizations = \UCRM\REST\Endpoints\Organization::get()->toArray();

                                        /** @var \UCRM\REST\Endpoints\Organization $organization */
                                        foreach ($organizations as $key => $organization) {

                                            printf('<option value="%d" %s>%s</option>',
                                                $organization->getId(),
                                                $organization->getSelected() ? "selected" : "",
                                                $organization->getName()
                                            );
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <label class="mb-0" for="frm-since">Since:</label>
                                <input type="date" name="since" id="frm-since" placeholder="YYYY-MM-DD" class="form-control form-control-sm" value="<?php echo htmlspecialchars($result['since'] ?? '', ENT_QUOTES); ?>">
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <label class="mb-0" for="frm-until">Until:</label>
                                <input type="date" name="until" id="frm-until" placeholder="YYYY-MM-DD"
                                       class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($result['until'] ?? '', ENT_QUOTES); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <button id="btn-submit" type="submit" class="btn btn-primary btn-sm btn-block">Generate</button>
                                <span id="btn-loading" class="d-none btn btn-primary btn-sm btn-block disabled">
                                        Generating...
                                    </span>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Services</h5>

                    <span id="test">Test</span>

                    <?php




                    $servicePlans = \UCRM\REST\Endpoints\ServicePlan::get()->toArray();

                    foreach($servicePlans as $servicePlan) {
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex flex-row justify-content-between align-items-center">
                                <div>
                                    <span class="mr-2">W-00768</span>
                                    <i class="fas fa-chevron-circle-right" id="service-plan-01-dropdown"></i>
                                </div>
                                <span><strong>$100.00</strong></span>
                            </div>
                        </div>

                        <?php


                        ?>

                        <div id="service-plan-01-content" class="card-body">
                            <div class="card-text">
                                <div class="d-flex flex-row justify-content-between align-items-center">
                                    <span>Donald Trump</span>
                                    <span>$100.00</span>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php
                    }
                    ?>



                </div>
            </div>
        </div>
    </div>

</div>



<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.bundle.min.js"
        integrity="sha384-zDnhMsjVZfS3hiP7oCBRmfjkQC4fzxVxFhBx8Hkz2aZX8gEvA/jsP3eXRCvzTofP"
        crossorigin="anonymous">
</script>

<script>

    function pad(string, width, char) {
        char = char || "0";
        string = string + "";
        return string.length >= width ? string : new Array(width - string.length + 1).join(char) + string;
    }


    $(function() {

        let today = new Date();
        let since = today.getFullYear() + "-" + pad(today.getMonth() + 1, 2) + "-" + pad(today.getDate(), 2);
        let until = today.getFullYear() + "-" + pad(today.getMonth() + 1, 2) + "-" + pad(today.getDate(), 2);

        $("#frm-since").val(since);
        $("#frm-until").val(until);

    });

    $("#btn-submit").on("click", function(e) {

        e.preventDefault();

        let organizationId  = $("#frm-organization").val();
        let since           = $("#frm-since").val();
        let until           = $("#frm-until").val();


        let response = $.get("public.php?/generator.php", {

            "frm-organization": organizationId,
            "frm-since": since,
            "frm-until": until

        }, function(data) {

            console.log(data);

        })


    });




</script>

</body>
</html>