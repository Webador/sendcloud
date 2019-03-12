<?php

namespace JouwWeb\SendCloud;

use GuzzleHttp\Exception\RequestException;
use JouwWeb\SendCloud\Exception\SendCloudClientException;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use JouwWeb\SendCloud\Exception\SendCloudStateException;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Model\Parcel;
use JouwWeb\SendCloud\Model\SenderAddress;
use JouwWeb\SendCloud\Model\ShippingMethod;
use JouwWeb\SendCloud\Model\User;

/**
 * Client to perform calls on the SendCloud API.
 */
class Client
{
    const API_BASE_URL = 'https://panel.sendcloud.sc/api/v2/';

    /** @var \GuzzleHttp\Client */
    protected $guzzleClient;

    /** @var string */
    protected $publicKey;

    /** @var string */
    protected $secretKey;

    /** @var string|null */
    protected $partnerId;

    public function __construct(
        string $publicKey,
        string $secretKey,
        ?string $partnerId = null,
        ?string $apiBaseUrl = null
    ) {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->partnerId = $partnerId;

        $clientConfig = [
            'base_uri' => $apiBaseUrl ?: self::API_BASE_URL,
            'timeout' => 15,
            'auth' => [
                $publicKey,
                $secretKey,
            ],
        ];

        if ($this->partnerId) {
            $clientConfig['headers'] = [
                'Sendcloud-Partner-Id' => $this->partnerId
            ];
        }

        $this->guzzleClient = new \GuzzleHttp\Client($clientConfig);
    }

    /**
     * Fetches basic details about the SendCloud account.
     *
     * @return User
     * @throws SendCloudClientException
     */
    public function getUser(): User
    {
        try {
            return new User(json_decode($this->guzzleClient->get('user')->getBody()->getContents(), true)['user']);
        } catch (RequestException $exception) {
            throw new SendCloudRequestException(
                'An error occurred while fetching the SendCloud user.',
                SendCloudRequestException::CODE_UNKNOWN,
                $exception
            );
        }
    }

    /**
     * Fetches available SendCloud shipping methods.
     *
     * @return ShippingMethod[]
     * @throws SendCloudClientException
     */
    public function getShippingMethods(): array
    {
        try {
            $response = $this->guzzleClient->get('shipping_methods');
            $shippingMethodsData = json_decode($response->getBody()->getContents(), true)['shipping_methods'];

            $shippingMethods = array_map(function (array $shippingMethodData) {
                return new ShippingMethod($shippingMethodData);
            }, $shippingMethodsData);

            // Sort by carrier and name
            usort($shippingMethods, function (ShippingMethod $method1, ShippingMethod $method2) {
                if ($method1->getCarrier() !== $method2->getCarrier()) {
                    return strcasecmp($method1->getCarrier(), $method2->getCarrier());
                }

                return strcasecmp($method1->getName(), $method2->getName());
            });

            return $shippingMethods;
        } catch (RequestException $exception) {
            throw $this->marshalRequestException(
                $exception,
                'An error occurred while fetching shipping methods from the SendCloud API.'
            );
        }
    }

