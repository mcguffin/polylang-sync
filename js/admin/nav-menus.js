(function($){
	console.log('Hier!')
	$(document).ready(function(){
		$('#post-body').append( $('#translate-nav-menu') );
		$(document).on('click','#translate-nav-menu button',function(e) {
			e.preventDefault();
			var data = {};
			$('[name^="translate-menu"]').each(function(i) {
				var key;
				try {
					key = $(this).attr('name').match(/translate-menu\[(\w+)\]/)[1];
					data[ key ] = $(this).val();
				} catch(err){}
			})
			document.location.search = $.param(data);
		});
	});
})(jQuery);
