<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Model\Entity\CaseData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Header\Cookie;
use Laminas\Http\Request;
use Laminas\Stdlib\RequestInterface;


class SiriusApi
{
    public function __construct(
        private readonly Client $client
    ) {
    }

    private function getAuthHeaders(RequestInterface $request): ?array
    {
        if (! ($request instanceof Request)) {
            return null;
        }

        $cookieHeader = $request->getHeader('Cookie');

        if (! ($cookieHeader instanceof Cookie)) {
            return null;
        }

        return [
            'Cookie' => $cookieHeader->getFieldValue(),
        ];
    }

    public function checkAuth(RequestInterface $request): bool
    {
        try {
            $headers = $this->getAuthHeaders($request);

            if ($headers === null) {
                return false;
            }

            $this->client->get('/api/v1/users/current', [
                'headers' => $headers,
            ]);
        } catch (GuzzleException $e) {
            return false;
        }

        return true;
    }

    public function postOfficeSuffix(string $suffix, CaseData $caseData)
    {
        $data = [
            "type" => "Save",
            "systemType" => "DLP-ID-PO-D",
            "content" => "",
            "suffix" => "{{contents of Yoti PDF, in base-64}}",
            "correspondentName" => "{{name of recipient}}",
            "correspondentAddress" => "{{address of recipient: array of address lines}}"
        ];
        //what if there is multiple LPA IDs??
        $response = $this->client->post('/api/v1/lpas/:lpa_id/documents', [
            'json' => $data
        ]);

        return [
            'status' => $response->getStatusCode(),
            'error' => json_decode(strval($response->getBody()), true)
        ];
    }
}
