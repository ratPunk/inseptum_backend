<?php
declare(strict_types=1);

namespace App\Http;

class JsonResponse extends Response
{
    public function __construct($data, int $status = 200, array $headers = [])
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            $body = '{"status":false,"message":"JSON encoding failed"}';
            $status = 500;
        }
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        parent::__construct($body, $status, $headers);
    }
}
