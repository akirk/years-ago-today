<?php
/**
 * Tests for `get_resized_content()`.
 */

class Test_Years_Ago_Today_Resized_Content extends WP_UnitTestCase {

	private $attachment_id;
	private $ref;
	private $post_id;

	public function setUp(): void {
		parent::setUp();

		// Add custom image size.
		add_image_size( 'yat-email', 600, 300 );

		// Copy test image into current Y/m uploads subdirectory.
		$upload_dir = wp_upload_dir();
		$subdir     = $upload_dir['path'];
		wp_mkdir_p( $subdir );

		$basename = 'test-image.jpg';
		$src_file = $subdir . '/' . $basename;
		copy( DIR_TESTDATA . '/images/canola.jpg', $src_file );

		// Create attachment post.
		$this->attachment_id = $this->factory->attachment->create_object( array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Canola',
			'guid'           => $upload_dir['url'] . '/' . $basename, // temp; fixed later
		) );

		// Create metadata for sample image sizes.
		$sizes = array(
			'thumbnail' => array( 150, 150 ),
			'medium'    => array( 300, 225 ),
			'yat-email' => array( 600, 300 ),
		);

		$meta = array(
			'width'  => 1600,
			'height' => 800,
			'file'   => trailingslashit( wp_basename( $upload_dir['subdir'] ) ) . $basename,
			'sizes'  => array(),
		);

		foreach ( $sizes as $key => $dims ) {
			$meta['sizes'][ $key ] = array(
				'file'      => 'test-image-' . $dims[0] . 'x' . $dims[1] . '.jpg',
				'width'     => $dims[0],
				'height'    => $dims[1],
				'mime-type' => 'image/jpeg',
			);
		}

		wp_update_attachment_metadata( $this->attachment_id, $meta );
		update_post_meta( $this->attachment_id, '_wp_attached_file', $meta['file'] );
		update_post_meta( $this->attachment_id, '_wp_attachment_image_alt', 'Canola' );

		// Generate zero-byte placeholder files so file_exists() passes.
		$abs_dir = trailingslashit( $upload_dir['basedir'] ) . dirname( $meta['file'] );
		wp_mkdir_p( $abs_dir );

		foreach ( array_keys( $sizes ) as $key ) {
			$dummy = $abs_dir . '/' . $meta['sizes'][ $key ]['file'];
			if ( ! file_exists( $dummy ) ) {
				touch( $dummy );
			}
		}

		// Fix the attachment GUID so URL->ID mapping works.
		wp_update_post( array(
			'ID'   => $this->attachment_id,
			'guid' => trailingslashit( $upload_dir['baseurl'] ) . $meta['file'],
		) );

		// Reflection for the private helper.
		$rm = new ReflectionMethod( 'c2c_YearsAgoToday', 'get_resized_content' );
		$rm->setAccessible( true );
		$this->ref = $rm;

