<?php
use Smackcoders\BrokenMediaFinder\Scanner\ScanTable;
use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

class ScanRepositoryTest extends WP_UnitTestCase {

	private $repo;
	private $scan_id = 'test-scan-001';

	public function setUp(): void {
		parent::setUp();
		ScanTable::create_table();
		$this->repo = new ScanRepository();
	}

	private function insert( $overrides = array() ) {
		return $this->repo->insert_result( array_merge( array(
			'scan_id'    => $this->scan_id,
			'item_type'  => 'post_content_image',
			'issue_type' => 'missing_image',
			'post_id'    => 1,
			'source_url' => 'https://example.com/wp-content/uploads/test.jpg',
			'file_path'  => '/var/www/html/wp-content/uploads/test.jpg',
			'message'    => 'Missing image in "Test Post".',
			'severity'   => 'high',
		), $overrides ) );
	}

	public function test_insert_and_fetch() {
		$id  = $this->insert();
		$this->assertGreaterThan( 0, $id );
		$row = $this->repo->get_result( $id );
		$this->assertEquals( 'missing_image', $row['issue_type'] );
		$this->assertEquals( 'open', $row['issue_status'] );
	}

	public function test_count_by_issue_type() {
		$this->insert( array( 'issue_type' => 'missing_image' ) );
		$this->insert( array( 'issue_type' => 'missing_image' ) );
		$this->insert( array( 'issue_type' => 'missing_featured_image' ) );

		$this->assertEquals( 2, $this->repo->count_results( array( 'issue_type' => 'missing_image' ) ) );
		$this->assertEquals( 1, $this->repo->count_results( array( 'issue_type' => 'missing_featured_image' ) ) );
	}

	public function test_update_result_status() {
		$id = $this->insert();
		$this->repo->update_result_status( $id, 'placeholder_applied' );
		$row = $this->repo->get_result( $id );
		$this->assertEquals( 'placeholder_applied', $row['issue_status'] );
	}

	public function test_get_scan_summary() {
		$this->insert( array( 'issue_type' => 'missing_image' ) );
		$this->insert( array( 'issue_type' => 'missing_image' ) );
		$this->insert( array( 'issue_type' => 'unused_media' ) );

		$summary = $this->repo->get_scan_summary( $this->scan_id );
		$this->assertEquals( 3, $summary['total'] );
		$this->assertEquals( 2, $summary['missing_image'] );
		$this->assertEquals( 1, $summary['unused_media'] );
	}

	public function test_get_latest_scan_id() {
		$this->insert();
		$latest = $this->repo->get_latest_scan_id();
		$this->assertEquals( $this->scan_id, $latest );
	}

	public function test_clear_all_results() {
		$this->insert();
		$this->insert();
		$this->repo->clear_all_results();
		$this->assertEquals( 0, $this->repo->count_results() );
	}

	public function test_delete_result() {
		$id = $this->insert();
		$this->repo->delete_result( $id );
		$this->assertNull( $this->repo->get_result( $id ) );
	}

	public function test_delete_results_by_scan_id() {
		$this->insert();
		$this->insert();
		$this->repo->delete_results_by_scan_id( $this->scan_id );
		$this->assertEquals( 0, $this->repo->count_results( array( 'scan_id' => $this->scan_id ) ) );
	}
}
