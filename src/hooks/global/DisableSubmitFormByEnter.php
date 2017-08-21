<?php
	class DisableSubmitFormByEnter extends HookSubscription{
		/*
			Disable Submit by Enter key hook.
			Inject JS code to intersect enter press event
		*/
		public function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id){
			print 	"<script>$(document).ready(function() {
						var form_el = $(\"form\").find('input');

						if (form_el){
						form_el.unbind(\"keydown\");
  						form_el.keydown(function(event){
    						if(event.keyCode == 13) {
    							

    							event.cancelBubble = true;
								event.returnValue = false;
      							event.preventDefault();

								//var self = $(this);
								var self = $(this)
								  , form = self.parents('form:eq(0)')
								  , focusable
								  , next
								  , prev
								  ;
      							form = self.parents('form:eq(0)');
								focusable = form.find('input,select,button,textarea').filter(':visible');
								
						        next = focusable.eq(focusable.index(this)+1);
						        if (next.length) {
						            next.focus();
    								$(this).trigger('blur');
						        } else {
						            form.submit();
						        }
						        return false;      							
    						}
  						});
						}
					});</script>";
		}
		//Hook name in UI
		public function hook_name() {
			return "Disable submit form by Enter";
		}
	}

?>