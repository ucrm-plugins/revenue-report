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

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
          crossorigin="anonymous">

    <link rel="stylesheet" href="public/css/main.css">

    <script type="text/javascript" src="/dist/jquery.min.js?v=2.15.0-beta5"></script>

</head>

<body>
<div id="header" class="text-center text-sm-left">
    <h1 class="float-sm-left mr-sm-3 mb-2 mb-sm-0">Revenue Report</h1>
    <a href="https://github.com/ucrm-plugins/revenue-report" target="_blank" class="button button--icon-only">
        <img src="?/images/github/logo-32px.png" alt="GitHub" height=16>
    </a>
</div>



<div id="content" class="container-fluid ml-0 mr-0 p-1 p-sm-3">



    <div class="row">
        <div class="col-12">
            <div class="card mb-1 mb-sm-3">
                <div class="card-body">
                    <div class="card-title">
                        <h5>Filter</h5>
                    </div>
                    <form id="report-form">
                        <div class="form-row mb-2">
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                                <label class="mb-0" for="frm-organization">Organization:</label>
                                <select name="organization" id="frm-organization" class="form-control form-control-sm mb-2">
                                    <?php

                                    // =================================================================================
                                    // DATABASE CONNECTION
                                    // =================================================================================

                                    $host = getenv("POSTGRES_HOST");
                                    $port = getenv("POSTGRES_PORT");
                                    $name = getenv("POSTGRES_DB");
                                    $user = getenv("POSTGRES_USER");
                                    $pass = getenv("POSTGRES_PASSWORD");

                                    $db = \MVQN\Data\Database::connect($host, (int)$port, $name, $user, $pass);

                                    $organizations = $db->query(
                                    "
                                        SELECT organization_id, selected, name
                                        FROM organization;
                                    "
                                    )->fetchAll();

                                    //$organizations = \UCRM\REST\Endpoints\Organization::get()->toArray();

                                    /** @var \UCRM\REST\Endpoints\Organization $organization */
                                    foreach ($organizations as $key => $organization) {
                                        /*
                                        echo
                                            "<option value='{$organization->getID()}' ".
                                            ($organization->getSelected() ? "selected" : "").">".
                                            $organization->getName()."</option>";
                                        */
                                        echo
                                            "<option value='{$organization['organization_id']}' ".
                                            ($organization['selected'] ? "selected" : "").">".
                                            $organization['name']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <label class="mb-0" for="frm-since">Since:</label>
                                <input type="date"
                                       name="since"
                                       id="frm-since"
                                       placeholder="YYYY-MM-DD"
                                       class="form-control form-control-sm mb-2"
                                       value="<?php echo htmlspecialchars($result['since'] ?? '', ENT_QUOTES); ?>">
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <label class="mb-0" for="frm-until">Until:</label>
                                <input type="date"
                                       name="until"
                                       id="frm-until"
                                       placeholder="YYYY-MM-DD"
                                       class="form-control form-control-sm mb-2"
                                       value="<?php echo htmlspecialchars($result['until'] ?? '', ENT_QUOTES); ?>">
                            </div>
                        </div>

                        <div class="form-row align-middle">
                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <button id="btn-submit" type="submit" class="btn btn-primary btn-sm btn-block">Generate</button>
                                <span id="btn-loading" class="d-none btn btn-primary btn-sm btn-block disabled">
                                        Generating...
                                    </span>
                            </div>

                            <div id="notice" class="col-12 col-sm-6 col-md-6 col-lg-6 offset-lg-3 mt-3 mt-sm-0 d-flex justify-content-center justify-content-sm-end">
                                <div id="notice-message" class="align-self-center"></div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <div id="results"></div>





</div>




<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js" integrity="sha384-xrRywqdh3PHs8keKZN+8zzc5TX0GRTLCcmivcbNJWm2rs5C8PRhcEn3czEjhAO9o" crossorigin="anonymous"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js">
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

            $notice = $("#notice");
            $message = $("#notice-message");

            if(data === null || data === "") {
                $notice.addClass("text-danger");
                $message.text("No results found!");
            } else {
                $notice.removeClass("text-danger");
                $message.text("");
            }

            $("#results").html(data);


        });


    });

    $(window).on("resize", function() {

        // Fix for iframe height with header!
        let headerHeight = $("#header").outerHeight();
        let windowHeight = $(window).outerHeight(); // Same as iframe!

        $("#content").css("height", (windowHeight - headerHeight) + "px");


    }).trigger("resize");







</script>

</body>
</html>