    /**
     * Creates a parcel in SendCloud.
     *
     * @param Address $shippingAddress Address to be shipped to.
     * @param int|null $servicePointId The order will be shipped to the service point if supplied. $shippingAddress is
     * still required as it will be printed on the label.
     * @param string|null $orderNumber
     * @param int|null $weight Weight of the parcel in grams. The default set in SendCloud will be used if null or zero.
     * @return Parcel
     * @throws SendCloudRequestException
     */
    public function createParcel(
        Address $shippingAddress,
        ?int $servicePointId,
        ?string $orderNumber = null,
        ?int $weight = null
    ): Parcel {
        $parcelData = $this->getParcelData(null, $shippingAddress, $servicePointId, $orderNumber, $weight, false, null, null);

        try {
            $response = $this->guzzleClient->post('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return new Parcel(json_decode($response->getBody()->getContents(), true)['parcel']);
        } catch (RequestException $exception) {
            throw $this->marshalRequestException($exception, 'Could not create parcel in SendCloud.');
        }
    }

    /**
     * Update details of an existing parcel.
     *
     * @param int $parcelId An existing parcel's ID.
     * @param Address $shippingAddress
     * @return Parcel
     * @throws SendCloudRequestException
     */
    public function updateParcel(int $parcelId, Address $shippingAddress): Parcel
    {
        $parcelData = $this->getParcelData($parcelId, $shippingAddress, null, null, null, false, null, null);

        try {
            $response = $this->guzzleClient->put('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return new Parcel(json_decode($response->getBody()->getContents(), true)['parcel']);
        } catch (RequestException $exception) {
            throw $this->marshalRequestException($exception, 'Could not update parcel in SendCloud.');
        }
    }

    public function createLabel(int $parcelId, int $shippingMethodId, $senderAddressIdOrAddress): Parcel
    {
        $parcelData = $this->getParcelData(
            $parcelId,
            null,
            null,
            null,
            null,
            true,
            $shippingMethodId,
            $senderAddressIdOrAddress
        );

        try {
            $response = $this->guzzleClient->put('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return new Parcel(json_decode($response->getBody()->getContents(), true)['parcel']);
        } catch (RequestException $exception) {
            throw $this->marshalRequestException($exception, 'Could not create parcel with SendCloud.');
        }
    }

    /**
     * Cancels a parcel. Returns whether the parcel was successfully cancelled.
     *
     * @param int $parcelId
     * @return bool
     * @throws SendCloudRequestException
     */
    public function cancelParcel(int $parcelId): bool
    {
        try {
            $this->guzzleClient->post(sprintf('parcels/%s/cancel', $parcelId));
            return true;
        } catch (RequestException $exception) {
            $statusCode = $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : 0;

            // Handle documented rejections
            if (in_array($statusCode, [400, 410])) {
                return false;
            }

            throw $this->marshalRequestException($exception, 'An error occurred while cancelling the parcel.');
        }
    }

    /**
     * Fetches the PDF label for the given parcel, The parcel must already have a label created.
     *
     * @param Parcel|int $parcelOrParcelId
     * @param int $format `Parcel::LABEL_FORMATS`
     * @return string PDF data.
     * @throws SendCloudClientException
     * @throws SendCloudRequestException
     * @throws SendCloudStateException
     */
    public function getLabelPdf($parcelOrParcelId, int $format): string
    {
        if (!in_array($format, Parcel::LABEL_FORMATS)) {
            throw new \InvalidArgumentException('Invalid label format given.');
        }

        if (is_int($parcelOrParcelId)) {
            $parcel = $this->getParcel($parcelOrParcelId);
        } elseif ($parcelOrParcelId instanceof Parcel) {
            $parcel = $parcelOrParcelId;
        } else {
            throw new \InvalidArgumentException('parcel must be an integer or a Parcel.');
        }

        $labelUrl = $parcel->getLabelUrl($format);

        if (!$labelUrl) {
            throw new SendCloudStateException('SendCloud parcel does not have any labels.');
        }

        try {
            return $this->guzzleClient->get($labelUrl)->getBody()->getContents();
        } catch (RequestException $exception) {
            throw $this->marshalRequestException($exception, 'Could not retrieve label.');
        }
    }

    /**
     * Fetches the sender addresses configured in SendCloud.
     *
     * @return SenderAddress[]
     * @throws SendCloudRequestException
     */
    public function getSenderAddresses(): array
    {
        try {
            $response = $this->guzzleClient->get('user/addresses/sender');
            $senderAddressesData = json_decode($response->getBody()->getContents(), true)['sender_addresses'];

            return array_map(function (array $senderAddressData) {
                return new SenderAddress($senderAddressData);
            }, $senderAddressesData);
        } catch (RequestException $exception) {
            throw $this->marshalRequestException($exception, 'Could not retrieve sender addresses.');
        }
    }

    /**
     * @param int $parcelId
     * @return Parcel
     * @throws SendCloudClientException
     */
    public function getParcel(int $parcelId): Parcel
    {
        try {
            $response = $this->guzzleClient->get('parcels/' . $parcelId);
            return new Parcel(json_decode($response->getBody()->getContents(), true)['parcel']);
        } catch (RequestException $exception) {
            throw $this->marshalRequestException($exception, 'Could not retrieve parcel.');
        }
    }

    /**
     * Returns the given arguments as data in SendCloud parcel format.
     *
     * @param int|null $parcelId
     * @param Address|null $shippingAddress
     * @param string|null $servicePointId
     * @param string|null $orderNumber
     * @param int|null $weight
     * @param bool $requestLabel
     * @param int|null $shippingMethodId Required if requesting a label.
     * @param int|Address $senderAddressIdOrAddress Passing null will pick SendCloud's default
     * @return mixed[]
     */
    protected function getParcelData(
        ?int $parcelId,
        ?Address $shippingAddress,
        ?string $servicePointId,
        ?string $orderNumber,
        ?int $weight,
        bool $requestLabel,
        ?int $shippingMethodId,
        $senderAddressIdOrAddress
    ): array {
        $parcelData = [];

        if ($parcelId) {
            $parcelData['id'] = $parcelId;
        }

        if ($shippingAddress) {
            $parcelData = array_merge($parcelData, [
                'name' => $shippingAddress->getName(),
                'company_name' => $shippingAddress->getCompanyName(),
                'address' => $shippingAddress->getStreet(),
                'house_number' => $shippingAddress->getHouseNumber(),
                'city' => $shippingAddress->getCity(),
                'postal_code' => $shippingAddress->getPostalCode(),
                'country' => $shippingAddress->getCountryCode(),
                'email' => $shippingAddress->getEmailAddress(),
                'telephone' => $shippingAddress->getPhoneNumber(),
            ]);
        }

        if ($servicePointId) {
            $parcelData['to_service_point'] = $servicePointId;
        }

        if ($orderNumber) {
            $parcelData['order_number'] = $orderNumber;
        }

        if ($weight) {
            $parcelData['weight'] = ceil($weight / 1000);
        }

        if (!$requestLabel) {
            return $parcelData;
        }

        // Additional fields are added when requesting a label
        $parcelData['request_label'] = true;

        // Sender address
        if (is_int($senderAddressIdOrAddress)) {
            /** @var int $senderAddressIdOrAddress */
            $parcelData['sender_address'] = $senderAddressIdOrAddress;
        } elseif ($senderAddressIdOrAddress instanceof Address) {
            /** @var Address $senderAddressIdOrAddress */
            $parcelData = array_merge($parcelData, [
                'from_name' => $senderAddressIdOrAddress->getName(),
                'from_company_name' => $senderAddressIdOrAddress->getCompanyName(),
                'from_address_1' => $senderAddressIdOrAddress->getStreet(),
                'from_address_2' => '',
                'from_house_number' => $senderAddressIdOrAddress->getHouseNumber(),
                'from_city' => $senderAddressIdOrAddress->getCity(),
                'from_postal_code' => $senderAddressIdOrAddress->getPostalCode(),
                'from_country' => $senderAddressIdOrAddress->getCountryCode(),
                'from_telephone' => $senderAddressIdOrAddress->getPhoneNumber(),
                'from_email' => $senderAddressIdOrAddress->getEmailAddress(),
            ]);
        } elseif ($senderAddressIdOrAddress !== null) {
            throw new \InvalidArgumentException(
                '$senderAddressIdOrAddress must be an integer, an Address or null when requesting a label.'
            );
        }

        // Shipping method
        if (!$shippingMethodId) {
            throw new \InvalidArgumentException(
                '$shippingMethodId must be passed when requesting a label.'
            );
        }

        $parcelData['shipment'] = [
            'id' => $shippingMethodId,
        ];

        return $parcelData;
    }

    protected function marshalRequestException(
        RequestException $exception,
        string $defaultMessage
    ): SendCloudRequestException {
        $message = $defaultMessage;
        $code = SendCloudRequestException::CODE_UNKNOWN;

        // Precondition failed, parse response message to determine code of exception
        if ($exception->getCode() === 412) {
            $message = 'SendCloud account is not fully configured yet.';

            $responseMessage = json_decode($exception->getResponse()->getBody()->getContents(), true)['error']['message'];
            if (stripos($responseMessage, 'no address data') !== false) {
                $code = SendCloudRequestException::CODE_NO_ADDRESS_DATA;
            } elseif (stripos($responseMessage, 'not allowed to announce') !== false) {
                $code = SendCloudRequestException::CODE_NOT_ALLOWED_TO_ANNOUNCE;
            }
        }

        return new SendCloudRequestException($message, $code, $exception);
    }
}
