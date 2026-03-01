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
            return $this->json_response([
                'error'   => 'phone_required',
                'message' => 'Query parameter "phone" is required.',
            ], 400);
        }

        $leads = $this->leadlookup_model->find_leads_by_phone($phone);

        if (empty($leads)) {
            return $this->json_response([
                'error'   => 'not_found',
                'message' => 'No leads found for the provided phone number.',
            ], 404);
        }

        // Success: data is an array of leads
        return $this->json_response($leads, 200);
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
            return $this->json_response([
                'error'   => 'unauthorized',
                'message' => 'Invalid or missing API key.',
            ], 401);
        }
    }

    // -------------------------
    // Helpers
    // -------------------------

    private function sanitize_phone($phone)
    {
        $phone = trim((string) $phone);

        // Keep digits and plus (international format)
        $phone = preg_replace('/[^\d\+]/', '', $phone);

        // Normalize multiple plus signs
        $phone = preg_replace('/^\++/', '+', $phone);

        // Reasonable length cap
        if (strlen($phone) > 30) {
            $phone = substr($phone, 0, 30);
        }

        return $phone;
    }

    private function is_assoc_array($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        $keys = array_keys($arr);
        return array_keys($keys) !== $keys;
    }

    /**
     * Wrap every response as:
     * { "status": "success|error", "data": [...] }
     *
     * Requirement: data must ALWAYS be an array, even on error.
     */
    private function json_response($payload, $httpCode = 200, $status = null)
    {
        if ($status === null) {
            $status = ((int) $httpCode >= 200 && (int) $httpCode < 300) ? 'success' : 'error';
        }

        // Ensure "data" is always an array:
        // - success usually sends array of leads (keep)
        // - error sends assoc array/object -> wrap into array
        if (!is_array($payload) || ($status === 'error' && $this->is_assoc_array($payload))) {
            $payload = [$payload];
        }

        $response = [
            'status' => $status,
            'data'   => $payload,
        ];

        $this->output
            ->set_status_header((int) $httpCode)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->output->_display();
        exit;
    }
}
