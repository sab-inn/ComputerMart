<?php
require_once __DIR__ . '/../config.php';

// Mirrors C#'s "0.##" format: no trailing zeros, no thousands separator -
// eSewa expects a plain numeric string.
function esewa_format_amount(float $amount): string {
    $s = number_format($amount, 2, '.', '');
    $s = rtrim($s, '0');
    return rtrim($s, '.');
}

function esewa_sign(string $message): string {
    return base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));
}

// Everything needed to render the auto-submitting form that sends the
// buyer's browser to eSewa's checkout page.
function esewa_build_payment_form(float $amount, string $transactionUuid): array {
    $amountStr = esewa_format_amount($amount);
    // eSewa mandates this exact field order for the signed message.
    $signedFieldNames = 'total_amount,transaction_uuid,product_code';
    $message = "total_amount={$amountStr},transaction_uuid={$transactionUuid},product_code=" . ESEWA_PRODUCT_CODE;

    return [
        'amount' => $amountStr,
        'tax_amount' => '0',
        'total_amount' => $amountStr,
        'transaction_uuid' => $transactionUuid,
        'product_code' => ESEWA_PRODUCT_CODE,
        'product_service_charge' => '0',
        'product_delivery_charge' => '0',
        'success_url' => ESEWA_SUCCESS_URL,
        'failure_url' => ESEWA_FAILURE_URL,
        'signed_field_names' => $signedFieldNames,
        'signature' => esewa_sign($message),
        'form_url' => ESEWA_FORM_URL,
    ];
}

// Decodes and verifies the base64 "data" query param eSewa redirects back
// with. Returns null if the signature doesn't check out - never mark an
// order paid off an unverified redirect.
function esewa_verify_callback(string $base64Data): ?array {
    $json = base64_decode($base64Data, true);
    if ($json === false) return null;

    $payload = json_decode($json, true);
    if (!is_array($payload) || !isset($payload['signature'], $payload['signed_field_names'])) {
        return null;
    }

    // Rebuild the "key=value,key=value" string using whatever fields THIS
    // response says were signed, in that order - never hardcode the list,
    // eSewa's docs use a different field set here than on the outgoing request.
    $parts = [];
    foreach (explode(',', $payload['signed_field_names']) as $field) {
        if (!array_key_exists($field, $payload)) return null;
        $parts[] = "{$field}=" . $payload[$field];
    }
    $expected = esewa_sign(implode(',', $parts));

    if (!hash_equals($expected, (string)$payload['signature'])) {
        return null;
    }

    return $payload;
}

// Server-to-server confirmation, independent of the browser redirect.
function esewa_check_status(string $transactionUuid, float $totalAmount): array {
    $url = ESEWA_STATUS_URL . '?product_code=' . urlencode(ESEWA_PRODUCT_CODE)
         . '&total_amount=' . urlencode(esewa_format_amount($totalAmount))
         . '&transaction_uuid=' . urlencode($transactionUuid);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);

    $data = json_decode((string)$body, true);
    return [
        'status' => $data['status'] ?? 'UNKNOWN',
        'ref_id' => $data['refId'] ?? null,
        'total_amount' => isset($data['totalAmount']) ? (float)$data['totalAmount'] : 0.0,
    ];
}
