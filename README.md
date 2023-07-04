# Sendcloud

This is a PHP library that provides a simple way to communicate with the Sendcloud API. It was created because there
were no simple alternatives that follow good object-oriented code practices.

> NOTE: This library does not implement all Sendcloud API functionality. If you require functionality that is missing
please request it through a GitHub issue or pull request.

## Example

```php
use JouwWeb\Sendcloud\Client;
use JouwWeb\Sendcloud\Model\Address;
use JouwWeb\Sendcloud\Model\Parcel;
use JouwWeb\Sendcloud\Model\ParcelItem;
use JouwWeb\Sendcloud\Model\WebhookEvent;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;

$client = new Client('your_public_key', 'your_secret_key');

// Print prices for all enabled shipping methods that ship to the Netherlands
foreach ($client->getShippingMethods() as $shippingMethod) {
    $price = $shippingMethod->getPriceForCountry('NL');
    if ($price) {
        echo $shippingMethod->getName() . ': €' . ($price / 100) . PHP_EOL;
    }
}

// Create a parcel and label
try {
    // Most of these arguments are optional and will fall back to defaults configured in Sendcloud
    $parcel = $client->createParcel(
        new Address('Customer name', 'Customer company name', 'Customer street', '4A', 'City', '9999ZZ', 'NL', 'test@test.test', '+31612345678'),
        null, // Service point ID
        '20190001', // Order number
        2500, // Weight (2.5kg)
        // Below options are only required when shipping outside the EU
        'customsInvoiceNumber',
        Parcel::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS,
        [
            new ParcelItem('green tea', 1, 123, 15.20, '090210', 'EC'),
            new ParcelItem('cardboard', 3, 50, 0.20, '090210', 'NL'),
        ]
    );

    $parcel = $client->createLabel(
        $parcel,
        8, // Shipping method ID
        null // Default sender address
    );

    $pdf = $client->getLabelPdf($parcel, Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT);

    var_dump($parcel, $pdf);
} catch (SendcloudRequestException $exception) {
    echo $exception->getMessage();
}

// Verify and parse a webhook request
$webhookEvent = $client->parseWebhookRequest($request);
if ($webhookEvent->getType() === WebhookEvent::TYPE_PARCEL_STATUS_CHANGED) {
    $parcel = $webhookEvent->getParcel();
}
```

## Installation
`composer require jouwweb/sendcloud`