		// Create dummy post.
		$this->post_id = $this->factory->post->create();
	}

	public function tearDown(): void {
		// Remove all test image atttachments.
		if ( $this->attachment_id ) { 
			wp_delete_attachment( $this->attachment_id, true );
		}

		remove_image_size( 'yat-email' );

		parent::tearDown();
	}


	//
	// HELPER FUNCTIONS
	//


	private function run_helper_on( $html ) {
		wp_update_post(
			array(
				'ID'           => $this->post_id,
				'post_content' => $html,
			)
		);

		$GLOBALS['post'] = get_post( $this->post_id );

		return $this->ref->invoke( null, 'medium' );
	}

	private function get_attachment_src( $attachment_id, $size = 'medium' ) {
		$meta       = wp_get_attachment_metadata( $attachment_id );
		$uploads    = wp_upload_dir();
		$attach_rel = dirname( $meta['file'] ) . '/' . $meta['sizes'][ $size ]['file'];
		return trailingslashit( $uploads['baseurl'] ) . ltrim( $attach_rel, '/' );
	}


	//
	//
	// TESTS
	//
	//


	public function test_inserts_alt_when_missing() {
		$html = sprintf(
			'<img src="%s" />',
			wp_get_attachment_url( $this->attachment_id )
		);
		$out = $this->run_helper_on( $html );

		$this->assertStringContainsString( 'alt="Canola"', $out );
	}

	public function test_no_duplicate_alt() {
		$html = sprintf(
			'<img src="%s" alt="Original" />',
			wp_get_attachment_url( $this->attachment_id )
		);
		$out = $this->run_helper_on( $html );

		$this->assertSame( 1, substr_count( $out, 'alt="' ) );
	}

	public function test_original_alt_is_retained() {
		$html = sprintf(
			'<img src="%s" alt="Original" />',
			wp_get_attachment_url( $this->attachment_id )
		);
		$out = $this->run_helper_on( $html );

		$this->assertStringContainsString( ' alt="Original" ', $out );
	}

	public function test_default_size_is_medium() {
		$html = sprintf(
			'<img src="%s" />',
			$this->get_attachment_src( $this->attachment_id )
		);
		$out = $this->run_helper_on( $html );

		$this->assertStringContainsString(
			esc_attr( $this->get_attachment_src( $this->attachment_id ) ),
			$out,
			'Default size "medium" should replace src attribute when no filter is set.'
		);
	}

	public function test_filter_overrides_to_thumbnail() {
		add_filter( 'c2c_years_ago_today-html_email_image_size', fn() => 'thumbnail' );

		$html = sprintf(
			'<img src="%s" />',
			$this->get_attachment_src( $this->attachment_id, 'thumbnail' )
		);
		$out = $this->run_helper_on( $html );

		remove_all_filters( 'c2c_years_ago_today-html_email_image_size' );

		$this->assertStringContainsString(
			esc_attr( $this->get_attachment_src( $this->attachment_id, 'thumbnail' ) ),
			$out,
			'Filtered size "thumbnail" should replace src attribute.'
		);
	}

	public function test_invalid_filter_falls_back_to_medium() {
		add_filter( 'c2c_years_ago_today-html_email_image_size', fn() => 'not-a-size' );

		$html = sprintf(
			'<img src="%s" />',
			$this->get_attachment_src( $this->attachment_id, 'medium' )
		);
		$out = $this->run_helper_on( $html );

		remove_all_filters( 'c2c_years_ago_today-html_email_image_size' );

		$this->assertStringContainsString(
			esc_attr( $this->get_attachment_src( $this->attachment_id, 'medium' ) ),
			$out,
			'Default size "medium" should replace src attribute when filter returns invalid size.'
		);
	}

	public function test_multiline_img_tag_is_resized() {
		$orig_url = wp_get_attachment_url( $this->attachment_id );

		// Craft an <img> with a line-break before src=
		$html = sprintf(
			"<p><img class=\"foo\" \n src=\"%s\" /></p>",
			esc_url( $orig_url )
		);

		$out = $this->run_helper_on( $html );

		$this->assertStringContainsString(
			esc_attr( $this->get_attachment_src( $this->attachment_id, 'medium' ) ),
			$out,
			'Helper should replace src even when the <img> tag spans multiple lines.'
		);
	}

	public function test_srcset_and_sizes_are_injected() {
		$orig = wp_get_attachment_url( $this->attachment_id );
		$out  = $this->run_helper_on( '<img src="' . esc_url( $orig ) . '" />' );

		$this->assertStringContainsString( 'srcset=', $out );
		$this->assertStringContainsString( 'sizes=',  $out );
	}

	public function test_width_height_added() {
		$orig = wp_get_attachment_url( $this->attachment_id );
		$out  = $this->run_helper_on( '<img src="' . esc_url( $orig ) . '" />' );

		$this->assertStringContainsString( 'width="300"',  $out );
		$this->assertStringContainsString( 'height="225"', $out );
	}

	public function test_width_height_updated_to_new_size() {
		$orig = wp_get_attachment_url( $this->attachment_id );
		$out  = $this->run_helper_on( '<img src="' . esc_url( $orig ) . '" />' );

		$this->assertStringContainsString( 'width="300"',  $out );
		$this->assertStringContainsString( 'height="225"', $out );
	}


	public function test_no_width_height_srcset_sizes_added_for_external_image() {
		$out  = $this->run_helper_on( '<img src="https://example.com/images/test.jpb" />' );

		$this->assertStringNotContainsString( 'alt="',    $out );
		$this->assertStringNotContainsString( 'sizes="',  $out );
		$this->assertStringNotContainsString( 'srcset="', $out );
		$this->assertStringNotContainsString( 'width="',  $out );
		$this->assertStringNotContainsString( 'height="', $out );
	}

	public function test_width_height_not_updated_for_external_image() {
		$out  = $this->run_helper_on( '<img src="https://example.com/images/test.jpb" alt="External" width="1200" height="900"/>' );

		$this->assertStringContainsString( 'alt="External"', $out );
		$this->assertStringContainsString( 'width="1200"',   $out );
		$this->assertStringContainsString( 'height="900"',   $out );
	}

}
