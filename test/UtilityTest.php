<?php

namespace Test\JouwWeb\SendCloud;

use GuzzleHttp\Psr7\Request;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Exception\SendCloudWebhookException;
use JouwWeb\SendCloud\Model\WebhookEvent;
use JouwWeb\SendCloud\Utility;
use PHPUnit\Framework\TestCase;
use function GuzzleHttp\Psr7\stream_for;

class UtilityTest extends TestCase
{
    public function testParseWebhookRequest():void
    {
        $payload = '{"action":"parcel_status_changed","timestamp":1525271885993,"parcel":{"id":3,"name":"John Doe","company_name":"SendCloud","address":"Insulindelaan 115","address_divided":{"street":"Insulindelaan","house_number":"115"},"city":"Eindhoven","postal_code":"5642CV","telephone":"0612345678","email":"","date_created":"01-01-2018 21:45:30","tracking_number":"S0M3TR4Ck1NgNumB3r","weight":"2.000","label":{"normal_printer":["https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=0&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=1&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=2&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4","https://panel.sendcloud.sc/api/v2/label/normal_printer/3172?start_from=3&hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4"],"label_printer":"https://panel.sendcloud.sc/api/v2/label/label_printer/3172?hash=bbfd669ee9ebb19408b85b33d181a50040fd9bc4"},"customs_declaration":{},"status":{"id":0,"message":"Ready to send"},"data":{},"country":{"iso_3":"NLD","iso_2":"NL","name":"Netherlands"},"shipment":{"id":8,"name":"Unstamped letter"},"order_number":"0rd3rnumb3r","shipment_uuid":"87e18823-016b-479b-b9e0-c5c0c4065452","external_order_id":"42","external_shipment_id":"S01"}}';
        $secretKey = 'be7e2f9eb99716d4698bbe8220eea46f';
        $signature = 'a9bd66100bcf12a2bf9a4ea21c4ba7ac6d81ac6459b9d077bf4f90db11fcee4a';

        $request = new Request('POST', 'https://some.webhook/url', [
            'SendCloud-Signature' => $signature,
        ], $payload);

        Utility::verifyWebhookRequest($request, $secretKey);
        $this->addToAssertionCount(1);

        try {
            Utility::verifyWebhookRequest($request->withBody(stream_for(substr($payload, 0, -1))), $secretKey);
            $this->fail('Invalid request was validated correctly.');
        } catch (SendCloudWebhookException $exception) {
            $this->addToAssertionCount(1);
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
}
