<?php

namespace JouwWeb\Sendcloud;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trait HttpClientTrait
{
    private function createHttpClient(
        ?HttpClientInterface $httpClient,
        string $apiBaseUrl,
        string $publicKey,
        string $secretKey,
        ?string $partnerId = null,
    ): HttpClientInterface {
        $requestOptions = new HttpOptions();
        $requestOptions->setBaseUri($apiBaseUrl);
        // Mainly because the shipping methods endpoint can take a very long time to respond.
        $requestOptions->setTimeout(60);
        $requestOptions->setAuthBasic($publicKey, $secretKey);

        $headers = [
            'User-Agent' => 'jouwweb/sendcloud',
        ];
        if ($partnerId) {
            $headers['Sendcloud-Partner-Id'] = $partnerId;
        }
        $requestOptions->setHeaders($headers);

        return $httpClient?->withOptions($requestOptions->toArray()) ?? HttpClient::create($requestOptions->toArray());
    }
}
