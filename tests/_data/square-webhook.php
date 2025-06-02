<?php

return [
  'id'               => 'wbhk_test-webhook-id',
  'name'             => 'WordPress Integration Webhook',
  'enabled'          => true,
  'notification_url' => 'http://whodat.test/commerce/v1/square/webhooks/receiver/live',
  'api_version'      => '2025-04-16',
  'signature_key'    => 'test-signature-key',
  'created_at'       => '2005-05-01 18:58:11 +0000 UTC',
  'updated_at'       => '2025-05-29 12:32:35 +0000 UTC',
  'expires_at'       => '2055-06-12T17:59:00Z',
  'fetched_at'       => '2015-05-29 20:59:00',
  'event_types'      => [
    'payment.updated',
    'payment.created',
    'refund.created',
    'refund.updated',
    'order.created',
    'order.updated',
    'customer.deleted',
    'inventory.count.updated',
    'oauth.authorization.revoked',
  ],
];