<?php

declare(strict_types=1);

namespace RioSlum\HiringTest;

class MockCrmClient
{
    /**
     * Simulates sending a contact to a third-party CRM.
     *
     * Return examples:
     * - ['success' => true, 'status' => 200, 'id' => 'crm_123']
     * - ['success' => false, 'status' => 429, 'error' => 'Rate limit exceeded', 'retry_after' => 1]
     * - ['success' => false, 'status' => 500, 'error' => 'Temporary CRM error']
     * - ['success' => false, 'status' => 400, 'error' => 'Invalid contact data']
     */
    public function sendContact(array $contact): array
    {
        $email = strtolower((string) ($contact['email'] ?? ''));

        if ($email === '') {
            return [
                'success' => false,
                'status' => 400,
                'error' => 'Missing email',
            ];
        }

        if (str_contains($email, 'rate.limit')) {
            return [
                'success' => false,
                'status' => 429,
                'error' => 'Rate limit exceeded',
                'retry_after' => 1,
            ];
        }

        if (str_contains($email, 'temporary.fail')) {
            return [
                'success' => false,
                'status' => 500,
                'error' => 'Temporary CRM error',
            ];
        }

        if (str_contains($email, 'invalid.crm')) {
            return [
                'success' => false,
                'status' => 400,
                'error' => 'Invalid contact data',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'id' => 'crm_' . substr(md5($email), 0, 10),
        ];
    }
}
