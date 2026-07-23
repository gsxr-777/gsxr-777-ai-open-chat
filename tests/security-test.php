<?php
/**
 * Session and request-security tests.
 */

class GSXR_777_Security_Test extends WP_UnitTestCase {

    public function test_server_issued_session_is_signed() {
        $widget = new GSXR_777_Widget();
        $session = $widget->create_session_credentials();

        $this->assertMatchesRegularExpression(
            '/^gsxr777_[a-f0-9]{32}$/',
            $session['session_id']
        );
        $this->assertTrue(
            $widget->verify_session_credentials(
                $session['session_id'],
                $session['session_token']
            )
        );
    }

    public function test_modified_session_token_is_rejected() {
        $widget = new GSXR_777_Widget();
        $session = $widget->create_session_credentials();

        $this->assertFalse(
            $widget->verify_session_credentials(
                $session['session_id'],
                $session['session_token'] . 'x'
            )
        );
    }

    public function test_empty_and_client_chosen_session_ids_are_rejected() {
        $security = new GSXR_777_Security();

        $this->assertFalse($security->is_valid_session_id(''));
        $this->assertFalse($security->is_valid_session_id('gsxr777_123'));
        $this->assertTrue(
            $security->is_valid_session_id('gsxr777_' . str_repeat('a', 32))
        );
    }

    public function test_forwarded_header_cannot_spoof_leftmost_address() {
        $trusted_proxy_filter = function() {
            return array('10.0.0.0/8');
        };
        add_filter('gsxr_777_trusted_proxy_ips', $trusted_proxy_filter);

        $_SERVER['REMOTE_ADDR'] = '10.0.0.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99, 198.51.100.42';

        $widget = new GSXR_777_Widget();
        $method = new ReflectionMethod($widget, 'get_client_ip');
        $method->setAccessible(true);

        $this->assertSame('198.51.100.42', $method->invoke($widget));

        remove_filter('gsxr_777_trusted_proxy_ips', $trusted_proxy_filter);
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
    }
}
