$(document).ready(function(){
$('#menu').slicknav({
label:'',
closeOnClick:true,
allowParentLinks:true
});
});

// close menu on outside click
document.addEventListener('touchstart', onDocumentTouchStart, false);
function onDocumentTouchStart(event) {
if (event.touches[0] && event.touches[0].target.tagName.toLowerCase() == "div") {
$("#menu").slicknav('close');}
}

// hide menu items when form inputs are active
$(document).on('focus', 'textarea,input,select', function() {
$('.slicknav_menu').css('display','none');
$('#mlogo').css('display','none');
$('#mnumber').css('display','none');
}).on('blur', 'textarea,input,select', function() {
$('.slicknav_menu').css('display','');
$('#mlogo').css('display','');
$('#mnumber').css('display','');
});