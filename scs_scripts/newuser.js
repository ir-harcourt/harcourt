function fn_newuser(action) {
	var data=new Array();
	var obj=jQuery(".scscpq_input");
	for (var key in obj) {
		if ( (!obj.hasOwnProperty(key)) || (!obj[key].id) ) continue;
		data.push( {id: obj[key].id, value: jQuery("#" + obj[key].id).val() } );
	}
	jQuery.ajax({
    	url: "/newuser.php",
        type: "post",
	    dataType: "json",
		data: { action: action, return_url: jQuery("#return_url").val(), registered: jQuery("#registered").prop('checked'), data: data },
	    success: function(response) {
        	if (response['scscpq_content']) {
                jQuery("#scscpq_content").html(response['scscpq_content']);
	            for (var key in response) {
                	if ( key.substring(0, 1) == ".") {
                    	if (response[key]==0) {
                        	jQuery(key).hide();
						  } else {
                        	jQuery(key).show();
                        }
					}                        
                }
              } else {
	            for (var key in response) {
	                jQuery("#" + key).html(response[key]);
	            }
			}
        },
        error: function(xhr, response) {
        	console.log(xhr);
			jQuery("#scscpq_input_error").html(xhr.responseText);
        }
	});
}
function fn_registered() {
	if ( jQuery("#registered").prop('checked') ) {
    	jQuery(".company_registered").hide();
	  } else {
    	jQuery(".company_registered").show();
    }
}
