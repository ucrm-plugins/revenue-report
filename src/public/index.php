<!DOCTYPE html>
<html lang="en">

    <? /* TODO: Add localization! */ ?>

    <head>

        <meta charset="UTF-8">

        <title>Revenue Report</title>

        <?/*------------------------------------------------------------------------------------------------------------
        Load Built-In UCRM Styles
        ------------------------------------------------------------------------------------------------------------*/?>
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

        <?/*------------------------------------------------------------------------------------------------------------
        Load FontAwesome Styles
        ------------------------------------------------------------------------------------------------------------*/?>
        <!--suppress SpellCheckingInspection -->
        <link href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
              rel="stylesheet"
              integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
              crossorigin="anonymous">

        <?/*------------------------------------------------------------------------------------------------------------
        Load Bootstrap Styles
        ------------------------------------------------------------------------------------------------------------*/?>
        <!--suppress SpellCheckingInspection -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
              rel="stylesheet"
              integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
              crossorigin="anonymous">

        <?/*------------------------------------------------------------------------------------------------------------
        Load Switch Styles
        ------------------------------------------------------------------------------------------------------------*/?>
        <link rel="stylesheet" href="public/css/switch.css">

        <?/*------------------------------------------------------------------------------------------------------------
        Load Plugin Styles
        ------------------------------------------------------------------------------------------------------------*/?>
        <link rel="stylesheet" href="public/css/main.css">

    </head>

    <body>

        <?/*************************************************************************************************************
        Plugin Header Row
        TODO: Convert this to a UI element!
        UI::renderHeader("Revenue Report", "ucrm-plugins/revenue-report");
        *************************************************************************************************************/?>
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

        <?/*************************************************************************************************************
        Plugin Content
        *************************************************************************************************************/?>
        <div id="content" class="container-fluid ml-0 mr-0 p-1 p-sm-3">

            <?/*--------------------------------------------------------------------------------------------------------
            Revenue Report Widget Enable/Disable
            --------------------------------------------------------------------------------------------------------*/?>
            <div class="row">

                <div class="col-12">

                    <div class="card mb-1 mb-sm-3">

                        <div class="card-body">

                            <?/*----------------------------------------------------------------------------------------
                            Widget Title
                            ----------------------------------------------------------------------------------------*/?>
                            <div class="card-title d-flex justify-content-between">
                                <h5>Dashboard Widget</h5>

                                <?php
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
                                ?>

                                <div class="switch switch-sm">
                                    <input
                                        type="checkbox"
                                        class="switch"
                                        id="switch-id"
                                        <?php echo $enabled ? "checked" : ""; ?>
                                    >
                                    <label class="mb-0" for="switch-id"></label>
                                </div>
                            </div>

                            <div>
                                <div class="alert alert-warning mb-0 text-justify">
                                    <strong>IMPORTANT:</strong> The new dashboard widget is currently an experimental
                                    feature, in the sense that it is NOT supported directly by the UCRM Plugin system or
                                    by the Ubiquiti development team.  And while I have tested this code extensively in
                                    both production and development environments, it is worth noting that this feature
                                    directly modifies some of the UCRM templates.<br/>
                                    <br/>
                                    <a
                                        id="read-more-toggle"
                                        data-toggle="collapse"
                                        href="#read-more-contents"
                                        role="button"
                                        aria-expanded="false"
                                        aria-controls="read-more-contents">
                                        Read more...
                                    </a>

                                    <div id="read-more-contents" class="mt-3 collapse in">

                                        <h5>Purpose</h5>
                                        <p>
                                            When the above switch is enabled, a new widget will be added to your UCRM's
                                            <a href="/" target="_parent">Dashboard</a> with the title <strong>Expected
                                            Monthly Revenue</strong>.
                                        </p>

                                        <h5>Permissions</h5>
                                        <p>
                                            This widget with be visible to anyone with <strong>Special Permissions /
                                                View financial information</strong> set to allow in the UCRM's
                                            <a href="/system/security/user-groups" target="_parent">User Groups</a>
                                            settings.
                                        </p>

                                        <h5>Notes</h5>
                                        <p>
                                            - The current implementation of this Dashboard Widget will not persist UCRM
                                            Updates and will need to have the above switch re-enabled upon every single
                                            update to the UCRM.
                                        </p>
                                        <p>
                                            - The current implementation of this Dashboard Widget will not automatically
                                            remove itself from the dashboard upon disabling or removing the plugin.  So,
                                            it is currently necessary to disable the Dashboard Widget using the above
                                            switch prior to disabling or removing the plugin.  Upon stable release of
                                            UCRM v2.16.0, the necessary hooks will be included to automate this entire
                                            process.
                                        </p>
                                        <p>
                                            - Please report any issues regarding this plugin to me directly, via email
                                            at <a href="mailto:rspaeth@mvqn.net" target="_blank">rspaeth@mvqn.net</a>
                                            or at <a href="https://github.com/ucrm-plugins/revenue-report/issues"
                                            target="_blank">https://github.com/ucrm-plugins/revenue-report/issues</a>
                                        </p>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?/*--------------------------------------------------------------------------------------------------------
            Revenue Report Filter
            --------------------------------------------------------------------------------------------------------*/?>
            <div class="row">

                <div class="col-12">

                    <div class="card mb-1 mb-sm-3">

                        <div class="card-body">

                            <?/*----------------------------------------------------------------------------------------
                            Filter Title
                            ----------------------------------------------------------------------------------------*/?>
                            <div class="card-title">
                                <h5>Filter</h5>
                            </div>

                            <?/*----------------------------------------------------------------------------------------
                            Filter Form
                            ----------------------------------------------------------------------------------------*/?>
                            <form id="report-form">

                                <div class="form-row">

                                    <?/*--------------------------------------------------------------------------------
                                    Filter Organization
                                    --------------------------------------------------------------------------------*/?>
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

                                            <?
                                            // =========================================================================
                                            // DATABASE CONNECTION
                                            // =========================================================================

                                            use MVQN\Data\Database;

                                            // Get all of the necessary database information...
                                            $host = getenv("POSTGRES_HOST");
                                            $port = getenv("POSTGRES_PORT");
                                            $name = getenv("POSTGRES_DB");
                                            $user = getenv("POSTGRES_USER");
                                            $pass = getenv("POSTGRES_PASSWORD");

                                            // Attempt to connect to the database.
                                            // NOTE: All database error handling performed inside Database singleton!
                                            $db = Database::connect($host, (int)$port, $name, $user, $pass);

                                            // Query the database for all organizations.
                                            $organizations = $db->query(
                                            "
                                                SELECT organization_id, selected, name
                                                FROM organization;
                                            "
                                            )->fetchAll();

                                            use UCRM\REST\Endpoints\Organization;

                                            // TODO: Determine the need to handle no Organizations setup?

                                            // Loop through each found organization...
                                            foreach ($organizations as $key => $organization)
                                            {
                                                /** @var Organization $organization */

                                                // Add the current organization and mark selected when it's the default!
                                                echo
                                                    "<option value='{$organization['organization_id']}' ".
                                                    ($organization['selected'] ? "selected" : "").">".
                                                    $organization['name']."</option>";
                                            }
                                            ?>
                                        </select>

                                    </div>

                                    <?/*--------------------------------------------------------------------------------
                                    Filter Since
                                    --------------------------------------------------------------------------------*/?>
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

                                    <?/*--------------------------------------------------------------------------------
                                    Filter Until
                                    --------------------------------------------------------------------------------*/?>
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

                                    <?/*--------------------------------------------------------------------------------
                                    Filter Pre-Defined Date Ranges
                                    --------------------------------------------------------------------------------*/?>
                                    <div class="col-12 col-sm-6 col-lg-3 my-2 mb-sm-0 order-sm-2 offset-lg-6 d-flex
                                        justify-content-between">

                                        <div class="btn-group w-100" role="group" aria-label="Basic example">
                                            <!-- Today -->
                                            <div id="defined-date-day" class="btn btn-sm btn-outline-secondary active">
                                                Today
                                            </div>

                                            <!-- Week to Date -->
                                            <div id="defined-date-wtd" class="btn btn-sm btn-outline-secondary">
                                                WTD
                                            </div>

                                            <!-- Month to Date -->
                                            <div id="defined-date-mtd" class="btn btn-sm btn-outline-secondary">
                                                MTD
                                            </div>

                                            <!-- Year to Date -->
                                            <div id="defined-date-ytd" class="btn btn-sm btn-outline-secondary">
                                                YTD
                                            </div>

                                        </div>

                                    </div>

                                    <?/*--------------------------------------------------------------------------------
                                    Filter Submit
                                    --------------------------------------------------------------------------------*/?>
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
                                    <?/*--------------------------------------------------------------------------------
                                    Filter Notices
                                    --------------------------------------------------------------------------------*/?>
                                    <div
                                        id="notice"
                                        class="alert alert-danger col-12 mt-3 mb-0 d-none"
                                        >
                                        <!-- Empty, unless notices are provided dynamically via JavaScript! -->
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?/*----------------------------------------------------------------------------------------------------------------
            Revenue Report Results (AJAX)
            ----------------------------------------------------------------------------------------------------------------*/?>
            <div id="results">
                <!-- Empty, unless there are generated results! -->
            </div>

        </div>

        <?/*********************************************************************************************************************
        Load jQuery JS Files from UCRM Assets
        *********************************************************************************************************************/?>
        <!--suppress HtmlUnknownTarget -->
        <script
            type="text/javascript"
            src="/dist/jquery.min.js?v=2.15.0-beta5">
        </script>

        <?/*********************************************************************************************************************
        Load Bootstrap JS Files
        *********************************************************************************************************************/?>
        <!--suppress SpellCheckingInspection -->
        <script
            src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"
            integrity="sha384-xrRywqdh3PHs8keKZN+8zzc5TX0GRTLCcmivcbNJWm2rs5C8PRhcEn3czEjhAO9o"
            crossorigin="anonymous">
        </script>

        <?/*********************************************************************************************************************
        Load Chart JS Files
        *********************************************************************************************************************/?>
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js">
        </script>

        <?/*********************************************************************************************************************
        Load Common Content Frame JS
        *********************************************************************************************************************/?>
        <script
            src="?/js/iframe.js">
        </script>

        <?/*********************************************************************************************************************
        Load Plugin Specific JS Files...
        *********************************************************************************************************************/?>
        <script
            src="?/js/filter.js">
        </script>

        <?/*********************************************************************************************************************
        Page Specific JavaScript...
        *********************************************************************************************************************/?>
        <script>

            $(function() {

                // Nothing to do here, for now...

            });

            let $switch = $("#switch-id");

            // Handle extra switch clicks here...
            $switch.on("click", function(e) {

                if($switch.hasClass("disabled"))
                    e.preventDefault();

            });

            // Handle toggling the Dashboard Widget on/off...
            $("#switch-id:checkbox").change(function() {

                //let $switch = $(this);
                let checked = this.checked;

                // Disabled the switch before making the request!
                $switch.addClass("disabled");

                $.ajax({
                    url : "?/api/dashboard-widget/" + (this.checked ? "enable" : "disable"),
                    type : "post",
                    success : function(data) {
                        // On success, set the checked state to what it should be.
                        $switch.prop("checked", checked);
                    },
                    error: function() {
                        // On error, set the check state back to what it was.
                        $switch.prop("checked", !checked);
                    },
                    complete: function() {
                        // Re-Enable the switch regardless of the results!
                        $switch.removeClass("disabled");
                    }
                });

            });


            let $readMoreToggle = $("#read-more-toggle");
            let $readMoreContents = $("#read-more-contents");

            $readMoreToggle.on("click", function() {

                if($readMoreContents.hasClass("show"))
                    $readMoreToggle.html("Read more...");
                else
                    $readMoreToggle.html("Read less...");

                // Unselect the link!
                //$readMoreToggle.blur();

            });




        </script>

    </body>

</html>
