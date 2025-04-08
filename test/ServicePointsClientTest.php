<?php

namespace Test\JouwWeb\Sendcloud;

use JouwWeb\Sendcloud\ServicePointsClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ServicePointsClientTest extends TestCase
{
    private ServicePointsClient $servicePointsClient;

    public function setUp(): void
    {
        $this->servicePointsClient = new ServicePointsClient(
            publicKey: 'handsome public key',
            secretKey: 'gorgeous secret key',
            partnerId: 'aPartnerId',
            httpClient: new MockHttpClient(),
        );
    }

    public function testSearchServicePoints(): void
    {
        $response = new MockResponse('[{"id":1,"code":"217165","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"Media Markt Eindhoven Centrum B.V.","street":"Boschdijktunnel","house_number":"1","postal_code":"5611AG","city":"EINDHOVEN","latitude":"51.441444","longitude":"5.475185","email":"","phone":"","homepage":"","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["10:00 - 20:00"],"1":["10:00 - 20:00"],"2":["10:00 - 20:00"],"3":["10:00 - 20:00"],"4":["10:00 - 20:00"],"5":["10:00 - 18:00"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true},{"id":2,"code":"217165","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"Media Markt Eindhoven Centrum B.V.","street":"Boschdijktunnel","house_number":"1","postal_code":"5611AG","city":"EINDHOVEN","latitude":"51.441444","longitude":"5.475185","email":"","phone":"","homepage":"","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["10:00 - 20:00"],"1":["10:00 - 20:00"],"2":["10:00 - 20:00"],"3":["10:00 - 20:00"],"4":["10:00 - 20:00"],"5":["10:00 - 18:00"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true}]');
        $this->configureApiResponse($response);

        $servicePoints = $this->servicePointsClient->searchServicePoints(country: 'NL');

        $this->assertEquals('https://servicepoints.sendcloud.sc/api/v2/service-points?country=NL', $response->getRequestUrl());
        $this->assertCount(2, $servicePoints);
        $this->assertEquals(1, $servicePoints[0]->getId());
        $this->assertEquals(2, $servicePoints[1]->getId());
    }

    public function testSearchServicePointWithDistance(): void
    {
        $response = new MockResponse('[{"id":1,"code":"217165","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"Media Markt Eindhoven Centrum B.V.","street":"Boschdijktunnel","house_number":"1","postal_code":"5611AG","city":"EINDHOVEN","latitude":"51.441444","longitude":"5.475185","email":"","phone":"","homepage":"","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["10:00 - 20:00"],"1":["10:00 - 20:00"],"2":["10:00 - 20:00"],"3":["10:00 - 20:00"],"4":["10:00 - 20:00"],"5":["10:00 - 18:00"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true,"distance":381}]');
        $this->configureApiResponse($response);

        $servicePoints = $this->servicePointsClient->searchServicePoints(country: 'NL', latitude: 0, longitude: 0);

        $this->assertEquals('https://servicepoints.sendcloud.sc/api/v2/service-points?country=NL&latitude=0&longitude=0', $response->getRequestUrl());
        $this->assertCount(1, $servicePoints);
        $this->assertEquals(1, $servicePoints[0]->getId());
        $this->assertNotNull($servicePoints[0]->getDistance());
    }

    public function testGetServicePoint(): void
    {
        $response = new MockResponse('{"id":26,"code":"4c8181feec8f49fdbe67d9c9f6aaaf6f","is_active":true,"shop_type":null,"extra_data":{"partner_name":"PostNL","sales_channel":"AFHAALPUNT","terminal_type":"NRS","retail_network_id":"PNPNL-01"},"name":"DUMMY-3f1d6384391f45ce","street":"Sesamstraat","house_number":"40","postal_code":"5699YE","city":"Eindhoven","latitude":"51.440400","longitude":"5.475800","email":"devnull@sendcloud.nl","phone":"+31401234567","homepage":"https://www.sendcloud.nl","carrier":"postnl","country":"NL","formatted_opening_times":{"0":["13:30 - 17:15"],"1":["09:00 - 12:00","13:30 - 17:15"],"2":["09:00 - 12:00","13:30 - 17:15"],"3":[],"4":["09:00 - 12:00","13:30 - 17:15"],"5":["09:00 - 12:00","13:30 - 17:15"],"6":[]},"open_tomorrow":true,"open_upcoming_week":true,"distance":361}');
        $this->configureApiResponse($response);

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

        $this->assertEquals('https://servicepoints.sendcloud.sc/api/v2/service-points/26', $response->getRequestUrl());
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

    private function configureApiResponse(MockResponse|array $responses): void
    {
        $httpClientProperty = new \ReflectionProperty($this->servicePointsClient, 'httpClient');
        /** @var MockHttpClient $httpClient */
        $httpClient = $httpClientProperty->getValue($this->servicePointsClient);
        $httpClient->setResponseFactory($responses);
    }
}
