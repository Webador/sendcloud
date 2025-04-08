<?php

namespace JouwWeb\Sendcloud;

use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
use JouwWeb\Sendcloud\Model\ServicePoint;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client to perform calls on the Sendcloud service points API.
 */
class ServicePointsClient
{
    use HttpClientTrait;

    protected const API_BASE_URL = 'https://servicepoints.sendcloud.sc/api/v2/';

    protected HttpClientInterface $httpClient;

    public function __construct(
        string $publicKey,
        #[\SensitiveParameter]
        string $secretKey,
        #[\SensitiveParameter]
        ?string $partnerId = null,
        ?string $apiBaseUrl = null,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $this->createHttpClient(
            httpClient: $httpClient,
            apiBaseUrl: $apiBaseUrl ?? self::API_BASE_URL,
            publicKey: $publicKey,
            secretKey: $secretKey,
            partnerId: $partnerId,
        );
    }

    /**
     * Summary of searchServicePoints
     *
     * @param string $country A country ISO 2 code (Example : 'NL')
     * @param string|null $address Address of the destination address. Can accept postal code instead of the street and the house number. (Example : 'Stadhuisplein 10')
     * @param string|null $carrier A comma-separated list of carrier codes (stringified) (Example : 'postnl,dpd')
     * @param string|null $city City of the destination address. (Example : 'Eindhoven')
     * @param string|null $houseNumber House number of the destination address. (Example : '10')
     * @param string|null $latitude Used as a reference point to calculate the distance of the service point to the provided location.
     * @param string|null $longitude Used as a reference point to calculate the distance of the service point to the provided location.
     * @param string|null $neLatitude Latitude of the northeast corner of the bounding box.
     * @param string|null $neLongitude Longitude of the northeast corner of the bounding box.
     * @param string|null $postalCode Postal code of the destination address. Using postal_code will return you service points located around that particular postal code. (Example : '5611 EM')
     * @param string|null $pudoId DPD-specific query parameter. (<= 7 characters)
     * @param int|null $radius Radius (in meter) of a bounding circle. Can be used instead of the ne_latitude, ne_longitude, sw_latitude, and sw_longitude parameters to define a bounding box. By default, it’s 100 meters. Minimum value: 100 meters. Maximum value: 50 000 meters.
     * @param string|null $shopType Filters results by their shop type.
     * @param string|null $swLatitude Latitude of the southwest corner of the bounding box.
     * @param string|null $swLongitude Longitude of the southwest corner of the bounding box.
     * @param float|null $weight Weight (in kg.) of the parcel to be shipped to the service points. Certain carriers impose limits for certain service points that cannot accept parcels above a certain weight limit.
     * @return ServicePoint[]
     * @see https://api.sendcloud.dev/docs/sendcloud-public-api/service-points%2Foperations%2Flist-service-points
     */
    public function searchServicePoints(
        string $country,
        ?string $address = null,
        ?string $carrier = null,
        ?string $city = null,
        ?string $houseNumber = null,
        ?string $latitude = null,
        ?string $longitude = null,
        ?string $neLatitude = null,
        ?string $neLongitude = null,
        ?string $postalCode = null,
        ?string $pudoId = null,
        ?int $radius = null,
        ?string $shopType = null,
        ?string $swLatitude = null,
        ?string $swLongitude = null,
        ?float $weight = null
    ): array {
        try {
            // Construct query array
            $query = [];
            $query['country'] = $country;

            if (isset($address)) {
                $query['address'] = $address;
            }
            if (isset($carrier)) {
                $query['carrier'] = $carrier;
            }
            if (isset($city)) {
                $query['city'] = $city;
            }
            if (isset($houseNumber)) {
                $query['house_number'] = $houseNumber;
            }
            if (isset($latitude)) {
                $query['latitude'] = $latitude;
            }
            if (isset($longitude)) {
                $query['longitude'] = $longitude;
            }
            if (isset($neLatitude)) {
                $query['ne_latitude'] = $neLatitude;
            }
            if (isset($neLongitude)) {
                $query['ne_longitude'] = $neLongitude;
            }
            if (isset($postalCode)) {
                $query['postal_code'] = $postalCode;
            }
            if (isset($pudoId)) {
                $query['pudo_id'] = $pudoId;
            }
            if (isset($radius)) {
                $query['radius'] = $radius;
            }
            if (isset($shopType)) {
                $query['shop_type'] = $shopType;
            }
            if (isset($swLatitude)) {
                $query['sw_latitude'] = $swLatitude;
            }
            if (isset($swLongitude)) {
                $query['sw_longitude'] = $swLongitude;
            }
            if (isset($weight)) {
                $query['weight'] = $weight;
            }

            // Send request
            $response = $this->httpClient->request('GET', 'service-points', [
                'query' => $query,
            ]);

            // Decode and create ServicePoint objects
            $servicePoints = [];
            foreach ($response->toArray() as $servicePointData) {
                $servicePoints[] = ServicePoint::fromData($servicePointData);
            }

            return $servicePoints;
        } catch (ExceptionInterface $exception) {
            throw Utility::parseHttpClientException($exception, 'Could not retrieve service point.');
        }
    }

    /**
     * Returns service point by ID.
     *
     * @throws SendcloudRequestException
     * @see https://api.sendcloud.dev/docs/sendcloud-public-api/service-points%2Foperations%2Fget-a-service-point
     */
    public function getServicePoint(ServicePoint|int $servicePoint): ServicePoint
    {
        $servicePointId = $servicePoint instanceof ServicePoint ? $servicePoint->getId() : $servicePoint;

        try {
            $response = $this->httpClient->request('GET', 'service-points/' . $servicePointId);
            return ServicePoint::fromData($response->toArray());
        } catch (ExceptionInterface $exception) {
            throw Utility::parseHttpClientException($exception, 'Could not retrieve service point.');
        }
    }
}
