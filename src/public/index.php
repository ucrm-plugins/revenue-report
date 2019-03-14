<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revenue Report</title>

    <!------------------------------------------------------------------------------------------------------------------
    Load Built-In UCRM Styles
    ------------------------------------------------------------------------------------------------------------------->
    <!--suppress HtmlUnknownTarget, SpellCheckingInspection -->
    <link rel="stylesheet" href="/assets/fonts/lato/lato.css?v=2.15.0-beta5">
    <!--suppress HtmlUnknownTarget -->
    <link rel="stylesheet" href="/assets/fonts/ubnt-icon/ubnt-icon.css?v=2.15.0-beta5">
    <!--suppress HtmlUnknownTarget -->
    <link rel="stylesheet" href="/assets/fonts/ucrm-icon/style.css?v=2.15.0-beta5">
    <!--suppress HtmlUnknownTarget -->
    <link rel="stylesheet" href="/assets/vendor/jquery-ui/themes/smoothness/jquery-ui.min.css?v=2.15.0-beta5">
    <!--suppress HtmlUnknownTarget -->
    <link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css?v=2.15.0-beta5">
    <!--suppress HtmlUnknownTarget -->
    <link rel="stylesheet" href="/dist/main.min.css?v=2.15.0-beta5">

    <!------------------------------------------------------------------------------------------------------------------
    Load FontAwesome Styles
    ------------------------------------------------------------------------------------------------------------------->
    <!--suppress SpellCheckingInspection -->
    <link href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
          rel="stylesheet"
          integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
          crossorigin="anonymous">

    <!------------------------------------------------------------------------------------------------------------------
    Load Bootstrap Styles
    ------------------------------------------------------------------------------------------------------------------->
    <!--suppress SpellCheckingInspection -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
          crossorigin="anonymous">

    <!------------------------------------------------------------------------------------------------------------------
    Load Plugin Styles
    ------------------------------------------------------------------------------------------------------------------->
    <link rel="stylesheet" href="?/css/main.css">

</head>

<body>

<!----------------------------------------------------------------------------------------------------------------------
Plugin Header Row
TODO: Convert this to a UI element!
UI::renderHeader("Revenue Report", "ucrm-plugins/revenue-report");
----------------------------------------------------------------------------------------------------------------------->
<div id="header" class="text-center text-sm-left">
    <h1
        class="float-sm-left mr-sm-3 mb-2 mb-sm-0">
        <!-- Plugin Title -->
        Revenue Report
    </h1>
    <a
        class="btn btn-sm btn-outline-secondary"
        href="https://github.com/ucrm-plugins/revenue-report"
        target="_blank">
        <!-- Link to GitHub Repo Button -->
        <img src="?/images/github/logo-32px.png" alt="GitHub" height=16>
    </a>
    <a
        class="btn btn-sm btn-outline-success float-sm-right"
        href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YGDTYH2P6WJNN&source=url"
        target="_blank">
        <!-- PayPal Donations Button -->
        Donate
    </a>
</div>

