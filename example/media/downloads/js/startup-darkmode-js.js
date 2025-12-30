(function() {
  if (typeof Zepto !== 'undefined') {
    (function($) {
      $("document").ready(function() {
        const darkmode = new Darkmode();
        $(".navigation").find("ul").eq(0).append("<li style=\"padding-right:1em;\"><span id=\"darkmode-toggle-button\" style=\"cursor:pointer;\">🌓</span></li>");
        $("#darkmode-toggle-button").on('click', function(e) { darkmode.toggle(); });
      });
    })(Zepto);
  } else {
    console.error("No Zepto!");
  }
}).call(this);
