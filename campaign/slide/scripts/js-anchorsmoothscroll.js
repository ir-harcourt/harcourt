var $root = $('html, body');
$('a').click(function() {
var href = $.attr(this, 'href');
$root.animate({
scrollTop: $(href).offset().top
//offset top margin
//scrollTop: $(href).offset().top - 200
}, 700, function () {
window.location.hash = href;
});
return false;
});