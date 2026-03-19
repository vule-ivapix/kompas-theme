<?php
/**
 * Rank Math integration for Kompas custom post authors.
 *
 * @package Kompas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'kompas_get_rank_math_author_data' ) ) {
	/**
	 * Resolve the author data used by Rank Math meta and schema.
	 *
	 * Priority:
	 * 1. CPT author from kompas_author_id.
	 * 2. Manual author name from kompas_custom_author.
	 * 3. Fallback to Rank Math / WordPress defaults.
	 *
	 * @param int $post_id Optional post ID.
	 * @return array{name?: string, url?: string}
	 */
	function kompas_get_rank_math_author_data( $post_id = 0 ) {
		if ( ! is_singular( 'post' ) ) {
			return array();
		}

		$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return array();
		}

		$author_id_meta_key     = defined( 'KOMPAS_AUTHOR_ID_META_KEY' ) ? KOMPAS_AUTHOR_ID_META_KEY : 'kompas_author_id';
		$custom_author_meta_key = defined( 'KOMPAS_CUSTOM_AUTHOR_META_KEY' ) ? KOMPAS_CUSTOM_AUTHOR_META_KEY : 'kompas_custom_author';
		$cpt_author_id          = (int) get_post_meta( $post_id, $author_id_meta_key, true );

		if ( $cpt_author_id > 0 ) {
			$cpt_author = get_post( $cpt_author_id );
			if ( $cpt_author instanceof WP_Post && 'kompas_autor' === $cpt_author->post_type && 'publish' === $cpt_author->post_status ) {
				$author_name = trim( (string) get_the_title( $cpt_author ) );
				if ( '' !== $author_name ) {
					$author_data = array(
						'name' => $author_name,
					);

					$author_url = get_permalink( $cpt_author );
					if ( is_string( $author_url ) && '' !== $author_url ) {
						$author_data['url'] = $author_url;
					}

					return $author_data;
				}
			}
		}

		$custom_author = trim( (string) get_post_meta( $post_id, $custom_author_meta_key, true ) );
		if ( '' !== $custom_author ) {
			return array(
				'name' => $custom_author,
			);
		}

		return array();
	}
}

if ( ! function_exists( 'kompas_filter_rank_math_social_author' ) ) {
	/**
	 * Override Rank Math social author output when a Kompas author is set.
	 *
	 * @param string $content Existing Rank Math author content.
	 * @return string
	 */
	function kompas_filter_rank_math_social_author( $content ) {
		$author_data = kompas_get_rank_math_author_data();

		if ( empty( $author_data['name'] ) ) {
			return $content;
		}

		return $author_data['name'];
	}
}
add_filter( 'rank_math/opengraph/facebook/article:author', 'kompas_filter_rank_math_social_author' );
add_filter( 'rank_math/opengraph/twitter/creator', 'kompas_filter_rank_math_social_author' );

if ( ! function_exists( 'kompas_get_rank_math_schema_author' ) ) {
	/**
	 * Build the schema author payload for Rank Math article entities.
	 *
	 * @return array
	 */
	function kompas_get_rank_math_schema_author() {
		$author_data = kompas_get_rank_math_author_data();

		if ( empty( $author_data['name'] ) ) {
			return array();
		}

		$schema_author = array(
			'@type' => 'Person',
			'name'  => $author_data['name'],
		);

		if ( ! empty( $author_data['url'] ) ) {
			$schema_author['url'] = $author_data['url'];
		}

		return $schema_author;
	}
}

if ( ! function_exists( 'kompas_filter_rank_math_article_entity' ) ) {
	/**
	 * Override Rank Math article rich snippet author.
	 *
	 * @param array $entity Article schema entity.
	 * @return array
	 */
	function kompas_filter_rank_math_article_entity( $entity ) {
		$schema_author = kompas_get_rank_math_schema_author();

		if ( empty( $schema_author ) || ! is_array( $entity ) ) {
			return $entity;
		}

		$entity['author'] = $schema_author;

		return $entity;
	}
}
add_filter( 'rank_math/snippet/rich_snippet_article_entity', 'kompas_filter_rank_math_article_entity' );

if ( ! function_exists( 'kompas_filter_rank_math_json_ld_author' ) ) {
	/**
	 * Override Rank Math JSON-LD author on article-like entities.
	 *
	 * @param array $data   JSON-LD data.
	 * @param mixed $jsonld Rank Math JSON-LD instance.
	 * @return array
	 */
	function kompas_filter_rank_math_json_ld_author( $data, $jsonld ) {
		$schema_author = kompas_get_rank_math_schema_author();

		if ( empty( $schema_author ) || ! is_array( $data ) ) {
			return $data;
		}

		unset( $jsonld );

		$article_types = array( 'Article', 'NewsArticle', 'BlogPosting' );

		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
				continue;
			}

			$entity_types = is_array( $entity['@type'] ) ? $entity['@type'] : array( $entity['@type'] );
			if ( empty( array_intersect( $entity_types, $article_types ) ) ) {
				continue;
			}

			$data[ $key ]['author'] = $schema_author;
		}

		return $data;
	}
}
add_filter( 'rank_math/json_ld', 'kompas_filter_rank_math_json_ld_author', 99, 2 );
