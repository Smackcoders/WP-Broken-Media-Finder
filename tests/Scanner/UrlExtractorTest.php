<?php
use Smackcoders\BrokenMediaFinder\Scanner\UrlExtractor;

class UrlExtractorTest extends WP_UnitTestCase
{

	private $extractor;

	public function setUp(): void
	{
		parent::setUp();
		$this->extractor = new UrlExtractor();
	}

	public function test_extract_image_urls_from_img_tag()
	{
		$content = '<img src="' . home_url('/wp-content/uploads/2024/01/photo.jpg') . '" alt="test">';
		$urls = $this->extractor->extract_image_urls($content);
		$this->assertContains(home_url('/wp-content/uploads/2024/01/photo.jpg'), $urls);
	}

	public function test_extract_image_urls_from_srcset()
	{
		$content = '<img src="' . home_url('/wp-content/uploads/photo.jpg') . '" srcset="' . home_url('/wp-content/uploads/photo-300x200.jpg') . ' 300w, ' . home_url('/wp-content/uploads/photo.jpg') . ' 800w">';
		$urls = $this->extractor->extract_srcset_urls(home_url('/wp-content/uploads/photo-300x200.jpg') . ' 300w, ' . home_url('/wp-content/uploads/photo.jpg') . ' 800w');
		$this->assertCount(2, $urls);
	}


	public function test_extract_upload_links_from_anchor()
	{
		$content = '<a href="https://example.com/wp-content/uploads/2024/01/document.pdf">Download</a>';
		$urls = $this->extractor->extract_upload_links($content);
		$this->assertContains('https://example.com/wp-content/uploads/2024/01/document.pdf', $urls);
	}

	public function test_normalize_url_strips_query_string()
	{
		$url = $this->extractor->normalize_url('https://example.com/wp-content/uploads/photo.jpg?v=123');
		$this->assertEquals('https://example.com/wp-content/uploads/photo.jpg', $url);
	}

	public function test_normalize_url_strips_anchor()
	{
		$url = $this->extractor->normalize_url('https://example.com/wp-content/uploads/photo.jpg#section');
		$this->assertEquals('https://example.com/wp-content/uploads/photo.jpg', $url);
	}

	public function test_is_internal_media_url_true()
	{
		$upload_dir = wp_upload_dir();
		$url = $upload_dir['baseurl'] . '/2024/01/photo.jpg';
		$this->assertTrue($this->extractor->is_internal_media_url($url));
	}

	public function test_is_internal_media_url_false_for_external()
	{
		$this->assertFalse($this->extractor->is_internal_media_url('https://external-site.com/photo.jpg'));
	}

	public function test_url_to_file_path()
	{
		$upload_dir = wp_upload_dir();
		$url = $upload_dir['baseurl'] . '/2024/01/photo.jpg';
		$path = $this->extractor->url_to_file_path($url);
		$this->assertStringContainsString($upload_dir['basedir'], $path);
	}
}
