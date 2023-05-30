<?php

namespace CP_Resources\Controllers;

use ChurchPlugins\Controllers\Controller;
use ChurchPlugins\Helpers;
use CP_Resources\Admin\Settings;
use CP_Resources\Exception;
use CP_Resources\Models\Item as ItemModel;
use CP_Resources\Models\ServiceType;
use CP_Resources\Models\Speaker;
use CP_Resources\Util\Convenience;

class Resource extends Controller{

	/**
	 * Update construct to default to use resource_id
	 *
	 * @param $id
	 * @param $use_origin
	 *
	 * @throws \ChurchPlugins\Exception
	 */
	public function __construct( $id, $use_origin = false ) {
		return parent::__construct( $id, $use_origin );
	}

	public function get_content( $raw = false ) {
		$content = get_the_content( null, false, $this->post );
		if ( ! $raw ) {
			$content = apply_filters( 'the_content', $content );
		}

		return $this->filter( $content, __FUNCTION__ );
	}

	public function get_title() {
		$title = get_the_title( $this->post->ID );

		// if we are on a single item that is not a Resource, remove the item's title from the Resource name
		if ( is_singular() && ! is_singular( cp_resources()->setup->post_types->resource->post_type ) ) {
			$object_title = get_the_title( get_queried_object_id() );
			$title = str_replace( ' - ' . $object_title, '', $title );
			$title = str_replace( ' – ' . $object_title, '', $title );
			$title = str_replace( ' &#8211 ' . $object_title, '', $title );
			$title = str_replace( $object_title . ' - ', '', $title );
			$title = str_replace( $object_title . ' – ', '', $title );
			$title = str_replace( $object_title . ' &#8211; ', '', $title );
		}

		return $this->filter( htmlspecialchars_decode( $title, ENT_QUOTES | ENT_HTML401 ), __FUNCTION__ );
	}

	/**
	 * Get the resource_url for this item. Return permalink if url is not specified.
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/22/23
	 */
	public function get_url() {
		$url = $this->model->get_meta_value( 'resource_url' );

		if ( empty( $url ) ) {
			$url = get_permalink( $this->post->ID );
		}

		return $this->filter( $url, __FUNCTION__ );
	}

	/**
	 * Get the icon for this resource
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/29/23
	 */
	public function get_icon() {
		list( $type, $ext, $test ) = explode( '/', $this->get_file_type() );

		$icon = 'link';

		if ( in_array( $type, [ 'audio', 'video' ] ) ) {
			$icon = $type;
		}

		if ( in_array( $ext, [ 'doc', 'pdf' ] ) ) {
			$icon = $ext;
		}

		ob_start();
		cp_resources()->templates->get_template_part( "icons/$icon.svg" );
		$icon = ob_get_clean();

		return $this->filter( $icon, __FUNCTION__ );
	}

	/**
	 * Get the file type for this resource
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/29/23
	 */
	public function get_file_type() {
		$type = Helpers::get_file_type( $this->model->get_meta_value( 'resource_url' ) );
		return $this->filter( $type, __FUNCTION__ );
	}

	/**
	 * Get thumbnail
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_thumbnail() {
		if ( $thumb = get_the_post_thumbnail_url( $this->post->ID ) ) {
			return $this->filter( $thumb, __FUNCTION__ );
		}

		if ( ! $thumb && ! empty( $this->get_types() ) ) {
			foreach( $this->get_types() as $id => $type ) {
				$thumb = get_term_meta( $id, 'cp_resource_type_thumbnail', true );

				if ( $thumb_id =  get_term_meta( $id, 'cp_resource_type_thumbnail_id', true ) ) {
					$image = wp_get_attachment_image_src( $thumb_id, 'large' );
					if ( ! empty( $image[0] ) ) {
						$thumb = $image[0];
					}
				}

				break;
			}
		}

		return $this->filter( $thumb, __FUNCTION__ );
	}

	public function get_publish_date( $relative = true, $format = false ) {
		$format = ! empty( $format ) ? $format : get_option( 'date_format' );

		if ( $date = get_post_datetime( $this->post, 'date', 'gmt' ) ) {
			$date = $date->format( 'U' );
		}

		$date = $relative ? Helpers::relative_time( $date ) : date( $format, $date );

		return $this->filter( $date, __FUNCTION__ );
	}

	public function get_topics() {
		$return = [];
		$terms  = get_the_terms( $this->post->ID, cp_resources()->setup->taxonomies->topic->taxonomy );

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$return[ $term->term_id ] = [
					'name' => $term->name,
					'slug' => $term->slug,
					'url'  => get_term_link( $term )
				];
			}
		}

		return $this->filter( $return, __FUNCTION__ );
	}

	public function get_types() {
		$return = [];
		$terms  = get_the_terms( $this->post->ID, cp_resources()->setup->taxonomies->type->taxonomy );

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$return[ $term->term_id ] = [
					'name' => $term->name,
					'slug' => $term->slug,
					'url'  => get_term_link( $term )
				];
			}
		}

		return $this->filter( $return, __FUNCTION__ );
	}

	/**
	 * Get the label for the first Type
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/29/23
	 */
	public function get_type_label() {
		$types = $this->get_types();
		$label = '';

		if ( ! empty( $types[0] ) ) {
			$label = $types[0]['name'];
		}

		return $this->filter( $label, __FUNCTION__ );
	}

}
