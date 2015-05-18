/*
 * imagemeta handlers
*/
	jQuery(document).ready(function() {		
    jQuery('#imagemetas input[type="text"]').addClass("idleField");  
    jQuery('#imagemetas input[type="text"]').focus(function() {  
        jQuery(this).removeClass("idleField").addClass("focusField");  
        if (this.value == this.defaultValue){  
            this.select(); 
        }  
    });  
    jQuery('#imagemetas input[type="text"]').blur(function() {  
        jQuery(this).removeClass("focusField").addClass("idleField");
        var fd = jQuery(this);
        //if (jQuery.trim(fd.val()) == '' || fd.val() == unescape(this.defaultValue)){  
        if (fd.val() == unescape(this.defaultValue)){  
            this.value = (this.defaultValue ? unescape(this.defaultValue) : '');  
            //alert("default value or blank");
        }
        else
        {	var fname = fd.attr('id').split(":");
        	var fval = fd.val();
        	//alert(fname + " > " + fval);
        	updateField(fname,fval);
        }
    });  
});
function updateField(fname,fval){
    showLoading(fname[0]+":"+fname[1]);  		// shows updating gif
	jQuery.post(ajax_object.ajaxurl, {
		action: 'ajax_action',
		fval: fval,
		fname: fname							// query is built in ajax function; returns true/false
	}, function(data) {
		//alert(data); 							// changes default value
		document.getElementById(fname[0]+":"+fname[1]).defaultValue = escape(fval);
		hideLoading(fname[0]+":"+fname[1]);		// hides updating gif
	});
	return;
}
// update indicators
function showLoading(div){
	if(document.getElementById(div)) {
		document.getElementById(div).className = 'updateField';
	}
}
function hideLoading(div){
	if(document.getElementById(div)) {
		document.getElementById(div).className = 'idleField';
	}
}
// copy titles across fields
function copyAcross(fID,mID){
	fval = document.getElementById("post_title:"+fID).value;
	var caption = document.getElementById('post_excerpt:'+fID);
	if(caption) { caption.value = unescape(fval);
				  updateField(["post_excerpt",fID],fval); }
				  
	var description = document.getElementById('post_content:'+fID);
	if(description) { description.value = unescape(fval);
					  updateField(["post_content",fID],fval); }
					  
	var alt = document.getElementById('meta_value:'+mID);
	if(alt) { alt.value = unescape(fval);
			  updateField(["meta_value",mID],fval); }
	return;	
}
