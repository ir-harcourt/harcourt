scscpq_version="20231121";
scscpq_url=window.location.origin + "/scscpq_wp.php";
jQuery(document).ready(function() {
	scscpq_event('initial');
});
jQuery(window).on('message', function(e) {
	var event=e.originalEvent;
	if ( (typeof event.data !== "object") || (!event.data.scscpq) ) return;
	switch (event.data.scscpq) {
      case "resize":
	    height=( (event.data.data) ? event.data.data : document.getElementById(event.data.id).contentWindow.document.body.scrollHeight);
	    document.getElementById(event.data.id).style.height=height + 40 + "px";
        break;
      case "cart_size":
      	jQuery("#" + event.data.id).html(event.data.data);
      	break;
	}
});
function scscpq_ipc(id, action, data) {
	window.parent.postMessage({'scscpq': action, 'token': sessionStorage['scscpq_token'], 'id': id, 'data': data},'*');
}
function scscpq_event(action) {
	scscpq_page=jQuery("#scscpq_page").html();
	scscpq_document=jQuery("#scscpq_document").html();

	scscpq_spinner=new Array();
	scscpq_spinner.push("<style>");
	scscpq_spinner.push(".scscpq_loader {display: inline-block; border: 10px solid #f3f3f3; border-radius: 50%; border-top: 10px solid #DC2536; width: 40px; height: 40px; -webkit-animation: spin 2s linear infinite; animation: spin 2s linear infinite; }");
	scscpq_spinner.push(".scscpq_loader_text { display: inline-block; vertical-align: top; margin-left: 10px; margin-top: 18px; }");
	scscpq_spinner.push("@-webkit-keyframes spin { 0% { -webkit-transform: rotate(0deg); } 100% { -webkit-transform: rotate(360deg); } }");
	scscpq_spinner.push("@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }");
	scscpq_spinner.push("</style>");
	scscpq_spinner.push("<div style='margin-top: 50px;margin-bottom: 50px; text-align: center;'><div class=scscpq_loader></div><div class=scscpq_loader_text>Loading ...</div></div>");

	jQuery.when(
    	jQuery("#scscpq_page").html(scscpq_spinner.join('\n'))
      ).then(
    );

	jQuery.ajax({
		url: scscpq_url,
		data: {
        	action: action,
            page: scscpq_page,
            document: scscpq_document,
            language: jQuery(".wpml-ls-native").html(),
            token: sessionStorage['scscpq_token'],
            url: window.location.href
        },
        type: "post",
	    dataType: "json",
	    success: function(response) {
	        for (var key in response) {
            	value=response[key];
				switch (true) {
                  case (key == "token"):
                  	sessionStorage['scscpq_token']=value;
                  	break;
                  case (key.substring(0,6) != "scscpq"):
                  	break;
                  case ( (key == "scscpq_document") && (value) ):
					jQuery("#scscpq_document").load(value);
                  	break;
                  case (key == "scscpq_document"):
					jQuery("#scscpq_document").html('');
                  	break;
                  case (key == "scscpq_link"):
					jQuery("#scscpq_page").load(value);
                  	break;
                  case (key == "scscpq_page"):
                  case (key == "scscpq_iframe"):
                 	jQuery("#scscpq_page").html(value);
                    break;
                  case (value == 0):
                	jQuery("." + key).hide();
                  	break;
                  default:
                	jQuery("." + key).show();
                    if (value != 1) jQuery("." + key).html(value);

                }
			}
        },
        error: function(xhr, response) {
			jQuery("#scscpq_status").html(xhr.responseText);
        }
	});
}

function ajax_fill(table,data) {
	for (var key in data) {
	    field="#ajax_" + table + "_" + key;
        console.log(field + " " + jQuery(field).prop('type') );
	    switch ( jQuery(field).prop('type') ) {
          case "undefined":
          	break;
	      case "text":
	      case "textarea":
	      case "select-one":
	        jQuery(field).val(data[key]);
	        break;
	      case "checkbox":
	        jQuery(field).prop("checked", (data[key]) ? true : false);
	        break;
	      default:
	        jQuery(field).html(data[key]);
	    }
	}
}
function ajax_portal(action) {
	jQuery.ajax({
	    url: "/portal_ajax.php",
	    dataType: 'json',
	    data: {action: action},
	    success:function(data){
	        ajax_fill('',data)
	    }

    });
}
