<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leadlookup extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // JSON-only endpoint
        $this->load->model('leadlookup/leadlookup_model');

        // Load module config (no sections)
        $this->config->load('leadlookup/leadlookup');

        // Security check
        $this->enforce_api_key();
    }

    /**
     * GET /leadlookup/by_phone?apikey=XXXX&phone=XXXXXXXX
     */
    public function by_phone()
    {
        $rawPhone = (string) $this->input->get('phone', true);
        $phone    = $this->sanitize_phone($rawPhone);

        if ($phone === '') {
            return $this->json_error(400, 'Query parameter "phone" is required.');
        }

        $leads = $this->leadlookup_model->find_leads_by_phone($phone);

        if (empty($leads)) {
            return $this->json_error(404, 'No leads found for the provided phone number.');
        }

        return $this->json_success($leads, 200);
    }

    // -------------------------
    // Security
    // -------------------------

    private function enforce_api_key()
    {
        $cfg      = (array) $this->config->item('leadlookup');
        $expected = (string) ($cfg['api_key'] ?? '');

        $provided = (string) $this->input->get('apikey', true);

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return $this->json_error(401, 'Invalid or missing API key.');
        }
    }

    // -------------------------
    // Helpers
    // -------------------------

    private function sanitize_phone($phone)
    {
        $phone = trim((string) $phone);

        // Keep digits and plus (international format)
        $phone = preg_replace('/[^\\d\\+]/', '', $phone);

        // Normalize multiple plus signs
        $phone = preg_replace('/^\\++/', '+', $phone);

        // Reasonable length cap
        if (strlen($phone) > 30) {
            $phone = substr($phone, 0, 30);
        }

        return $phone;
    }

    private function json_success($data, $httpCode = 200)
    {
        $response = [
            'status' => 'success',
            'data'   => is_array($data) ? $data : [$data],
        ];

        $this->output
            ->set_status_header((int) $httpCode)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->output->_display();
        exit;
    }

    private function json_error($httpCode, $message)
    {
        $response = [
            'status'  => 'error',
            'message' => (string) $message,
            'data'    => [],
        ];

        $this->output
            ->set_status_header((int) $httpCode)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->output->_display();
        exit;
    }
}
