# PHP Library for Decoding TCF2 Consent Strings

We created this utility class to be able to have a fast and lightweight library for decoding TCF2 consent strings.

## Installation

Require the package via composer

```
composer require mobilefuse/php-tcf2-decoder
```

## Usage

```
use MobileFuse\Tcf2Decoder\ConsentString;

$consent = ConsentString::decode("COvFyGBOvFyGBAbAAAENAPCAAOAAAAAAAAAAAEEUACCKAAA.IFoEUQQgAIQwgIwQABAEAAAAOIAACAIAAAAQAIAgEAACEAAAAAgAQBAAAAAAAGBAAgAAAAAAAFAAECAAAgAAQARAEQAAAAAJAAIAAgAAAYQEAAAQmAgBC3ZAYzUw");

var_dump($consent);
```

Output:
```
array(3) {
  ["version"]=>
  int(2)
  ["core"]=>
  array(19) {
    ["version"]=>
    int(2)
    ["created"]=>
    int(1582243059300)
    ["lastUpdated"]=>
    int(1582243059300)
    ["cmpId"]=>
    int(27)
    ["cmpVersion"]=>
    int(0)
    ["consentScreen"]=>
    int(0)
    ["consentLanguage"]=>
    string(2) "EN"
    ["vendorListVersion"]=>
    int(15)
    ["policyVersion"]=>
    int(2)
    ["isServiceSpecific"]=>
    bool(false)
    ["useNonStandardStacks"]=>
    bool(false)
    ["specialFeatureOptins"]=>
    array(0) {
    }
    ["purposeConsents"]=>
    array(3) {
      [1]=>
      int(1)
      [2]=>
      int(2)
      [3]=>
      int(3)
    }
    ["purposeLegitimateInterests"]=>
    array(0) {
    }
    ["purposeOneTreatment"]=>
    bool(false)
    ["publisherCountryCode"]=>
    string(2) "AA"
    ["vendorConsents"]=>
    array(3) {
      [2]=>
      int(2)
      [6]=>
      int(6)
      [8]=>
      int(8)
    }
    ["vendorLegitimateInterests"]=>
    array(3) {
      [2]=>
      int(2)
      [6]=>
      int(6)
      [8]=>
      int(8)
    }
    ["publisherRestrictions"]=>
    array(0) {
    }
  }
  ["vendorsDisclosed"]=>
  array(1) {
    ["vendorsDisclosed"]=>
    array(79) {
      [2]=>
      int(2)
      [6]=>
      int(6)
      [8]=>
      int(8)
      // ...
    }
  }
}
```