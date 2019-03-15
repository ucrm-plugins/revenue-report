



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

    //$notice.fadeOut(500, function() {
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
        /*
        $notice.fadeIn(500, function() {

            setTimeout(function() {
                hideNotice();
            }, 2500);

        });
        */
        $notice.slideToggle(250);
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