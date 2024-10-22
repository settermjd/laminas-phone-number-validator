# Phone Number Validator that uses Twilio's Lookup (V2) API

![GitHub Actions Workflow Status](https://github.com/settermjd/laminas-phone-number-validator/actions/workflows/php.yml/badge.svg)

This is a custom laminas-validator class that checks if a phone number is valid by using Twilio's Lookup API.

## Overview

The package provides a custom laminas-validator class that checks if a phone number is valid by using Twilio's Lookup API, providing a simple way of validating phone numbers are valid, based on communications provider data, accessed through Twilio.

## Requirements

To use the application, you'll need the following:

- A Twilio account (free or paid).
  [Create an account][twilio-referral-url] if you don't already have one.
- PHP 8.3
- [Composer][composer-url] installed globally
- [Git][git-url]

## Getting Started

### Add the Package as a Project Dependency

To use the package in your project, first, either add it as a required package in _composer.json_'s `require` attribute.

```json
"require": {
    "settermjd/laminas-twilio-phone-number-validator": "^1.0"
}
```

Or, use `composer require` to add it:

```bash
composer require settermjd/laminas-twilio-phone-number-validator
```

### How to Use the Validator

Then, you can either use it directly, as in the following example, to validate a phone number.

```php
use Settermjd\Validator\VerifyPhoneNumber;
use Twilio\Rest\Client;

$validator = new VerifyPhoneNumber(new Client(
    `<YOUR_TWILIO_ACCOUNT_SID>`,
    `<YOUR_TWILIO_AUTH_TOKEN>`,
));

if ($validator->isValid($email)) {
    // The phone number is valid, so do what you want with the information.
} else {
    // The phone number is not invalid, so print the reasons why.
    foreach ($validator->getMessages() as $messageId => $message) {
        printf("Validation failure '%s': %s\n", $messageId, $message);
    }
}
```

Or, you can use it in conjunction with [laminas-inputfilter][laminas-inputfilter-url], as in the following example.

```php
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\Input;
use Laminas\Validator;
use Settermjd\Validator\VerifyPhoneNumber;
use Twilio\Rest\Client;

$phoneNumber = new Input('phone_number');
$phoneNumber->getValidatorChain()
          ->attach(
          new VerifyPhoneNumber(
              new Client(
                  `<TWILIO_ACCOUNT_SID>`,
                  `<TWILIO_AUTH_TOKEN>`,
              )
          )
    );

$inputFilter = new InputFilter();
$inputFilter->add($phoneNumber);

$inputFilter->setData($_POST);
if ($inputFilter->isValid()) {
    echo "The form is valid\n";
} else {
    echo "The form is not valid\n";
    foreach ($inputFilter->getInvalidInput() as $error) {
        print_r($error->getMessages());
    }
}
```

In both of the above examples, the `VerifyPhoneNumber` validator is initialised with a `Twilio\Rest\Client` object which is initialised with a Twilio Account SID and Auth Token.
To retrieve these, open [the Twilio Console][twilio-console-url] in your browser of choice, then copy the Account SID and Auth Token, as you can see in the screenshot below.

![The Account Info panel of the Twilio Console, showing a user's Account SID, Auth Token, and phone number, where the Account SID and phone number have been partially or completely redacted.](./docs/images/twilio-console-account-info-panel.png)

> [!CAUTION]
> Use a package such as [PHP Dotenv][phpdotenv-url] to keep credentials, such as the Twilio Account SID and Auth Token out of code, and avoid them accidentally being tracked by Git (or your version control tool of choice), or your deployment tool's secrets manager is strongly encouraged.

#### Add Caching Support

The validator is [PSR-16][psr16-url]-compliant.
So, if you want to further enhance performance, when initialising a `VerifyPhoneNumber` object, provide an object that implements [CacheInterface][cacheinterface-url] as the third argument; the example below uses [laminas-cache][laminascache-psr16-url].

```php
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerInterface;
use Settermjd\Validator\VerifyPhoneNumber;
use Twilio\Rest\Client;

/** @var ContainerInterface $container */
$container = null; // can be any configured PSR-11 container

$storageFactory = $container->get(StorageAdapterFactoryInterface::class);
$storage = $storageFactory->create('apc');

$validator = new VerifyPhoneNumber(
    new Client(`<YOUR_TWILIO_ACCOUNT_SID>`, `<YOUR_TWILIO_AUTH_TOKEN>`), 
    new SimpleCacheDecorator($storage)
);
```

If you're not sure which PSR-16 implementation to use, [check out the full list of providers on Packagist][simplecache-implementation-url].

## Contributing

If you want to contribute to the project, whether you have found issues with it or just want to improve it, here's how:

- [Issues][github-issues-url]: ask questions and submit your feature requests, bug reports, etc
- [Pull requests][github-pr-url]: send your improvements

## Did You Find the Project Useful?

If the project was useful, and you want to say thank you and/or support its active development, here's how:

- Add a GitHub Star to the project
- Write an interesting article about the project wherever you blog

[cacheinterface-url]: https://www.php-fig.org/psr/psr-16/#21-cacheinterface
[composer-url]: https://getcomposer.org
[git-url]: https://git-scm.com/downloads
[twilio-console-url]: https://console.twilio.com/
[twilio-referral-url]: http://www.twilio.com/referral/QlBtVJ
[github-issues-url]: https://github.com/settermjd/laminas-phone-number-validator/issues
[github-pr-url]: https://github.com/settermjd/laminas-phone-number-validator/pulls
[laminascache-psr16-url]: https://docs.laminas.dev/laminas-cache/v4/psr16/
[laminas-inputfilter-url]: https://docs.laminas.dev/laminas-inputfilter/
[phpdotenv-url]: https://github.com/vlucas/phpdotenv
[psr16-url]: https://www.php-fig.org/psr/psr-16/
[simplecache-implementation-url]: https://packagist.org/providers/psr/simple-cache-implementation
