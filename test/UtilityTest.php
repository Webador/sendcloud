<?php

namespace Test\JouwWeb\SendCloud;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Exception\SendCloudWebhookException;
use JouwWeb\SendCloud\Model\WebhookEvent;
use JouwWeb\SendCloud\Utility;
use PHPUnit\Framework\TestCase;

class UtilityTest extends TestCase
{
    public function testParseWebhookRequest(): void
    {
        $payload = '{"action":"parcel_status_changed","timestamp":1525271885993,"parcel":{"id":3,"name":"John Doe","company_name":"SendCloud","address":"Insulindelaan 115","address_2":"","to_state":"","address_divided":{"street":"Insulindelaan","house_number":"115"},"city":"Eindhoven","postal_code":"5642CV","telephone":"0612345678","email":"","date_created":"01-01-2018 21:45:30","tracking_number":"S0M3TR4Ck1NgNumB3r","weight":"2.000","label":{"normal_printer":["https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=0&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=1&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=2&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=3&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4"],"label_printer":"https://panel.sendcloud.sc/api/v2/label/label_printer/3172?hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4"},"customs_declaration":{},"status":{"id":0,"message":"Ready to send"},"data":{},"country":{"iso_3":"NLD","iso_2":"NL","name":"Netherlands"},"shipment":{"id":8,"name":"Unstamped letter"},"order_number":"0rd3rnumb3r","shipment_uuid":"87e18823-016b-479b-b9e0-c5c0c4065452","external_order_id":"42","external_shipment_id":"S01"}}';
        $secretKey = 'be7e2f9eb99716d4698bbe8220eea46f';
        $signature = '46969e389acdfd89083672b64f6214b68c9b0af6bb1c7457b37a36a7cc0ee52b';

        $request = new Request('POST', 'https://some.webhook/url', [
            'SendCloud-Signature' => $signature,
        ], $payload);

        Utility::verifyWebhookRequest($request, $secretKey);
        $this->addToAssertionCount(1);

        try {
            Utility::verifyWebhookRequest($request->withBody(Utils::streamFor(substr($payload, 0, -1))), $secretKey);
            $this->fail('Invalid request was validated correctly.');
        } catch (SendCloudWebhookException $exception) {
            $this->assertEquals(SendCloudWebhookException::CODE_VERIFICATION_FAILED, $exception->getCode());
        }

        // Parse with client and test the event
        $client = new Client('whatever', $secretKey);
        $event = $client->parseWebhookRequest($request);

        $this->assertEquals(WebhookEvent::TYPE_PARCEL_STATUS_CHANGED, $event->getType());
        $this->assertEquals('Insulindelaan', $event->getParcel()->getAddress()->getStreet());
        $this->assertEquals(new \DateTimeImmutable('2018-05-02 14:38:05.993'), $event->getCreated());
        $this->assertCount(1, $event->getPayload());
        $this->assertArrayHasKey('parcel', $event->getPayload());
    }

    public function testParseWebhookRequestInvalid(): void
    {
        try {
            Utility::parseWebhookRequest(new Request('GET', 'https://some.webhook/url'), null);
            $this->fail('Invalid webhook request was parsed.');
        } catch (SendCloudWebhookException $exception) {
            $this->assertEquals(SendCloudWebhookException::CODE_INVALID_REQUEST, $exception->getCode());

        }
    }
}
