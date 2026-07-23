<?php
/**
 * API integration helper tests.
 */

class GSXR_777_API_Test extends WP_UnitTestCase {

    public function test_api_key_encryption_round_trip_and_authentication() {
        $api = new GSXR_777_API();
        $plain = 'test-secret-key-123456789';
        $encrypted = $api->encrypt_api_key($plain);

        $this->assertStringStartsWith('v2:', $encrypted);
        $this->assertSame($plain, $api->decrypt_api_key($encrypted));

        $tampered_payload = base64_decode(substr($encrypted, 3), true);
        $tampered_payload[28] = chr(ord($tampered_payload[28]) ^ 1);
        $tampered = 'v2:' . base64_encode($tampered_payload);
        $this->assertSame('', $api->decrypt_api_key($tampered));
    }

    public function test_remote_http_endpoint_is_rejected_but_loopback_is_allowed() {
        $api = new GSXR_777_API();

        $this->assertFalse($api->is_allowed_api_base_url('http://example.com/v1'));
        $this->assertTrue($api->is_allowed_api_base_url('https://example.com/v1'));
        $this->assertTrue($api->is_allowed_api_base_url('http://127.0.0.1:11434/v1'));
    }
}
