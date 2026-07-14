<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AttachmentScanner {

	private $extractor;
	private $repository;

	public function __construct( UrlExtractor $extractor, ScanRepository $repository ) {
		$this->extractor  = $extractor;
		$this->repository = $repository;
	}

	public function scan( $scan_id, $post_types = array( 'post', 'page' ) ) {
		$found    = 0;
		$per_page = 50;
		$paged    = 1;

		do {
			$query = new \WP_Query( array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'no_found_rows'  => false,
			) );

			if ( ! $query->have_posts() ) {
				break;
			}

			foreach ( $query->posts as $post ) {
				if ( empty( $post->post_content ) ) {
					continue;
				}

				$links = $this->extractor->extract_upload_links( $post->post_content );
				$seen  = array();

				foreach ( $links as $url ) {
					if ( isset( $seen[ $url ] ) ) {
						continue;
					}
					$seen[ $url ] = true;

					// Skip image URLs — handled by ContentScanner
					if ( preg_match( '/\.(jpe?g|png|gif|webp|avif|svg)$/i', $url ) ) {
						continue;
					}

					$path = $this->extractor->url_to_file_path( $url );
					if ( empty( $path ) ) {
						continue;
					}

					if ( ! file_exists( $path ) ) {
						$this->repository->insert_result( array(
							'scan_id'    => $scan_id,
							'item_type'  => 'post_content_link',
							'issue_type' => 'broken_attachment_url',
							'post_id'    => $post->ID,
							'source_url' => $url,
							'file_path'  => $path,
							'message'    => sprintf(
								/* translators: 1: Post title, 2: Attachment URL. */
								__( 'Broken attachment link in "%1$s": %2$s', 'broken-media-finder' ),
								$post->post_title,
								$url
							),
							'severity'   => 'medium',
						) );
						$found++;
					}
				}
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );

		return $found;
	}
}
