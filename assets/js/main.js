

var $ = jQuery;
$('.cp-resources-filter--toggle--button').on('click', function(e) {
	e.preventDefault();
	$('.cp-resources-filter--has-dropdown').toggle();
});

$( '.cp-resources-filter--form input[type=checkbox]' ).on( 'change',
	function() {

		// Munge the URL to discard pagination when fiilter options change
		var form = $( this ).parents( 'form.cp-resources-filter--form' );
		var location = window.location;
		var baseUrl = location.protocol + '//' + location.hostname;
		var pathSplit = location.pathname.split( '/' );
		let finalPath = '';

		// Get the URL before the `page` element
		var gotBoundary = false;
		$( pathSplit ).each(
			function(index, token) {

				if( 'page' === token ) {
					gotBoundary = true;
				}
				if( !gotBoundary ) {

					if( '' === token ) {
						if( !finalPath.endsWith( '/' ) ) {
							finalPath += '/';
						}
					} else {
						finalPath += token;
						if( !finalPath.endsWith( '/' ) ) {
							finalPath += '/';
						}
					}

				}
			}
		);
		// Finish and add already-used GET params
		if( !finalPath.endsWith( '/' ) ) {
			finalPath += '/';
		}
		if( location.search && location.search.length > 0 ) {
			finalPath += location.search;
		}
		// Set form property and do it
		$( form ).attr( 'action', baseUrl + finalPath );
		$('.cp-resources-filter--form').submit();
	}
);

$('.cp-resources-filter--has-dropdown a').on( 'click', function(e) {
	e.preventDefault();
	$(this).parent().toggleClass('open');
})

$('.cp-resources--filter--search-input').on('change', function(e) {
	if(e.target.value.length) {
		$(this).attr('name', 'search')
	} else {
		$(this).removeAttr('name')
	}
})
