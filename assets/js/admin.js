(function($){

	$(document).ready(function() {
		var $is_resource = $('#_is_resource');
		var $body = $('body');

		// resources are always resources
		if ( $body.hasClass('post-type-cp_resource') ) {
			$body.addClass('is-resource');
		}

		if ( ! $is_resource.length ) {
			return;
		}


		if( $is_resource.prop('checked') ) {
			$body.addClass( 'is-resource' );
		}

		$is_resource.on('change', function () {
			if ($is_resource.prop('checked')) {
				$body.addClass('is-resource');
			} else {
				$body.removeClass('is-resource');
			}

		});
	})
})(jQuery)
