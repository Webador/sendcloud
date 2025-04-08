<?php

namespace Test\JouwWeb\Sendcloud;

use JouwWeb\Sendcloud\Client;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
use JouwWeb\Sendcloud\Model\Address;
use JouwWeb\Sendcloud\Model\Parcel;
use JouwWeb\Sendcloud\Model\ParcelItem;
use JouwWeb\Sendcloud\Model\ShippingMethod;
use JouwWeb\Sendcloud\Model\ShippingProduct;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ClientTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        $this->client = new Client(
            publicKey: 'handsome public key',
            secretKey: 'gorgeous secret key',
            partnerId: 'aPartnerId',
            // See configureResponses().
            httpClient: new MockHttpClient(),
        );
    }

    public function testGetUser(): void
    {
        $response = new MockResponse('{"user":{"address":"Insulindelaan 115","city":"Eindhoven","company_logo":null,"company_name":"Sendcloud","data":[],"email":"johndoe@sendcloud.nl","invoices":[{"date":"05-06-201811:58:52","id":1,"isPayed":false,"items":"https://local.sendcloud.sc/api/v2/user/invoices/1","price_excl":77.4,"price_incl":93.65,"ref":"1","type":"periodic"}],"modules":[{"activated":true,"id":5,"name":"SendcloudClient","settings":null,"short_name":"sendcloud_client"},{"id":3,"name":"PrestashopIntegration","settings":{"url_webshop":"http://localhost/testing/prestashop","api_key":"O8ALXHMM24QULWM213CC6SGQ5VDJKC8W"},"activated":true,"short_name":"prestashop"}],"postal_code":"5642CV","registered":"2018-05-2912:52:51","telephone":"+31626262626","username":"johndoe"}}');
        $this->configureApiResponse($response);

        $user = $this->client->getUser();

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/user', $response->getRequestUrl());
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
        $response = new MockResponse('{"shipping_methods":[{"service_point_input":"none","min_weight":"0.001","max_weight":"1.001","name":"Low weight shipment","carrier":"carrier_code","countries":[{"iso_2":"BE","iso_3":"BEL","id":1,"price":3.50,"name":"Belgium"},{"iso_2":"NL","iso_3":"NLD","id":2,"price":4.20,"name":"Netherlands"}],"min_weight":"0.001","id":1,"price":0}]}');
        $this->configureApiResponse($response);

        $shippingMethods = $this->client->getShippingMethods();

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/shipping_methods?sender_address=all', $response->getRequestUrl());
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
        $response = new MockResponse('{"shipping_methods":[{"service_point_input":"none","max_weight":"1.000","name":"Low weight shipment","carrier":"carrier_code","countries":[{"iso_2":"BE","iso_3":"BEL","id":1,"price":3.50,"name":"Belgium"},{"iso_2":"NL","iso_3":"NLD","id":2,"price":4.20,"name":"Netherlands"}],"min_weight":"0.001","id":1,"price":0}]}');
        $this->configureApiResponse($response);

        $this->client->getShippingMethods(10, 11, true);

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/shipping_methods?service_point_id=10&sender_address=11&is_return=true', $response->getRequestUrl());
    }

    public function testGetShippingProducts(): void
    {
        $shippingProduct1 = '{"name":"Shipping product 1","carrier":"carrier_code_1","available_functionalities":{"last_mile":["home_delivery"],"returns":[false]},"methods":[{"id":2,"name":"B- Heavy weight shipment","properties":{"min_weight":51,"max_weight":1001}},{"id":1,"name":"A- Low weight shipment","properties":{"min_weight":1,"max_weight":51}}],"weight_range":{"min_weight":1,"max_weight":1001}}';
        $shippingProduct2 = '{"name":"Shipping product 2","carrier":"carrier_code_2","available_functionalities":{"last_mile":["service_point"],"returns":[true]},"methods":[{"id":3,"name":"C- Heavy weight shipment","properties":{"min_weight":1000,"max_weight":2001}}],"weight_range":{"min_weight":1000,"max_weight":2001}}';
        $response = new MockResponse(sprintf('[%s,%s]', $shippingProduct1, $shippingProduct2));
        $this->configureApiResponse($response);

        $shippingProducts = $this->client->getShippingProducts(fromCountry: 'NL');

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/shipping-products?from_country=NL', $response->getRequestUrl());

        // All shipping products should be in result
        $this->assertCount(2, $shippingProducts);

        // All shipping methods should be in result, inside the different shipping products
        $this->assertCount(2, $shippingProducts[0]->getMethods());
        $this->assertCount(1, $shippingProducts[1]->getMethods());

        $this->assertEquals(['Shipping product 1', 'Shipping product 2'], array_map(fn ($product) => $product->getName(), $shippingProducts));

        $this->assertEquals(1, $shippingProducts[0]->getMinimumWeight());
        $this->assertEquals(1001, $shippingProducts[0]->getMaximumWeight());
        $this->assertEquals('carrier_code_1', $shippingProducts[0]->getCarrier());
        $this->assertEquals(false, $shippingProducts[0]->getWithReturn());
        $this->assertEquals(true, $shippingProducts[1]->getWithReturn());

        // Shipping methods order should be ascending by their name
        $this->assertEquals(['A- Low weight shipment', 'B- Heavy weight shipment'], array_map(fn ($product) => $product->getName(), $shippingProducts[0]->getMethods()));

        $this->assertFalse($shippingProducts[0]->getAllowServicePoints());
        $this->assertTrue($shippingProducts[1]->getAllowServicePoints());

        // Prices should be empty
        $this->assertEquals([], $shippingProducts[0]->getMethods()[0]->getPrices());
        $this->assertNull($shippingProducts[0]->getMethods()[0]->getPriceForCountry('EN'));
    }

    public function testGetShippingProductsCaseAllOptionalArguments(): void
    {
        $response = new MockResponse('[{"name":"Shipping product 2","carrier":"carrier_code_2","available_functionalities":{"last_mile":["service_point"]},"methods":[{"id":2,"name":"B- Heavy weight shipment","properties":{"min_weight":1000,"max_weight":2001}}],"weight_range":{"min_weight":1000,"max_weight":2001}}]');
        $this->configureApiResponse($response);

        $shippingMethods = $this->client->getShippingProducts(
            'NL',
            ShippingProduct::DELIVERY_MODE_SERVICE_POINT,
            'EN',
            'carrier_code_2',
            1500,
            ShippingProduct::WEIGHT_UNIT_GRAM,
            1
        );

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/shipping-products?from_country=NL&last_mile=service_point&to_country=EN&carrier=carrier_code_2&weight=1500&weight_unit=gram&returns=1', $response->getRequestUrl());
        $this->assertCount(1, $shippingMethods);
    }

    public function testGetShippingProductsCaseEmptyResponse(): void
    {
        $response = new MockResponse('[]');
        $this->configureApiResponse($response);

        $shippingMethods = $this->client->getShippingProducts(fromCountry: 'NL');

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/shipping-products?from_country=NL', $response->getRequestUrl());
        $this->assertCount(0, $shippingMethods);
    }

    public function testGetShippingProductsCaseBadArgumentDeliveryMode(): void
    {
        $this->expectExceptionMessage('Delivery mode "abc" is not available to get shipping products.');

        $this->client->getShippingProducts(
            fromCountry: 'NL',
            deliveryMode: 'abc',
        );
    }

    public function testGetShippingProductsCaseArgumentWeightUnitMissing(): void
    {
        $this->expectExceptionMessage('Weight unit is needed to get shipping products.');

        $this->client->getShippingProducts(
            fromCountry: 'NL',
            weight: 1500,
        );
    }

    public function testGetShippingProductsCaseBadArgumentWeightUnit(): void
    {
        $this->expectExceptionMessage('Weight unit "ton" provided is not available to get shipping products.');

        $this->client->getShippingProducts(
            fromCountry: 'NL',
            weight: 1500,
            weightUnit: 'ton',
        );
    }

    public function testGetSenderAddresses(): void
    {
        $response = new MockResponse('{"sender_addresses":[{"id":92837,"company_name":"AwesomeCo Inc.","contact_name":"Bertus Bernardus","email":"bertus@awesomeco.be","telephone":"+31683749586","street":"Wegstraat","house_number":"233","postal_box":"","postal_code":"8398","city":"Brussel","country":"BE"},{"id":28397,"company_name":"AwesomeCo Inc. NL","contact_name":"","email":"","telephone":"0645000000","street":"Torenallee","house_number":"20","postal_box":"","postal_code":"5617 BC","city":"Eindhoven","country":"NL"}]}');
        $this->configureApiResponse($response);

        $senderAddresses = $this->client->getSenderAddresses();

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/user/addresses/sender', $response->getRequestUrl());
        $this->assertCount(2, $senderAddresses);
        $this->assertEquals(92837, $senderAddresses[0]->getId());
        $this->assertEquals('AwesomeCo Inc.', $senderAddresses[0]->getCompanyName());
        $this->assertEquals('', $senderAddresses[1]->getContactName());
    }

    public function testCreateParcel(): void
    {
        $response = new MockResponse('{"parcel":{"id":8293794,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}}');
        $this->configureApiResponse($response);

        $parcel = $this->client->createParcel(
            new Address('Baron van der Zanden', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', 'Blok 3', 'CA'),
            null,
            '201900001',
            2486
        );

        $this->assertEquals('POST', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels', $response->getRequestUrl());
        $this->assertEquals('{"parcel":{"name":"Baron van der Zanden","company_name":"","address":"straat","address_2":"CA","house_number":"baron@vanderzanden.nl","city":"23","postal_code":"Gehucht","country":"9283DD","email":"NL","telephone":"Blok 3","country_state":"","order_number":"201900001","weight":"2.486"}}', $response->getRequestOptions()['body']);
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
        $response = new MockResponse('{"parcel":{"id":8293794,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001","errors":{"name":"This field is required."}}}');
        $this->configureApiResponse($response);

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

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels?errors=verbose', $response->getRequestUrl());
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
        $parcel1Json = '{"id":8293794,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}';
        $parcel2Json = '{"id":8293795,"address":"straat 23","address_2":"Blok 3","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CA","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}';
        $response = new MockResponse(sprintf('{"parcels":[%s,%s]}', $parcel1Json, $parcel2Json));
        $this->configureApiResponse($response);

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
        $parcelJson = '{"name":"Baron van der Zanden","company_name":"","address":"straat","address_2":"CA","house_number":"23","city":"Gehucht","postal_code":"9283DD","country":"NL","email":"baron@vanderzanden.nl","telephone":"Blok 3","country_state":"","order_number":"201900001","weight":"2.486","request_label":true,"shipment":{"id":1},"quantity":2}';
        $response = new MockResponse(sprintf('{"parcels":[],"failed_parcels":[{"parcel":%s,"errors":{"name":["This field is required."]}}]}', $parcelJson));
        $this->configureApiResponse($response);

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

        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels?errors=verbose', $response->getRequestUrl());
        $this->assertCount(0, $parcels);
    }

    public function testCreateParcelCustoms(): void
    {
        $response = new MockResponse('{"parcel":{"id":36054805,"address":"Street 123","address_2":"Unit 83","address_divided":{"house_number":"123","street":"Street"},"city":"Place","company_name":"","country":{"iso_2":"BM","iso_3":"BMU","name":"Bermuda"},"data":{},"date_created":"06-02-2020 21:33:13","email":"drcoffee@drcoffee.dr","name":"Dr. Coffee","postal_code":"7837","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"1.000","label":{},"customs_declaration":{},"order_number":"","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"customsInvoiceNumber","customs_shipment_type":2,"parcel_items":[{"description":"cardboard","quantity":3,"weight":"0.050","value":"0.20","hs_code":"090210","origin_country":"NL","product_id":"","properties":{},"sku":"","return_reason":null,"return_message":null},{"description":"green tea","quantity":1,"weight":"0.123","value":"15.20","hs_code":"090210","origin_country":"EC","product_id":"Product2839","properties":{"propertyKey":"propertyValue"},"sku":"SKUSKUSKU","return_reason":null,"return_message":null}],"documents":[],"type":null,"shipment_uuid":"f893c98c-43a6-49bb-9dda-9bf3e76a87ad","shipping_method":null,"external_order_id":"36054805","external_shipment_id":"","external_reference":null,"is_return":false,"note":""}}');
        $this->configureApiResponse($response);

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

        $this->assertEquals('POST', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels', $response->getRequestUrl());
        $this->assertEquals('{"parcel":{"name":"Dr. Coffee","company_name":"","address":"Street","address_2":"Unit 83","house_number":"123","city":"Place","postal_code":"7837","country":"BM","email":"drcoffee@drcoffee.dr","telephone":"","country_state":"","customs_invoice_nr":"customsInvoiceNumber","customs_shipment_type":2,"parcel_items":[{"description":"green tea","quantity":1,"weight":"0.123","value":15.2,"hs_code":"090210","origin_country":"EC"},{"description":"cardboard","quantity":3,"weight":"0.050","value":0.2,"hs_code":"090210","origin_country":"NL","sku":"SKUSKUSKU","product_id":"Product2839","properties":{"propertyKey":"propertyValue"}}]}}', $response->getRequestOptions()['body']);
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

    /**
     * Verifies that update only updates the address details (and not e.g., order number/weight)
     */
    public function testUpdateParcel(): void
    {
        $response = new MockResponse('{"parcel":{"id":8293794,"address":"Rosebud 2134 A","address_2":"Above the skies","address_divided":{"street":"Rosebud","house_number":"2134"},"city":"Almanda","company_name":"Some company","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"completelydifferent@email.com","name":"Completely different person","postal_code":"9238DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"+31699999999","tracking_number":"","weight":"2.490","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":"CS","customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}}');
        $this->configureApiResponse($response);

        $parcel = $this->client->updateParcel(8293794, new Address('Completely different person', 'Some company', 'Rosebud', 'Almanda', '9238DD', 'NL', 'completelydifferent@email.com', '2134A', '+31699999999', 'Above the skies', 'CS'));

        $this->assertEquals('PUT', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels', $response->getRequestUrl());
        $this->assertEquals('{"parcel":{"id":8293794,"name":"Completely different person","company_name":"Some company","address":"Rosebud","address_2":"Above the skies","house_number":"2134A","city":"Almanda","postal_code":"9238DD","country":"NL","email":"completelydifferent@email.com","telephone":"+31699999999","country_state":"CS"}}', $response->getRequestOptions()['body']);
        $this->assertEquals('Some company', $parcel->getAddress()->getCompanyName());
    }

    public function testCreateLabel(): void
    {
        $response = new MockResponse('{"parcel":{"id":8293794,"address":"Rosebud 2134 A","address_2":"","address_divided":{"street":"Rosebud","house_number":"2134"},"city":"Almanda","company_name":"Some company","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"completelydifferent@email.com","name":"Completely different person","postal_code":"9238 DD","reference":"0","shipment":{"id":117,"name":"DHLForYou Drop Off"},"status":{"id":1000,"message":"Ready to send"},"to_service_point":null,"telephone":"+31699999999","tracking_number":"JVGL4004421100020097","weight":"2.490","label":{"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer/8293794","normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=3"]},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":"parcel","shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":117,"external_order_id":"8293794","external_shipment_id":"201900001","carrier":{"code":"dhl"},"tracking_url":"https://jouwweb.shipping-portal.com/tracking/?country=nl&tracking_number=jvgl4004421100020097&postal_code=9238dd"}}');
        $this->configureApiResponse($response);

        $parcel = $this->client->createLabel(8293794, 117, 61361);

        $this->assertEquals('PUT', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels', $response->getRequestUrl());
        $this->assertEquals('{"parcel":{"id":8293794,"shipment":{"id":117},"sender_address":61361,"request_label":true}}', $response->getRequestOptions()['body']);
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
        $response = new MockResponse('{"parcel":{"id":2784972,"address":"Teststraat 12 A10","address_2":"","address_divided":{"street":"Teststraat","house_number":"12"},"city":"Woonplaats","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"27-08-2018 11:32:04","email":"sjoerd@jouwweb.nl","name":"Sjoerd Nuijten","postal_code":"7777 AA","reference":"0","shipment":{"id":8,"name":"Unstamped letter"},"status":{"id":11,"message":"Delivered"},"to_service_point":null,"telephone":"","tracking_number":"3SYZXG192833973","weight":"1.000","label":{"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer/13846453","normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=3"]},"customs_declaration":{},"order_number":"201806006","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":"parcel","shipment_uuid":"cb1e0f2d-4e7f-456b-91fe-0bcf09847d10","shipping_method":39,"external_order_id":"2784972","external_shipment_id":"201806006","carrier":{"code":"postnl"},"tracking_url":"https://tracking.sendcloud.sc/forward?carrier=postnl&code=3SYZXG192833973&destination=NL&lang=nl&source=NL&type=parcel&verification=7777AA"}}');
        $this->configureApiResponse($response);

        $parcel = $this->client->getParcel(2784972);

        $this->assertEquals('GET', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels/2784972', $response->getRequestUrl());
        $this->assertEquals(2784972, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_DELIVERED, $parcel->getStatusId());
    }

    public function testCancelParcel(): void
    {
        $response1 = new MockResponse('{"status":"deleted","message":"Parcel has been deleted"}');
        $response2 = new MockResponse('{"status":"failed","message":"Shipped parcels, or parcels being shipped, can no longer be cancelled."}', [
            'http_code' => 400,
        ]);
        $this->configureApiResponse([$response1, $response2]);

        $this->assertTrue($this->client->cancelParcel(8293794));
        $this->assertEquals('POST', $response1->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels/8293794/cancel', $response1->getRequestUrl());

        $this->assertFalse($this->client->cancelParcel(2784972));
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels/2784972/cancel', $response2->getRequestUrl());
    }

    public function testParseRequestException(): void
    {
        $response = new MockResponse('{"error":{"message":"Invalid username/password.","request":"api/v2/user","code":401}}', [
            'http_code' => 401,
        ]);
        $this->configureApiResponse($response);

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
        $response = new MockResponse('');
        $response->cancel();
        $this->configureApiResponse($response);

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
        $response = new MockResponse('{"url":"https://awesome.shipping-portal.com/returns/initiate/HocusBogusPayloadPath/"}');
        $this->configureApiResponse($response);

        $this->assertEquals(
            'https://awesome.shipping-portal.com/returns/initiate/HocusBogusPayloadPath/',
            $this->client->getReturnPortalUrl(9265)
        );
        $this->assertEquals('GET', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels/9265/return_portal_url', $response->getRequestUrl());
    }

    public function testGetReturnPortalUrlNotFound(): void
    {
        $response = new MockResponse('{"url":"https://awesome.shipping-portal.com/returns/initiate/HocusBogusPayloadPath/"}', [
            'http_code' => 404,
        ]);
        $this->configureApiResponse($response);

        $this->assertNull($this->client->getReturnPortalUrl(9265));
        $this->assertEquals('GET', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels/9265/return_portal_url', $response->getRequestUrl());

    }

    public function testGetBulkLabelPdf(): void
    {
        $response1 = new MockResponse('{"label":{"normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=3"],"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer?ids=1234,4321"}}');
        $response2 = new MockResponse('pdfdata');
        $this->configureApiResponse([$response1, $response2]);

        $parcelMock = $this->createMock(Parcel::class);
        $parcelMock->method('getId')->willReturn(4321);

        $pdf = $this->client->getBulkLabelPdf([1234, $parcelMock], Parcel::LABEL_FORMAT_A4_TOP_LEFT);

        $this->assertEquals('{"label":{"parcels":[1234,4321]}}', $response1->getRequestOptions()['body']);
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/labels/normal_printer?ids=1234,4321&start_from=0', $response2->getRequestUrl());
        $this->assertEquals('pdfdata', $pdf);
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

    #[DataProvider('contentTypesProvider')]
    public function testGetParcelDocumentErrorsWithAnDpiPerContentType(string $contentType): void
    {
        $this->expectExceptionMessage(sprintf('DPI "0" is not accepted for "%s". Valid values: %s.', $contentType, implode(', ', Parcel::DOCUMENT_DPI_VALUES[$contentType])));
        $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, $contentType, 0);
    }

    public static function contentTypesProvider(): array
    {
        return array_map(static fn (string $value) => [$value], Parcel::DOCUMENT_CONTENT_TYPES);
    }

    public function testGetParcelDocumentRethrowsTheCorrectException(): void
    {
        $response = new MockResponse('', ['http_code' => 400]);
        $this->configureApiResponse($response);

        $this->expectException(SendcloudRequestException::class);
        $this->expectExceptionMessage(sprintf('Could not retrieve parcel document "%s" for parcel id "1".', Parcel::DOCUMENT_TYPE_LABEL));

        $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, Parcel::DOCUMENT_CONTENT_TYPE_ZPL, Parcel::DOCUMENT_DPI_203);
    }

    public function testGetParcelDocumentReturnsTheRequestedContent(): void
    {
        $response = new MockResponse('The ZPL content');
        $this->configureApiResponse($response);

        $this->assertEquals('The ZPL content', $this->client->getParcelDocument(1, Parcel::DOCUMENT_TYPE_LABEL, Parcel::DOCUMENT_CONTENT_TYPE_ZPL, Parcel::DOCUMENT_DPI_203));
        $this->assertEquals('GET', $response->getRequestMethod());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/parcels/1/documents/label?dpi=203', $response->getRequestUrl());
    }

    /**
     * Responses need to be configured directly on the client's property because the original client is cloned (via
     * {@see HttpClientInterface::withOptions()}).
     *
     * @param MockResponse|MockResponse[] $responses
     */
    private function configureApiResponse(MockResponse|array $responses): void
    {
        $httpClientProperty = new \ReflectionProperty($this->client, 'httpClient');
        /** @var MockHttpClient $httpClient */
        $httpClient = $httpClientProperty->getValue($this->client);
        $httpClient->setResponseFactory($responses);
    }
}
