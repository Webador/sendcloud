<?php

namespace Test\JouwWeb\Sendcloud;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JouwWeb\Sendcloud\Client;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
use JouwWeb\Sendcloud\Model\Address;
use JouwWeb\Sendcloud\Model\Parcel;
use JouwWeb\Sendcloud\Model\ParcelItem;
use JouwWeb\Sendcloud\Model\ShippingMethod;
use JouwWeb\Sendcloud\ServicePointsClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected Client $client;

    protected ServicePointsClient $servicePointsClient;

    /** @var \GuzzleHttp\Client&MockObject */
    protected \GuzzleHttp\Client $guzzleClientMock;

    public function setUp(): void
    {
        $this->client = new Client('handsome public key', 'gorgeous secret key', 'aPartnerId');
        $this->servicePointsClient = new ServicePointsClient('handsome public key', 'gorgeous secret key', 'aPartnerId');

        $this->guzzleClientMock = $this->createPartialMock(\GuzzleHttp\Client::class, ['request']);

        // Inject the mock HTTP client through reflection. The alternative is to pass it into the ctor but that would
        // require us to use PSR-7 requests instead of Guzzle's more convenient usage.
        $clientProperty = new \ReflectionProperty(Client::class, 'guzzleClient');
        $clientProperty->setValue($this->client, $this->guzzleClientMock);

        $clientProperty = new \ReflectionProperty(ServicePointsClient::class, 'guzzleClient');
        $clientProperty->setValue($this->servicePointsClient, $this->guzzleClientMock);
    }

    public function testGetUser(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"user":{"address":"Insulindelaan 115","city":"Eindhoven","company_logo":null,"company_name":"Sendcloud","data":[],"email":"johndoe@sendcloud.nl","invoices":[{"date":"05-06-201811:58:52","id":1,"isPayed":false,"items":"https://local.sendcloud.sc/api/v2/user/invoices/1","price_excl":77.4,"price_incl":93.65,"ref":"1","type":"periodic"}],"modules":[{"activated":true,"id":5,"name":"SendcloudClient","settings":null,"short_name":"sendcloud_client"},{"id":3,"name":"PrestashopIntegration","settings":{"url_webshop":"http://localhost/testing/prestashop","api_key":"O8ALXHMM24QULWM213CC6SGQ5VDJKC8W"},"activated":true,"short_name":"prestashop"}],"postal_code":"5642CV","registered":"2018-05-2912:52:51","telephone":"+31626262626","username":"johndoe"}}'
        ));

        $user = $this->client->getUser();

        $this->assertEquals('johndoe', $user->getUsername());
        $this->assertEquals('Sendcloud', $user->getCompanyName());
        $this->assertEquals('+31626262626', $user->getPhoneNumber());
        $this->assertEquals('Insulindelaan 115', $user->getAddress());
        $this->assertEquals('Eindhoven', $user->getCity());
        $this->assertEquals('5642CV', $user->getPostalCode());
        $this->assertEquals('johndoe@sendcloud.nl', $user->getEmailAddress());
        $this->assertEquals(new \DateTimeImmutable('2018-05-29 12:52:51'), $user->getRegistered());
    }

    public function testGetShippingMethods(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturnCallback(function () {
            $this->assertEquals([
                'GET',
                'shipping_methods',
                ['query' => [
                    'sender_address' => 'all',
                ]],
            ], func_get_args());

            return new Response(
                200,
                [],
                '{"shipping_methods": [{"service_point_input": "none","min_weight": "0.001","max_weight": "1.001","name": "Low weight shipment","carrier": "carrier_code","countries": [{"iso_2": "BE","iso_3": "BEL","id": 1,"price": 3.50,"name": "Belgium"},{"iso_2": "NL","iso_3": "NLD","id": 2,"price": 4.20,"name": "Netherlands"}],"min_weight": "0.001","id": 1,"price": 0}]}'
            );
        });

        $shippingMethods = $this->client->getShippingMethods();

        $this->assertCount(1, $shippingMethods);
        $this->assertEquals(1, $shippingMethods[0]->getId());
        $this->assertEquals(1, $shippingMethods[0]->getMinimumWeight());
        $this->assertEquals(1001, $shippingMethods[0]->getMaximumWeight());
        $this->assertEquals('carrier_code', $shippingMethods[0]->getCarrier());
        $this->assertEquals(['BE' => 350, 'NL' => 420], $shippingMethods[0]->getPrices());
        $this->assertEquals(420, $shippingMethods[0]->getPriceForCountry('NL'));
        $this->assertNull($shippingMethods[0]->getPriceForCountry('EN'));
        $this->assertFalse($shippingMethods[0]->getAllowsServicePoints());
    }

    public function testGetShippingMethodsOptionalArguments(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturnCallback(function () {
            $this->assertEquals([
                'GET',
                'shipping_methods',
                ['query' => [
                    'service_point_id' => 10,
                    'sender_address' => 11,
                    'is_return' => 'true',
                ]],
            ], func_get_args());

            return new Response(
                200,
                [],
                '{"shipping_methods": [{"service_point_input": "none","max_weight": "1.000","name": "Low weight shipment","carrier": "carrier_code","countries": [{"iso_2": "BE","iso_3": "BEL","id": 1,"price": 3.50,"name": "Belgium"},{"iso_2": "NL","iso_3": "NLD","id": 2,"price": 4.20,"name": "Netherlands"}],"min_weight": "0.001","id": 1,"price": 0}]}'
            );
        });

        $this->client->getShippingMethods(10, 11, true);
    }

    public function testGetSenderAddresses(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"sender_addresses":[{"id":92837,"company_name":"AwesomeCo Inc.","contact_name":"Bertus Bernardus","email":"bertus@awesomeco.be","telephone":"+31683749586","street":"Wegstraat","house_number":"233","postal_box":"","postal_code":"8398","city":"Brussel","country":"BE"},{"id":28397,"company_name":"AwesomeCo Inc. NL","contact_name":"","email":"","telephone":"0645000000","street":"Torenallee","house_number":"20","postal_box":"","postal_code":"5617 BC","city":"Eindhoven","country":"NL"}]}'
        ));

        $senderAddresses = $this->client->getSenderAddresses();

        $this->assertCount(2, $senderAddresses);
        $this->assertEquals(92837, $senderAddresses[0]->getId());
        $this->assertEquals('AwesomeCo Inc.', $senderAddresses[0]->getCompanyName());
        $this->assertEquals('', $senderAddresses[1]->getContactName());
    }

    public function testCreateParcel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":8293794,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}}'
        ));

        $parcel = $this->client->createParcel(
            new Address('Baron van der Zanden', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', 'Blok 3', 'CA'),
            null,
            '201900001',
            2486
        );

        $this->assertEquals(8293794, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_NO_LABEL, $parcel->getStatusId());
        $this->assertEquals(new \DateTimeImmutable('2019-03-11 14:35:10'), $parcel->getCreated());
        $this->assertEquals('Baron van der Zanden', $parcel->getAddress()->getName());
        $this->assertEquals('', $parcel->getAddress()->getCompanyName());
        $this->assertFalse($parcel->hasLabel());
        $this->assertNull($parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT));
        $this->assertEquals(2486, $parcel->getWeight());
        $this->assertEquals('201900001', $parcel->getOrderNumber());
        $this->assertNull($parcel->getShippingMethodId());
        $this->assertEquals('Blok 3', $parcel->getAddress()->getAddressLine2());
        $this->assertEquals('CA', $parcel->getAddress()->getCountryStateCode());
    }

    public function testCreateParcelWithVerboseError(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":8293794,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001", "errors" : {"name": "This field is required."}}}'
        ));

        $parcel = $this->client->createParcel(
            new Address('', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', 'Blok 3', 'CA'),
            null,
            '201900001',
            2486,
            null,
            null,
            null,
            null,
            null,
            Parcel::ERROR_VERBOSE
        );

        $this->assertEquals(8293794, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_NO_LABEL, $parcel->getStatusId());
        $this->assertEquals(new \DateTimeImmutable('2019-03-11 14:35:10'), $parcel->getCreated());
        //$this->assertEquals('Baron van der Zanden', $parcel->getAddress()->getName());
        $this->assertEquals('', $parcel->getAddress()->getCompanyName());
        $this->assertFalse($parcel->hasLabel());
        $this->assertNull($parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT));
        $this->assertEquals(2486, $parcel->getWeight());
        $this->assertEquals('201900001', $parcel->getOrderNumber());
        $this->assertNull($parcel->getShippingMethodId());
        $this->assertEquals('Blok 3', $parcel->getAddress()->getAddressLine2());
        $this->assertEquals('CA', $parcel->getAddress()->getCountryStateCode());
        $this->assertEquals(['name' => ["This field is required."]], $parcel->getErrors());
    }

    public function testCreateMultiParcel(): void
    {
        $parcel_json_1 = '{"id":8293794,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}';
        $parcel_json_2 = '{"id":8293795,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}';

        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcels":['.$parcel_json_1.','.$parcel_json_2.']}'
        ));

        $parcels = $this->client->createMultiParcel(
            new Address('Baron van der Zanden', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', 'Blok 3', 'CA'),
            null,
            '201900001',
            2486,
            null,
            null,
            null,
            null,
            ShippingMethod::fromData(['id' => 1, 'name' => 'test', 'min_weight' => 1, 'max_weight' => 1000, 'carrier' => 'sendcloud', 'service_point_input' => 'none', 'countries' => [['iso_2' => 'CA', 'price' => 0]]]),
            null,
            2
        );

        $this->assertCount(2, $parcels);

        foreach ($parcels as $key => $parcel) {
            $id = $key == 0 ? 8293794 : 8293795;

            $this->assertEquals($id, $parcel->getId());
            $this->assertEquals(Parcel::STATUS_NO_LABEL, $parcel->getStatusId());
            $this->assertEquals(new \DateTimeImmutable('2019-03-11 14:35:10'), $parcel->getCreated());
            $this->assertEquals('Baron van der Zanden', $parcel->getAddress()->getName());
            $this->assertEquals('', $parcel->getAddress()->getCompanyName());
            $this->assertFalse($parcel->hasLabel());
            $this->assertNull($parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT));
            $this->assertEquals(2486, $parcel->getWeight());
            $this->assertEquals('201900001', $parcel->getOrderNumber());
            $this->assertNull($parcel->getShippingMethodId());
            $this->assertEquals('Blok 3', $parcel->getAddress()->getAddressLine2());
            $this->assertEquals('CA', $parcel->getAddress()->getCountryStateCode());
        }
    }

    public function testCreateMultiParcelWithVerboseError(): void
    {
        $parcel_json = '{"name":"Baron van der Zanden","company_name":"","address":"straat","address_2":"CA","house_number":"23","city":"Gehucht","postal_code":"9283DD","country":"NL","email":"baron@vanderzanden.nl","telephone":"Blok 3","country_state":"","order_number":"201900001","weight":"2.486","request_label":true,"shipment":{"id":1},"quantity":2}';

        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcels": [], "failed_parcels": [{"parcel":'.$parcel_json.', "errors": { "name": ["This field is required."]}}]}'
        ));

        $parcels = $this->client->createMultiParcel(
            new Address('Baron van der Zanden', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', 'Blok 3', 'CA'),
            null,
            '201900001',
            2486,
            null,
            null,
            null,
            null,
            ShippingMethod::fromData(['id' => 1, 'name' => 'test', 'min_weight' => 1, 'max_weight' => 1000, 'carrier' => 'sendcloud', 'service_point_input' => 'none', 'countries' => [['iso_2' => 'CA', 'price' => 0]]]),
            Parcel::ERROR_VERBOSE,
            2
        );

        $this->assertCount(0, $parcels);
    }

    public function testCreateParcelCustoms(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')
            ->willReturnCallback(function () {
                $this->assertEquals([
                    'POST',
                    'parcels',
                    ['json' => ['parcel' => ['name' => 'Dr. Coffee', 'company_name' => '', 'address' => 'Street', 'house_number' => '123', 'address_2' => 'Unit 83', 'city' => 'Place', 'postal_code' => '7837', 'country' => 'BM', 'email' => 'drcoffee@drcoffee.dr', 'telephone' => '', 'country_state' => '', 'customs_invoice_nr' => 'customsInvoiceNumber', 'customs_shipment_type' => 2, 'parcel_items' => [0 => ['description' => 'green tea', 'quantity' => 1, 'weight' => '0.123', 'value' => 15.2, 'hs_code' => '090210', 'origin_country' => 'EC'], 1 => ['description' => 'cardboard', 'quantity' => 3, 'weight' => '0.050','value' => 0.2, 'hs_code' => '090210', 'origin_country' => 'NL', 'sku' => 'SKUSKUSKU', 'product_id' => 'Product2839', 'properties' => ['propertyKey' => 'propertyValue']]]]]],
                ], func_get_args());

                return new Response(200, [], '{"parcel":{"id":36054805,"address":"Street 123","address_2":"Unit 83","address_divided":{"house_number":"123","street":"Street"},"city":"Place","company_name":"","country":{"iso_2":"BM","iso_3":"BMU","name":"Bermuda"},"data":{},"date_created":"06-02-2020 21:33:13","email":"drcoffee@drcoffee.dr","name":"Dr. Coffee","postal_code":"7837","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"1.000","label":{},"customs_declaration":{},"order_number":"","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"customsInvoiceNumber","customs_shipment_type":2,"parcel_items":[{"description":"cardboard","quantity":3,"weight":"0.050","value":"0.20","hs_code":"090210","origin_country":"NL","product_id":"","properties":{},"sku":"","return_reason":null,"return_message":null},{"description":"green tea","quantity":1,"weight":"0.123","value":"15.20","hs_code":"090210","origin_country":"EC","product_id":"Product2839","properties":{"propertyKey":"propertyValue"},"sku":"SKUSKUSKU","return_reason":null,"return_message":null}],"documents":[],"type":null,"shipment_uuid":"f893c98c-43a6-49bb-9dda-9bf3e76a87ad","shipping_method":null,"external_order_id":"36054805","external_shipment_id":"","external_reference":null,"is_return":false,"note":""}}');
            });

        $parcel = $this->client->createParcel(
            new Address('Dr. Coffee', null, 'Street', 'Place', '7837', 'BM', 'drcoffee@drcoffee.dr', '123', null, 'Unit 83'),
            null,
            null,
            null,
            'customsInvoiceNumber',
            Parcel::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS,
            [
                'keyIgnored' => new ParcelItem('green tea', 1, 123, 15.20, '090210', 'EC'),
                new ParcelItem('cardboard', 3, 50, 0.20, '090210', 'NL', 'SKUSKUSKU', 'Product2839', ['propertyKey' => 'propertyValue']),
            ]
        );

        $this->assertEquals('customsInvoiceNumber', $parcel->getCustomsInvoiceNumber());
        $this->assertEquals(Parcel::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS, $parcel->getCustomsShipmentType());
        $this->assertCount(2, $parcel->getItems());
        $this->assertEquals([
            'description' => 'green tea',
            'quantity' => 1,
            'weight' => 123,
            'value' => 15.2,
            'harmonizedSystemCode' => '090210',
            'originCountryCode' => 'EC',
            'sku' => 'SKUSKUSKU',
            'productId' => 'Product2839',
            'properties' => [
                'propertyKey' => 'propertyValue',
            ],
        ], $parcel->getItems()[1]->toArray());
    }

    public function testUpdateParcel(): void
    {
        // Test that update only updates the address details (and not e.g., order number/weight)
        $this->guzzleClientMock->expects($this->once())->method('request')
            ->willReturnCallback(function () {
                $this->assertEquals([
                    'PUT',
                    'parcels',
                    ['json' => ['parcel' => ['id' => 8293794, 'name' => 'Completely different person', 'company_name' => 'Some company', 'address' => 'Rosebud', 'address_2' => 'Above the skies', 'house_number' => '2134A', 'city' => 'Almanda', 'postal_code' => '9238DD', 'country' => 'NL', 'email' => 'completelydifferent@email.com', 'telephone' => '+31699999999', 'country_state' => 'CS']]]
                ], func_get_args());

                return new Response(
                    200,
                    [],
                    '{"parcel":{"id":8293794,"address":"Rosebud 2134 A","address_2":"Above the skies","address_divided":{"street":"Rosebud","house_number":"2134"},"city":"Almanda","company_name":"Some company","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"completelydifferent@email.com","name":"Completely different person","postal_code":"9238DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"+31699999999","tracking_number":"","weight":"2.490","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CS","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}}'
                );
            });

        $parcel = $this->client->updateParcel(8293794, new Address('Completely different person', 'Some company', 'Rosebud', 'Almanda', '9238DD', 'NL', 'completelydifferent@email.com', '2134A', '+31699999999', 'Above the skies', 'CS'));

        $this->assertEquals('Some company', $parcel->getAddress()->getCompanyName());
    }

    public function testCreateLabel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":8293794,"address":"Rosebud 2134 A","address_2":"","address_divided":{"street":"Rosebud","house_number":"2134"},"city":"Almanda","company_name":"Some company","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"completelydifferent@email.com","name":"Completely different person","postal_code":"9238 DD","reference":"0","shipment":{"id":117,"name":"DHLForYou Drop Off"},"status":{"id":1000,"message":"Ready to send"},"to_service_point":null,"telephone":"+31699999999","tracking_number":"JVGL4004421100020097","weight":"2.490","label":{"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer/8293794","normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=3"]},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":"parcel","shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":117,"external_order_id":"8293794","external_shipment_id":"201900001","carrier":{"code":"dhl"},"tracking_url":"https://jouwweb.shipping-portal.com/tracking/?country=nl&tracking_number=jvgl4004421100020097&postal_code=9238dd"}}'
        ));

        $parcel = $this->client->createLabel(8293794, 117, 61361);

        $this->assertEquals(Parcel::STATUS_READY_TO_SEND, $parcel->getStatusId());
        $this->assertEquals('JVGL4004421100020097', $parcel->getTrackingNumber());
        $this->assertEquals('https://jouwweb.shipping-portal.com/tracking/?country=nl&tracking_number=jvgl4004421100020097&postal_code=9238dd', $parcel->getTrackingUrl());
        $this->assertTrue($parcel->hasLabel());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/labels/label_printer/8293794', $parcel->getLabelUrl(Parcel::LABEL_FORMAT_A6));
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=3', $parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT));
        $this->assertEquals('dhl', $parcel->getCarrier());
        $this->assertEquals(117, $parcel->getShippingMethodId());
    }

    public function testGetParcel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":2784972,"address":"Teststraat 12 A10","address_2":"","address_divided":{"street":"Teststraat","house_number":"12"},"city":"Woonplaats","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"27-08-2018 11:32:04","email":"sjoerd@jouwweb.nl","name":"Sjoerd Nuijten","postal_code":"7777 AA","reference":"0","shipment":{"id":8,"name":"Unstamped letter"},"status":{"id":11,"message":"Delivered"},"to_service_point":null,"telephone":"","tracking_number":"3SYZXG192833973","weight":"1.000","label":{"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer/13846453","normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=3"]},"customs_declaration":{},"order_number":"201806006","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":"parcel","shipment_uuid":"cb1e0f2d-4e7f-456b-91fe-0bcf09847d10","shipping_method":39,"external_order_id":"2784972","external_shipment_id":"201806006","carrier":{"code":"postnl"},"tracking_url":"https://tracking.sendcloud.sc/forward?carrier=postnl&code=3SYZXG192833973&destination=NL&lang=nl&source=NL&type=parcel&verification=7777AA"}}'
        ));

        $parcel = $this->client->getParcel(2784972);

        $this->assertEquals(2784972, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_DELIVERED, $parcel->getStatusId());
    }

    public function testCancelParcel(): void
    {
        $this->guzzleClientMock->expects($this->exactly(2))->method('request')->willReturnCallback(function ($method, $url) {
            $parcelId = (int)explode('/', $url)[1];

            if ($parcelId === 8293794) {
                return new Response(200, [], '{"status":"deleted","message":"Parcel has been deleted"}');
            }

            throw new RequestException(
                'Client error: ...',
                new Request('POST', 'url'),
                new Response(400, [], '{"status":"failed","message":"Shipped parcels, or parcels being shipped, can no longer be cancelled."}')
            );
        });

        $this->assertTrue($this->client->cancelParcel(8293794));
        $this->assertFalse($this->client->cancelParcel(2784972));
    }

    public function testParseRequestException(): void
    {
        $this->guzzleClientMock->method('request')->willThrowException(new RequestException(
            "Client error: `GET https://panel.sendcloud.sc/api/v2/user` resulted in a `401 Unauthorized` response:\n{\"error\":{\"message\":\"Invalid username/password.\",\"request\":\"api/v2/user\",\"code\":401}}\n))",
            new Request('GET', 'https://some.uri'),
            new Response(401, [], '{"error":{"message":"Invalid username/password.","request":"api/v2/user","code":401}}')
        ));

        try {
            $this->client->getUser();
            $this->fail('getUser completed successfully while a SendcloudRequestException was expected.');
        } catch (SendcloudRequestException $exception) {
            $this->assertEquals(SendcloudRequestException::CODE_UNAUTHORIZED, $exception->getCode());
            $this->assertEquals(401, $exception->getSendcloudCode());
            $this->assertEquals('Invalid username/password.', $exception->getSendcloudMessage());
        }
    }

    public function testParseRequestExceptionNoBody(): void
    {
        $this->guzzleClientMock->method('request')->willThrowException(new ConnectException(
            'Failed to reach server or something.',
            new Request('GET', 'https://some.uri')
        ));

        try {
            $this->client->getUser();
            $this->fail('getUser completed successfully while a SendcloudRequestException was expected.');
        } catch (SendcloudRequestException $exception) {
            $this->assertEquals(SendcloudRequestException::CODE_CONNECTION_FAILED, $exception->getCode());
            $this->assertNull($exception->getSendcloudCode());
            $this->assertNull($exception->getSendcloudMessage());
        }
    }

    public function testGetReturnPortalUrl(): void
    {
        $this->guzzleClientMock->method('request')->willReturn(new Response(
            200,
            [],
            '{"url":"https://awesome.shipping-portal.com/returns/initiate/HocusBogusPayloadPath/"}'
        ));

        $this->assertEquals(
            'https://awesome.shipping-portal.com/returns/initiate/HocusBogusPayloadPath/',
            $this->client->getReturnPortalUrl(9265)
        );
    }

    public function testGetReturnPortalUrlNotFound(): void
    {
        $this->guzzleClientMock->method('request')->willThrowException(new RequestException(
            "Client error: `GET https://panel.sendcloud.sc/api/v2/parcels/23676385/return_portal_url` resulted in a `404 Not Found` response:\n{\"url\":null}\n",
            new Request('GET', 'https://some.url'),
            new Response(404, [], '{"url":null}')
        ));

        $this->assertNull($this->client->getReturnPortalUrl(9265));
    }

    public function testGetBulkLabelPdf(): void
    {
        $requestNumber = 0;
        $this->guzzleClientMock->expects($this->exactly(2))->method('request')->willReturnCallback(
            function ($method, $url, $data) use (&$requestNumber) {
                $requestNumber++;

                if ($requestNumber === 1) {
                    $this->assertEquals(['json' => ['label' => ['parcels' => [0 => 1234, 1 => 4321]]]], $data);
                    return new Response(
                        200,
                        [],
                        '{"label":{"normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=3"],"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer?ids=1234,4321"}}'
                    );
                }

                if ($requestNumber === 2) {
                    $this->assertEquals('https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=0', $url);
                    return new Response(
                        200,
                        [],
                        'pdfdata'
                    );
                }

                return null;
            }
        );

        $parcelMock = $this->createMock(Parcel::class);
        $parcelMock->method('getId')->willReturn(4321);

        $pdf = $this->client->getBulkLabelPdf([1234, $parcelMock], Parcel::LABEL_FORMAT_A4_TOP_LEFT);
        $this->assertEquals('pdfdata', $pdf);
    }

    public function testSearchServicePoints(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '[
                {"id":1,"code":"217165","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"Media Markt Eindhoven Centrum B.V.","street":"Boschdijktunnel","house_number":"1","postal_code":"5611AG","city":"EINDHOVEN","latitude":"51.441444","longitude":"5.475185","email":"","phone":"","homepage":"","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["10:00 - 20:00"],"1":["10:00 - 20:00"],"2":["10:00 - 20:00"],"3":["10:00 - 20:00"],"4":["10:00 - 20:00"],"5":["10:00 - 18:00"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true},
                {"id":2,"code":"217165","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"Media Markt Eindhoven Centrum B.V.","street":"Boschdijktunnel","house_number":"1","postal_code":"5611AG","city":"EINDHOVEN","latitude":"51.441444","longitude":"5.475185","email":"","phone":"","homepage":"","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["10:00 - 20:00"],"1":["10:00 - 20:00"],"2":["10:00 - 20:00"],"3":["10:00 - 20:00"],"4":["10:00 - 20:00"],"5":["10:00 - 18:00"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true}
            ]'
        ));

        $servicePoints = $this->servicePointsClient->searchServicePoints(country: 'NL');

        $this->assertCount(2, $servicePoints);
        $this->assertEquals(1, $servicePoints[0]->getId());
        $this->assertEquals(2, $servicePoints[1]->getId());
    }

    public function testSearchServicePointWithDistance(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '[
                {"id":1,"code":"217165","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"Media Markt Eindhoven Centrum B.V.","street":"Boschdijktunnel","house_number":"1","postal_code":"5611AG","city":"EINDHOVEN","latitude":"51.441444","longitude":"5.475185","email":"","phone":"","homepage":"","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["10:00 - 20:00"],"1":["10:00 - 20:00"],"2":["10:00 - 20:00"],"3":["10:00 - 20:00"],"4":["10:00 - 20:00"],"5":["10:00 - 18:00"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true,"distance":381}
            ]'
        ));

        $servicePoints = $this->servicePointsClient->searchServicePoints(country: 'NL', latitude: 0, longitude: 0);

        $this->assertCount(1, $servicePoints);
        $this->assertEquals(1, $servicePoints[0]->getId());
        $this->assertNotNull($servicePoints[0]->getDistance());
    }

    public function testGetServicePoint(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"id":26,"code":"4c8181feec8f49fdbe67d9c9f6aaaf6f","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"DUMMY-3f1d6384391f45ce","street":"Sesamstraat","house_number":"40","postal_code":"5699YE","city":"Eindhoven","latitude":"51.440400","longitude":"5.475800","email":"devnull@sendcloud.nl","phone":"+31401234567","homepage":"https://www.sendcloud.nl","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["13:30 - 17:15"],"1":["09:00 - 12:00","13:30 - 17:15"],"2":["09:00 - 12:00","13:30 - 17:15"],"3":[],"4":["09:00 - 12:00","13:30 - 17:15"],"5":["09:00 - 12:00","13:30 - 17:15"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true,"distance":361}'
        ));

        $extraData = [
            'partner_name' => 'PostNL',
            'sales_channel' => 'AFHAALPUNT',
            'terminal_type' => 'NRS',
            'retail_network_id' => 'PNPNL-01',
        ];

        $formattedOpeningTimes = [
            '0' => [
                '13:30 - 17:15',
            ],
            '1' => [
                '09:00 - 12:00',
                '13:30 - 17:15',
            ],
            '2' =>  [
                '09:00 - 12:00',
                '13:30 - 17:15',
            ],
            '3' => [],
            '4' =>  [
                '09:00 - 12:00',
                '13:30 - 17:15',
            ],
            '5' => [
                '09:00 - 12:00',
                '13:30 - 17:15',
            ],
            '6' => [],
        ];

        $servicePoint = $this->servicePointsClient->getServicePoint(26);

        $this->assertEquals(26, $servicePoint->getId());
        $this->assertEquals('4c8181feec8f49fdbe67d9c9f6aaaf6f', $servicePoint->getCode());
        $this->assertTrue($servicePoint->isActive());
        $this->assertNull($servicePoint->getShopType());
        $this->assertEquals($extraData, $servicePoint->getExtraData());
        $this->assertEquals('DUMMY-3f1d6384391f45ce', $servicePoint->getName());
        $this->assertEquals('Sesamstraat', $servicePoint->getStreet());
        $this->assertEquals('40', $servicePoint->getHouseNumber());
        $this->assertEquals('5699YE', $servicePoint->getPostalCode());
        $this->assertEquals('Eindhoven', $servicePoint->getCity());
        $this->assertEquals('51.440400', $servicePoint->getLatitude());
        $this->assertEquals('5.475800', $servicePoint->getLongitude());
        $this->assertEquals('devnull@sendcloud.nl', $servicePoint->getEmail());
        $this->assertEquals('+31401234567', $servicePoint->getPhone());
        $this->assertEquals('https://www.sendcloud.nl', $servicePoint->getHomepage());
        $this->assertEquals('postnl', $servicePoint->getCarrier());
        $this->assertEquals('NL', $servicePoint->getCountry());
        $this->assertEquals($formattedOpeningTimes, $servicePoint->getFormattedOpeningTimes());
        $this->assertTrue($servicePoint->isOpenTomorrow());
        $this->assertTrue($servicePoint->isOpenUpcomingWeek());
        $this->assertEquals(361, $servicePoint->getDistance());
    }

    public function testGetParcelDocumentErrorsWithAnInvalidDocumentType(): void
    {
        $this->expectExceptionMessage(sprintf('Document type "invalid document type" is not accepted. Valid types: %s.', implode(', ', Parcel::DOCUMENT_TYPES)));
        $this->client->getParcelDocument(1, 'invalid document type', 'invalid content type', 0);
    }

    public function testGetParcelDocumentErrorsWithAnInvalidContentType(): void
    {
        $this->expectExceptionMessage(sprintf('Content type "invalid content type" is not accepted. Valid types: %s.', implode(', ', Parcel::DOCUMENT_CONTENT_TYPES)));
        $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, 'invalid content type', 0);
    }

    public static function contentTypesProvider(): array
    {
        return array_map(static fn (string $value) => [$value], Parcel::DOCUMENT_CONTENT_TYPES);
    }

    /** @dataProvider contentTypesProvider */
    public function testGetParcelDocumentErrorsWithAnDpiPerContentType(string $contentType): void
    {
        $this->expectExceptionMessage(sprintf('DPI "0" is not accepted for "%s". Valid values: %s.', $contentType, implode(', ', Parcel::DOCUMENT_DPI_VALUES[$contentType])));
        $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, $contentType, 0);
    }

    public function testGetParcelDocumentRethrowsTheCorrectException(): void
    {
        $this->guzzleClientMock->method('request')->willThrowException(new TransferException('Whoops'));

        $this->expectException(SendcloudRequestException::class);
        $this->expectExceptionMessage(sprintf('Could not retrieve parcel document "%s" for parcel id "1".', Parcel::DOCUMENT_TYPE_LABEL));

        $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, Parcel::DOCUMENT_CONTENT_TYPE_ZPL, Parcel::DOCUMENT_DPI_203);
    }

    public function testGetParcelDocumentReturnsTheRequestedContent(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            'The ZPL content'
        ));

        $this->assertEquals('The ZPL content', $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, Parcel::DOCUMENT_CONTENT_TYPE_ZPL, Parcel::DOCUMENT_DPI_203));
    }
}
