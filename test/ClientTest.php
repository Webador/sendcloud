<?php

namespace Test\JouwWeb\SendCloud;

use GuzzleHttp\Psr7\Response;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Model\Parcel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /** @var Client */
    protected $client;

    /** @var \GuzzleHttp\Client|MockObject */
    protected $guzzleClientMock;

    public function setUp(): void
    {
        $this->client = new Client('handsome public key', 'gorgeous secret key', 'aPartnerId');

        $this->guzzleClientMock = $this->createPartialMock(\GuzzleHttp\Client::class, ['request']);

        // Inject the mock HTTP client through reflection. The alternative is to pass it into the ctor but that would
        // require us to use PSR-7 requests instead of Guzzle's more convenient usage.
        $clientProperty = new \ReflectionProperty(Client::class, 'guzzleClient');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->client, $this->guzzleClientMock);
    }

    public function testGetUser(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"user":{"address":"Insulindelaan","city":"Eindhoven","company_logo":null,"company_name":"SendCloud","data":[],"email":"johndoe@sendcloud.nl","invoices":[{"date":"05-06-201811:58:52","id":1,"isPayed":false,"items":"https://local.sendcloud.sc/api/v2/user/invoices/1","price_excl":77.4,"price_incl":93.65,"ref":"1","type":"periodic"}],"modules":[{"activated":true,"id":5,"name":"SendCloudClient","settings":null,"short_name":"sendcloud_client"},{"id":3,"name":"PrestashopIntegration","settings":{"url_webshop":"http://localhost/testing/prestashop","api_key":"O8ALXHMM24QULWM213CC6SGQ5VDJKC8W"},"activated":true,"short_name":"prestashop"}],"postal_code":"5642CV","registered":"2018-05-2912:52:51","telephone":"+31626262626","username":"johndoe"}}'
        ));

        $user = $this->client->getUser();

        $this->assertEquals('johndoe', $user->getUsername());
        $this->assertEquals('SendCloud', $user->getCompanyName());
        $this->assertEquals('+31626262626', $user->getTelephone());
        $this->assertEquals('Insulindelaan', $user->getAddress());
        $this->assertEquals('Eindhoven', $user->getCity());
        $this->assertEquals('5642CV', $user->getPostalCode());
        $this->assertEquals('johndoe@sendcloud.nl', $user->getEmail());
        $this->assertEquals(new \DateTimeImmutable('2018-05-29 12:52:51'), $user->getRegistered());

    }

    public function testGetShippingMethods(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"shipping_methods": [{"service_point_input": "none","max_weight": "1.000","name": "Low weight shipment","carrier": "carrier_code","countries": [{"iso_2": "BE","iso_3": "BEL","id": 1,"price": 3.50,"name": "Belgium"},{"iso_2": "NL","iso_3": "NLD","id": 2,"price": 4.20,"name": "Netherlands"}],"min_weight": "0.001","id": 1,"price": 0}]}'
        ));

        $shippingMethods = $this->client->getShippingMethods();

        $this->assertCount(1, $shippingMethods);
        $this->assertEquals(1, $shippingMethods[0]->getId());
        $this->assertEquals(1, $shippingMethods[0]->getMinimumWeight());
        $this->assertEquals(1000, $shippingMethods[0]->getMaximumWeight());
        $this->assertEquals('carrier_code', $shippingMethods[0]->getCarrier());
        $this->assertEquals(['BE' => 350, 'NL' => 420], $shippingMethods[0]->getPrices());
        $this->assertEquals(420, $shippingMethods[0]->getPriceForCountry('NL'));
        $this->assertEquals(null, $shippingMethods[0]->getPriceForCountry('EN'));
    }

    public function testCreateParcel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":8293794,"address":"straat 23","address_2":"","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.490","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"20250533","external_shipment_id":"201900001"}}'
        ));

        $parcel = $this->client->createParcel(
            new Address('Baron van der Zanden', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', null),
            null,
            '201900001',
            2486
        );

        $this->assertEquals(8293794, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_NO_LABEL, $parcel->getStatusId());
        $this->assertEquals(new \DateTimeImmutable('2019-03-11 14:35:10'), $parcel->getCreated());
        $this->assertEquals('Baron van der Zanden', $parcel->getAddress()->getName());
        $this->assertEquals('', $parcel->getAddress()->getCompanyName());
        $this->assertEquals(null, $parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT));
    }

    public function testUpdateParcel(): void
    {
        $this->markTestIncomplete();
    }
}