<!----------------------------------------------------------------------------------------------------------------------
Plugin Content
----------------------------------------------------------------------------------------------------------------------->
<div id="content" class="container-fluid ml-0 mr-0 p-1 p-sm-3">
    <!------------------------------------------------------------------------------------------------------------------
    Revenue Report Filter
    ------------------------------------------------------------------------------------------------------------------->
    <div class="row">
        <div class="col-12">
            <div class="card mb-1 mb-sm-3">
                <div class="card-body">
                    <!--------------------------------------------------------------------------------------------------
                    Filter Title
                    --------------------------------------------------------------------------------------------------->
                    <div class="card-title">
                        <h5>Filter</h5>
                    </div>

                    <!--------------------------------------------------------------------------------------------------
                    Filter Form
                    --------------------------------------------------------------------------------------------------->
                    <form id="report-form">
                        <div class="form-row">
                            <!------------------------------------------------------------------------------------------
                            Filter Organization
                            ------------------------------------------------------------------------------------------->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                                <label
                                    class="mb-0"
                                    for="frm-organization">
                                    Organization:
                                </label>
                                <select
                                    id="frm-organization"
                                    class="form-control form-control-sm mb-2"
                                    name="organization">

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

                                    // Query the database for all organizations.
                                    $organizations = $db->query(
                                    "
                                        SELECT organization_id, selected, name
                                        FROM organization;
                                    "
                                    )->fetchAll();

                                    // Loop through each organization...
                                    foreach ($organizations as $key => $organization)
                                    {
                                        /** @var \UCRM\REST\Endpoints\Organization $organization */

                                        // Add the current organization and flag it as selected when set as the default!
                                        echo
                                            "<option value='{$organization['organization_id']}' ".
                                            ($organization['selected'] ? "selected" : "").">".
                                            $organization['name']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!------------------------------------------------------------------------------------------
                            Filter Since
                            ------------------------------------------------------------------------------------------->
                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <label
                                    class="mb-0"
                                    for="frm-since">
                                    Since:
                                </label>
                                <input
                                    id="frm-since"
                                    class="form-control form-control-sm mb-2"
                                    type="date"
                                    name="since"
                                    placeholder="YYYY-MM-DD"
                                    value="<?php echo htmlspecialchars($result['since'] ?? '', ENT_QUOTES); ?>"
                                />
                            </div>

                            <!------------------------------------------------------------------------------------------
                            Filter Until
                            ------------------------------------------------------------------------------------------->
                            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                <label
                                    class="mb-0"
                                    for="frm-until">
                                    Until:
                                </label>
                                <input
                                    id="frm-until"
                                    class="form-control form-control-sm mb-2"
                                    type="date"
                                    name="until"
                                    placeholder="YYYY-MM-DD"
                                    value="<?php echo htmlspecialchars($result['until'] ?? '', ENT_QUOTES); ?>"
                                />
                            </div>
                        </div>

                        <div class="form-row">

                            <div class="col-12 col-sm-6 col-lg-3 my-2 mb-sm-0 order-sm-2 offset-lg-6 d-flex justify-content-between">

                                <div class="btn-group w-100" role="group" aria-label="Basic example">
                                    <div id="defined-date-day" class="btn btn-sm btn-outline-secondary active">Today</div>
                                    <div id="defined-date-wtd" class="btn btn-sm btn-outline-secondary">WTD</div>
                                    <div id="defined-date-mtd" class="btn btn-sm btn-outline-secondary">MTD</div>
                                    <div id="defined-date-ytd" class="btn btn-sm btn-outline-secondary">YTD</div>
                                </div>

                                <!--
                                <label
                                    class="mb-0 d-none"
                                    for="frm-organization">
                                    Pre-Defined:
                                </label>

                                <select
                                        id="defined-dates"
                                        class="form-control form-control-sm"
                                        name="defined-dates">

                                    <option value="today">Today</option>
                                </select>
                                -->
                            </div>

                            <!------------------------------------------------------------------------------------------
                            Filter Submit
                            ------------------------------------------------------------------------------------------->
                            <div class="col-12 col-sm-6 col-lg-3 mt-2 order-sm-1">
                                <button
                                    id="btn-submit"
                                    class="btn btn-primary btn-sm btn-block"
                                    type="submit">
                                    Generate
                                </button>
                            </div>



                        </div>

                        <div class="form-row" style="padding-left:5px; padding-right:5px;">
                            <!------------------------------------------------------------------------------------------
                            Filter Notices
                            ------------------------------------------------------------------------------------------->
                            <div
                                id="notice"
                                class="alert alert-danger col-12 mt-3 mb-0 d-none"
                                >

                                <!--
                                <div
                                    id="notice-message"
                                    class="align-self-center">

                                </div>
                                -->
                            </div>
                        </div>



                    </form>
                </div>
            </div>
        </div>
    </div>




    <div id="results"></div>





</div>

<!----------------------------------------------------------------------------------------------------------------------
Load jQuery JS Files from UCRM Assets
----------------------------------------------------------------------------------------------------------------------->
<!--suppress HtmlUnknownTarget -->
<script
    type="text/javascript"
    src="/dist/jquery.min.js?v=2.15.0-beta5">
</script>

<!----------------------------------------------------------------------------------------------------------------------
Load Bootstrap JS Files
----------------------------------------------------------------------------------------------------------------------->
<!--suppress SpellCheckingInspection -->
<script
    src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"
    integrity="sha384-xrRywqdh3PHs8keKZN+8zzc5TX0GRTLCcmivcbNJWm2rs5C8PRhcEn3czEjhAO9o"
    crossorigin="anonymous">
</script>

<!----------------------------------------------------------------------------------------------------------------------
Load Chart JS Files
----------------------------------------------------------------------------------------------------------------------->
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js">
</script>

<!----------------------------------------------------------------------------------------------------------------------
Custom JavaScript...
----------------------------------------------------------------------------------------------------------------------->
<script>

    function pad(string, width, char) {
        char = char || "0";
        string = string + "";
        return string.length >= width ? string : new Array(width - string.length + 1).join(char) + string;
    }

    function hideNotice() {

        let $notice = $("#notice");
        let visible = !$notice.hasClass("d-none");

        if(!visible)
            return;

        $notice.slideToggle(250, function() {

            $notice.removeClass("d-flex");
            $notice.addClass("d-none");

            $notice.html("");

            // Remove any previous alert-* classes!
            $notice.removeClass (function (index, className) {
                return (className.match (/(^|\s)alert-\S+/g) || []).join(' ');
            });

        });

    }

    function showNotice(message, bsColor = "danger") {

        if(message === undefined || message === null || message === "")
            return;

        let $notice = $("#notice");
        let visible = !$notice.hasClass("d-none");

        // TODO: Handle checking for only viable Bootstrap color classes here?

        // Add the provided class.
        $notice.addClass("alert-" + bsColor);

        // Update the notice.
        $notice.html(message);

        if(!visible) {

            // Toggle visibility!
            $notice.removeClass("d-none");
            $notice.addClass("d-flex");

            $notice.hide();
            $notice.slideToggle();
        }
    }


    $(function() {

        let today = new Date();
        let since = today.getFullYear() + "-" + pad(today.getMonth() + 1, 2) + "-" + pad(today.getDate(), 2);
        let until = today.getFullYear() + "-" + pad(today.getMonth() + 1, 2) + "-" + pad(today.getDate(), 2);

        $("#frm-since").val(since);
        $("#frm-until").val(until);

    });

    let buttonClicked = false;
    let buttonHtml = "";

    $("#btn-submit").on("click", function(e) {

        e.preventDefault();

        if(buttonClicked)
            return;

        buttonClicked = true;

        //console.log("Clicked");

        let organizationId  = $("#frm-organization").val();
        let since           = $("#frm-since").val();
        let until           = $("#frm-until").val();

        //let $notice = $("#notice");
        //hideNotice();

        let $button = $("#btn-submit");
        buttonHtml = $button.html();
        $button.html("<i class='fas fa-spinner fa-spin'></i>");


        $.get("public.php?/generator.php", {

            "frm-organization": organizationId,
            "frm-since": since,
            "frm-until": until

        }, function(data) {

            let $notice = $("#notice");
            let $message = $("#notice-message");

            if(data === null || data === "") {
                showNotice("No results found!", "danger");
            } else {
                hideNotice();
            }

            $("#results").html(data);


        })
            .always(function() {

                let $button = $("#btn-submit");
                $button.html(buttonHtml);

                $button.blur();

                buttonClicked = false;
            });


    });

    $(window).on("resize", function() {

        // Fix for iframe height with header!
        let headerHeight = $("#header").outerHeight();
        let windowHeight = $(window).outerHeight(); // Same as iframe!

        $("#content").css("height", (windowHeight - headerHeight) + "px");


    }).trigger("resize");




    $("div[id^=defined-date-]").on("click", function() {

        // Start by removing the active class from all of the buttons!
        $("div[id^=defined-date-]").each(function() {
            $(this).removeClass("active");
        });


        let $this = $(this);
        let id = $this.attr("id").replace("defined-date-", "");

        let $since = $("#frm-since");
        let $until = $("#frm-until");

        let today = new Date();
        let since = today.getFullYear() + "-" + pad(today.getMonth() + 1, 2) + "-" + pad(today.getDate(), 2);
        let until = today.getFullYear() + "-" + pad(today.getMonth() + 1, 2) + "-" + pad(today.getDate(), 2);

        switch(id) {

            case "day":
                // Already set!
                break;

            case "wtd":
                let wtd = new Date();
                let firstOfWeek = wtd.getDate() - wtd.getDay();
                let firstDay = new Date(wtd.setDate(firstOfWeek));
                since = firstDay.getFullYear() + "-" + pad(firstDay.getMonth() + 1, 2) + "-" + pad(firstDay.getDate(), 2);
                break;

            case "mtd":
                let mtd = new Date();
                since = mtd.getFullYear() + "-" + pad(mtd.getMonth() + 1, 2) + "-" + "01";
                break;

            case "ytd":
                let ytd = new Date();
                since = ytd.getFullYear() + "-" + "01" + "-" + "01";
                break;

            default:
                // Do nothing!
                break;
        }

        $since.val(since);
        $until.val(until);

        $this.addClass("active");

        $("#btn-submit").trigger("click");

    });







</script>

</body>
</html>