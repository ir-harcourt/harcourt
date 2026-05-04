jq171(document).ready(function() {
var offset = 200;
var duration = 500;
jq171(window).scroll(function() {
if (jQuery(this).scrollTop() > offset) {
jq171(".scroll-to-top").fadeIn(duration);
} else {
jq171(".scroll-to-top").fadeOut(duration);
}
});
jq171(".scroll-to-top").click(function(event) {
event.preventDefault();
jq171("html, body").animate({scrollTop: 0}, duration);
return false;
})
});