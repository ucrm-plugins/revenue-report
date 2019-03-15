$(window).on("resize", function() {

    // Fix for iframe height with header!
    let headerHeight = $("#header").outerHeight();
    let windowHeight = $(window).outerHeight(); // Same as iframe!

    $("#content").css("height", (windowHeight - headerHeight) + "px");

    $("#notice-container").css("height", headerHeight + "px");

}).trigger("resize");