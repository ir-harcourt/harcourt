$( document ).ready(function() {
	var pathname = window.location.pathname;
	$.ajax({
	    url: "/scs_slider.php",
	    data: {pathname: pathname },
	    dataType: 'json',
	    success:function(data){
        	$('#scs_slider_id').html(data['content']);
//			$('.flexslider').removeData("flexslider");
 			$('.flexslider').flexslider();
	   }
	});
});