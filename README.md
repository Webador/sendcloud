# SendCloud

This is a PHP library that provides a simple way to communicate with the SendCloud API.
It was created because there were no simple alternatives that follow good object-oriented code practices. 

> NOTE: This library does not (yet) fully implement the SendCloud API.
Only basic calls for creating and managing parcels are implemented to serve my specific use-case.
If you require functionality that is missing please request it through a GitHub issue or pull request.

## Example

```php
use Villermen\SendCloud;

$client = new SendCloud\Client('your_public_key', 'your_secret_key');

// Print prices for all enabled shipping methods that ship to the Netherlands
foreach ($client->getShippingMethods() as $shippingMethod) {
        $price = $shippingMethod->getPriceForCountry('NL');
        if ($price) {
            echo $shippingMethod->getName() . ': €' . ($price / 100) . PHP_EOL;
        }
    }
}

// Create a parcel
try {
    // Most of these arguments are optional and will fall back to defaults configured in SendCloud
    $parcel = $client->createParcel(
        new SendCloud\Address('Customer name', 'Customer company name', 'Customer street', '4A', 'City', '9999ZZ', 'NL', 'test@test.test', '+31612345678'),
        '80018', // Order number
        8, // Shipping method ID
        1000, // 1KG
        true, // Request a label
        new SendCloud\Address('From name', 'From company', 'From street', '234', 'City', '9999ZZ', 'NL', '', '')
    );

    var_dump($parcel->getId());
} catch (\Villermen\SendCloud\Exception\SendCloudRequestException $exception) {
    echo $exception->getMessage();
}
```

## Installation
`composer require villermen/sendcloud`
