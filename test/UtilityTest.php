<?php

namespace Test\JouwWeb\Sendcloud;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use JouwWeb\Sendcloud\Client;
use JouwWeb\Sendcloud\Exception\SendcloudWebhookException;
use JouwWeb\Sendcloud\Model\WebhookEvent;
use JouwWeb\Sendcloud\Utility;
use PHPUnit\Framework\TestCase;

class UtilityTest extends TestCase
{
    public function testParseWebhookRequest(): void
    {
        $payload = '{"action":"parcel_status_changed","timestamp":1525271885993,"parcel":{"id":3,"name":"John Doe","company_name":"Sendcloud","address":"Insulindelaan 115","address_2":"","to_state":"","address_divided":{"street":"Insulindelaan","house_number":"115"},"city":"Eindhoven","postal_code":"5642CV","telephone":"0612345678","email":"","date_created":"01-01-2018 21:45:30","tracking_number":"S0M3TR4Ck1NgNumB3r","weight":"2.000","label":{"normal_printer":["https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=0&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=1&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=2&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=3&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4"],"label_printer":"https://panel.sendcloud.sc/api/v2/label/label_printer/3172?hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4"},"customs_declaration":{},"status":{"id":0,"message":"Ready to send"},"data":{},"country":{"iso_3":"NLD","iso_2":"NL","name":"Netherlands"},"shipment":{"id":8,"name":"Unstamped letter"},"order_number":"0rd3rnumb3r","shipment_uuid":"87e18823-016b-479b-b9e0-c5c0c4065452","external_order_id":"42","external_shipment_id":"S01"}}';
        $secretKey = 'be7e2f9eb99716d4698bbe8220eea46f';
        $signature = 'fd38c9dae7cf11f22c66470fabef28e415b086cebea2e9cfccafe4eaca672ba2';

        $request = new Request('POST', 'https://some.webhook/url', [
            'Sendcloud-Signature' => $signature,
        ], $payload);

        Utility::verifyWebhookRequest($request, $secretKey);
        $this->addToAssertionCount(1);

        try {
            Utility::verifyWebhookRequest($request->withBody(Utils::streamFor(substr($payload, 0, -1))), $secretKey);
            $this->fail('Invalid request was validated correctly.');
        } catch (SendcloudWebhookException $exception) {
            $this->assertEquals(SendcloudWebhookException::CODE_VERIFICATION_FAILED, $exception->getCode());
        }

        // Parse with client and test the event
        $client = new Client('whatever', $secretKey);
        $event = $client->parseWebhookRequest($request);

        $this->assertEquals(WebhookEvent::TYPE_PARCEL_STATUS_CHANGED, $event->type);
        $this->assertEquals('Insulindelaan 115', $event->parcel->address->addressLine1);
        $this->assertEquals('Insulindelaan', $event->parcel->address->street);
        $this->assertEquals('115', $event->parcel->address->houseNumber);
        $this->assertEquals(new \DateTimeImmutable('2018-05-02 14:38:05.993'), $event->created);
        $this->assertCount(1, $event->payload);
        $this->assertArrayHasKey('parcel', $event->payload);
    }

    public function testParseWebhookRequestInvalid(): void
    {
        try {
            Utility::parseWebhookRequest(new Request('GET', 'https://some.webhook/url'), null);
            $this->fail('Invalid webhook request was parsed.');
        } catch (SendcloudWebhookException $exception) {
            $this->assertEquals(SendcloudWebhookException::CODE_INVALID_REQUEST, $exception->getCode());

        }
    }
}
