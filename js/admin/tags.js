(function($){

	jQuery(document).ajaxSuccess( function( event, xhr, settings ) {

		var res, parent, term, indent, i, r;

		if ( !!settings && !! settings.data ) {
			var requestData = $.unserialize(settings.data);
			if ( !! requestData.action && requestData.action == "add-tag" ) {
				// should update tag list
			} else if ( /* delete tag */ ) {
			}
		}
	});
})(jQuery)