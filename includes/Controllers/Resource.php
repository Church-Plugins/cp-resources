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
		return $this->filter( get_the_title( $this->post->ID ), __FUNCTION__ );
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
		$url = $this->resource_url;

		if ( empty( $url ) ) {
			$url = get_permalink( $this->post->ID );
		}

		return $this->filter( $url, __FUNCTION__ );
	}

	public function get_icon() {}

	public function get_file_type() {
		if ( $file_id = $this->resource_url_id ) {

		}
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
			try {
			} catch( Exception $e ) {
				error_log( $e );
			}
		}

		return $this->filter( $thumb, __FUNCTION__ );
	}

	public function get_publish_date() {
		if ( $date = get_post_datetime( $this->post, 'date', 'gmt' ) ) {
			$date = $date->format( 'U' );
		}

		return $this->filter( $date, __FUNCTION__ );
	}

	public function get_topics() {
		$return = [];
		$terms  = get_the_terms( $this->post->ID, cp_resources()->setup->taxonomies->topic->taxonomy );

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$return[ $term->slug ] = [
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
				$return[ $term->slug ] = [
					'name' => $term->name,
					'slug' => $term->slug,
					'url'  => get_term_link( $term )
				];
			}
		}

		return $this->filter( $return, __FUNCTION__ );
	}

}
