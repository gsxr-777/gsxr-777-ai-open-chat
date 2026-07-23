<?php
/**
 * Database-backed mini-RAG tests.
 */

class GSXR_777_Knowledge_Test extends WP_UnitTestCase {

    public function test_knowledge_document_is_saved_and_retrieved_from_index() {
        $knowledge = new GSXR_777_Knowledge();
        $filename = 'phpunit-brakes.md';
        $content = 'Brake fluid replacement interval and ABS diagnostic procedure.';

        $this->assertTrue($knowledge->save_file($filename, $content));
        $this->assertSame($content, $knowledge->get_file_content($filename));

        $retrieved = $knowledge->retrieve_relevant_content('ABS brake diagnostics');
        $this->assertStringContainsString('ABS diagnostic procedure', $retrieved);

        $this->assertTrue($knowledge->delete_file($filename));
    }

    public function test_published_wordpress_page_is_indexed() {
        $post_id = self::factory()->post->create(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Toyota repair manuals',
            'post_content' => 'Workshop instructions for engine and transmission service.'
        ));

        $knowledge = new GSXR_777_Knowledge();
        $knowledge->index_post($post_id);

        $retrieved = $knowledge->retrieve_relevant_content('Toyota transmission service');
        $this->assertStringContainsString('Toyota repair manuals', $retrieved);
    }
}
