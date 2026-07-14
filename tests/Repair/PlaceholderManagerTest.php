<?php
use Smackcoders\BrokenMediaFinder\Repair\PlaceholderManager;

class PlaceholderManagerTest extends WP_UnitTestCase {

	public function test_default_placeholder_url() {
		update_option( 'wpbmf_settings', array( 'placeholder_source' => 'default' ) );
		$manager = new PlaceholderManager();
		$url     = $manager->get_placeholder_url();
		$this->assertStringContainsString( 'placeholder.svg', $url );
	}

	public function test_custom_placeholder_url() {
		update_option( 'wpbmf_settings', array(
			'placeholder_source'     => 'custom',
			'custom_placeholder_url' => 'https://example.com/my-placeholder.png',
		) );
		$manager = new PlaceholderManager();
		$url     = $manager->get_placeholder_url();
		$this->assertEquals( 'https://example.com/my-placeholder.png', $url );
	}

	public function test_falls_back_to_default_when_custom_empty() {
		update_option( 'wpbmf_settings', array(
			'placeholder_source'     => 'custom',
			'custom_placeholder_url' => '',
		) );
		$manager = new PlaceholderManager();
		$url     = $manager->get_placeholder_url();
		$this->assertStringContainsString( 'placeholder.svg', $url );
	}
}
