<?php
?>

<?php if ( have_posts() ) : ?>
	<div class="cp-resources-single">
		<?php do_action( 'cp_resources_before_cpl_single' ); ?>

		<?php while( have_posts() ) : the_post(); ?>
			<?php the_title(); // \CP_Resources\Templates::get_template_part( "parts/$type-single" ); ?>
		<?php endwhile; ?>

		<?php do_action( 'cp_resources_after_cpl_single' ); ?>
	</div>
<?php endif; ?>
