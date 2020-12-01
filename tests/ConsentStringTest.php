<?php
use MobileFuse\Tcf2Decoder\ConsentString;
use PHPUnit\Framework\TestCase;

class ConsentStringTest extends TestCase {
    // TODO: add tests for vendorsAllowed & publisherTC
    public function testValidConsentStringWorks(): void {
        $test_string = 'CO7VKWBO7VKWBEsABBENA7CoAP_AAG_AAAZQGTpB7T5FbSFCyO55dLsAMAhXRkCGAiQAAASAAmABQAKQAAQCkkAQlASgBCACAAAgIiJBAQAMCAgACQEBwABAAAAEAAAABAAIIAAAgAEAAAAIAAACAIAQAAAIAAAAEAAAmwgAAIIACBk4AIgqRQEAQkjASTQpACAAAEYABAAgAAAAgAJgAAAAEAAEAhJAAAAAAAAAAAAAICICQQAAAAAIAAAAAcAAQAAAAAAAAAAACAAAAAAAAAAAAAAAAgCAEAAAAAAAAAAAAIEAAAAAAAgAAA';
        $consent = ConsentString::decode($test_string);

        $this->assertEquals(2, $consent['version']);
        $this->assertEquals(2, $consent['core']['version']);
        $this->assertEquals(1602778867300, $consent['core']['created']);
        $this->assertEquals(1602778867300, $consent['core']['lastUpdated']);
        $this->assertEquals(300, $consent['core']['cmpId']);
        $this->assertEquals(1, $consent['core']['cmpVersion']);
        $this->assertEquals(1, $consent['core']['consentScreen']);
        $this->assertEquals('EN', $consent['core']['consentLanguage']);
        $this->assertEquals(59, $consent['core']['vendorListVersion']);
        $this->assertEquals(2, $consent['core']['policyVersion']);
        $this->assertTrue($consent['core']['isServiceSpecific']);
        $this->assertFalse($consent['core']['useNonStandardStacks']);
        $this->assertFalse($consent['core']['purposeOneTreatment']);
        $this->assertEquals('DK', $consent['core']['publisherCountryCode']);

        $this->assertCount(1, $consent['core']['specialFeatureOptins']);
        $this->assertArrayHasKey('1', $consent['core']['specialFeatureOptins']);

        $this->assertCount(10, $consent['core']['purposeConsents']);
        $this->assertArrayHasKey('1', $consent['core']['purposeConsents']);
        $this->assertArrayHasKey('10', $consent['core']['purposeConsents']);

        $this->assertCount(8, $consent['core']['purposeLegitimateInterests']);
        $this->assertArrayHasKey('2', $consent['core']['purposeLegitimateInterests']);
        $this->assertArrayHasKey('10', $consent['core']['purposeLegitimateInterests']);

        $this->assertCount(134, $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('1', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('10', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('18', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('365', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('793', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('807', $consent['core']['vendorConsents']);

        $this->assertCount(58, $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('11', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('15', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('32', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('109', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('762', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('807', $consent['core']['vendorLegitimateInterests']);

        $this->assertCount(0, $consent['core']['publisherRestrictions']);
    }

    public function testAnotherValidConsentStringWorks(): void {
        $test_string = 'COvFyGBOvFyGBAbAAAENAPCAAOAAAAAAAAAAAEEUACCKAAA.IFoEUQQgAIQwgIwQABAEAAAAOIAACAIAAAAQAIAgEAACEAAAAAgAQBAAAAAAAGBAAgAAAAAAAFAAECAAAgAAQARAEQAAAAAJAAIAAgAAAYQEAAAQmAgBC3ZAYzUw';
        $consent = ConsentString::decode($test_string);

        $this->assertEquals(2, $consent['version']);
        $this->assertEquals(2, $consent['core']['version']);
        $this->assertEquals(1582243059300, $consent['core']['created']);
        $this->assertEquals(1582243059300, $consent['core']['lastUpdated']);
        $this->assertEquals(27, $consent['core']['cmpId']);
        $this->assertEquals(0, $consent['core']['cmpVersion']);
        $this->assertEquals(0, $consent['core']['consentScreen']);
        $this->assertEquals('EN', $consent['core']['consentLanguage']);
        $this->assertEquals(15, $consent['core']['vendorListVersion']);
        $this->assertEquals(2, $consent['core']['policyVersion']);
        $this->assertFalse($consent['core']['isServiceSpecific']);
        $this->assertFalse($consent['core']['useNonStandardStacks']);
        $this->assertFalse($consent['core']['purposeOneTreatment']);
        $this->assertEquals('AA', $consent['core']['publisherCountryCode']);

        $this->assertCount(0, $consent['core']['specialFeatureOptins']);

        $this->assertCount(3, $consent['core']['purposeConsents']);
        $this->assertArrayHasKey('1', $consent['core']['purposeConsents']);
        $this->assertArrayHasKey('3', $consent['core']['purposeConsents']);

        $this->assertCount(0, $consent['core']['purposeLegitimateInterests']);

        $this->assertCount(3, $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('2', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('6', $consent['core']['vendorConsents']);
        $this->assertArrayHasKey('8', $consent['core']['vendorConsents']);

        $this->assertCount(3, $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('2', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('6', $consent['core']['vendorLegitimateInterests']);
        $this->assertArrayHasKey('8', $consent['core']['vendorLegitimateInterests']);

        $this->assertCount(0, $consent['core']['publisherRestrictions']);

        $this->assertCount(79, $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('2', $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('6', $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('12', $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('127', $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('467', $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('708', $consent['vendorsDisclosed']['vendorsDisclosed']);
        $this->assertArrayHasKey('720', $consent['vendorsDisclosed']['vendorsDisclosed']);
    }

    public function testNonsenseStringFails(): void {
        $test_string = 'this-is-nonsense';
        $this->assertNull(ConsentString::decode($test_string));
    }

    public function testMalformedStringFails(): void {
        $test_string = 'CO7VKWBO7VKWBEsABBENA7CoAP_AAG_';
        $this->assertNull(ConsentString::decode($test_string));
    }
}
