<?php
namespace MobileFuse\Tcf2Decoder;

use Exception;

// Logic heavily ported from:
// https://github.com/InteractiveAdvertisingBureau/iabtcf-es/blob/master/modules/core/src/TCString.ts
// Handy tools:
// - https://www.consentstringdecoder.com/
// - https://iabtcf.com/#/decode
class ConsentString {
    private const BIT_LENGTH_ANY_BOOLEAN      = 1;
    private const BIT_LENGTH_BASIS            = 6;
    private const BIT_LENGTH_ENCODING_TYPE    = 1;
    private const BIT_LENGTH_IS_RANGE         = 1;
    private const BIT_LENGTH_MAX_ID           = 16;
    private const BIT_LENGTH_NUM_ENTRIES      = 12;
    private const BIT_LENGTH_NUM_RESTRICTIONS = 12;
    private const BIT_LENGTH_PURPOSE_ID       = 6;
    private const BIT_LENGTH_RESTRICTION_TYPE = 2;
    private const BIT_LENGTH_SEGMENT_TYPE     = 3;
    private const BIT_LENGTH_VENDOR_ID        = 16;
    private const BIT_LENGTH_VERSION          = 6;

    private const SEGMENT_CORE              = 'core';
    private const SEGMENT_PUBLISHER_TC      = 'publisherTC';
    private const SEGMENT_VENDORS_ALLOWED   = 'vendorsAllowed';
    private const SEGMENT_VENDORS_DISCLOSED = 'vendorsDisclosed';

    private const VECTOR_ENCODING_TYPE_RANGE = 1;

    private const SEGMENT_ID_TO_KEY = [
        0 => self::SEGMENT_CORE,
        1 => self::SEGMENT_VENDORS_DISCLOSED,
        2 => self::SEGMENT_VENDORS_ALLOWED,
        3 => self::SEGMENT_PUBLISHER_TC,
    ];

    // version => segment type => field => bit length/encoder
    private const FIELD_SEQUENCE = [
        2 => [
            self::SEGMENT_CORE => [
                'version'                    => ['bits' => 6, 'encoder' => 'parseInt'],
                'created'                    => ['bits' => 36, 'encoder' => 'parseDate'],
                'lastUpdated'                => ['bits' => 36, 'encoder' => 'parseDate'],
                'cmpId'                      => ['bits' => 12, 'encoder' => 'parseInt'],
                'cmpVersion'                 => ['bits' => 12, 'encoder' => 'parseInt'],
                'consentScreen'              => ['bits' => 6, 'encoder' => 'parseInt'],
                'consentLanguage'            => ['bits' => 12, 'encoder' => 'parseLang'],
                'vendorListVersion'          => ['bits' => 12, 'encoder' => 'parseInt'],
                'policyVersion'              => ['bits' => 6, 'encoder' => 'parseInt'],
                'isServiceSpecific'          => ['bits' => 1, 'encoder' => 'parseBool'],
                'useNonStandardStacks'       => ['bits' => 1, 'encoder' => 'parseBool'],
                'specialFeatureOptins'       => ['bits' => 12, 'encoder' => 'parseFixedVector'],
                'purposeConsents'            => ['bits' => 24, 'encoder' => 'parseFixedVector'],
                'purposeLegitimateInterests' => ['bits' => 24, 'encoder' => 'parseFixedVector'],
                'purposeOneTreatment'        => ['bits' => 1, 'encoder' => 'parseBool'],
                'publisherCountryCode'       => ['bits' => 12, 'encoder' => 'parseLang'],
                'vendorConsents'             => ['bits' => null, 'encoder' => 'parseVendorVector'],
                'vendorLegitimateInterests'  => ['bits' => null, 'encoder' => 'parseVendorVector'],
                'publisherRestrictions'      => ['bits' => null, 'encoder' => 'parsePurposeRestrictionVector'],
            ],
            self::SEGMENT_PUBLISHER_TC => [
                'publisherConsents'                  => ['bits' => 24, 'encoder' => 'parseFixedVector'],
                'publisherLegitimateInterests'       => ['bits' => 24, 'encoder' => 'parseFixedVector'],
                'numCustomPurposes'                  => ['bits' => 6, 'encoder' => 'parseInt'],
                'publisherCustomConsents'            => ['bits' => null, 'encoder' => 'parseFixedVector'],
                'publisherCustomLegitimateInterests' => ['bits' => null, 'encoder' => 'parseFixedVector'],
            ],
            self::SEGMENT_VENDORS_ALLOWED => [
                'vendorsAllowed' => ['bits' => null, 'encoder' => 'parseVendorVector'],
            ],
            self::SEGMENT_VENDORS_DISCLOSED => [
                'vendorsDisclosed' => ['bits' => null, 'encoder' => 'parseVendorVector'],
            ],
        ],
    ];

    private const CHARACTER_TO_BINARY_VALUE = [
        'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5,
        'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11,
        'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17,
        'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
        'Y' => 24, 'Z' => 25, 'a' => 26, 'b' => 27, 'c' => 28, 'd' => 29,
        'e' => 30, 'f' => 31, 'g' => 32, 'h' => 33, 'i' => 34, 'j' => 35,
        'k' => 36, 'l' => 37, 'm' => 38, 'n' => 39, 'o' => 40, 'p' => 41,
        'q' => 42, 'r' => 43, 's' => 44, 't' => 45, 'u' => 46, 'v' => 47,
        'w' => 48, 'x' => 49, 'y' => 50, 'z' => 51, '0' => 52, '1' => 53,
        '2' => 54, '3' => 55, '4' => 56, '5' => 57, '6' => 58, '7' => 59,
        '8' => 60, '9' => 61, '-' => 62, '_' => 63,
    ];

    private static $calculated_bit_length = null;
    private static $exception             = null;

    // will throw errors for invalid incoming data
    public static function decode(string $consent): ?array {
        try {
            $segments = explode('.', $consent);
            $model    = [];

            foreach ($segments as $segment) {
                // First character will contain 6 bits, we only need the first three, which tells us
                // what type of segment we're looking at, ie. what type of information it holds

                $first_char        = self::base64UrlDecode($segment[0]);
                $segment_type_bits = bindec(substr($first_char, 0, self::BIT_LENGTH_SEGMENT_TYPE));
                $segment_type      = self::SEGMENT_ID_TO_KEY[$segment_type_bits];

                self::parseConsentSegment($segment, $model, $segment_type);
            }

            return $model;
        } catch (Exception $exception) {
            self::$exception = $exception;
            return null;
        }
    }

    public static function getException(): ?Exception {
        return self::$exception;
    }

    private static function base64UrlDecode(string $string): string {
        $result = '';

        foreach (str_split($string) as $character) {
            $value = self::CHARACTER_TO_BINARY_VALUE[$character];
            $string_bits = base_convert($value, 10, 2);

            $result .= str_pad($string_bits, self::BIT_LENGTH_BASIS, 0, STR_PAD_LEFT);
        }

        return $result;
    }

    private static function parseConsentSegment(string $segment, array &$model, string $segment_type): void {
        $bit_field        = self::base64UrlDecode($segment);
        $bit_string_index = 0;

        if ($segment_type === self::SEGMENT_CORE) {
            $model['version'] = bindec(substr($bit_field, 0, self::BIT_LENGTH_VERSION));

            if ($model['version'] !== 2) {
                throw new Exception("Unsupported version: {$model['version']}");
            }
        } else {
            $bit_string_index += self::BIT_LENGTH_SEGMENT_TYPE;
        }

        foreach (self::FIELD_SEQUENCE[$model['version']][$segment_type] as $field => $config) {
            self::$calculated_bit_length = null;

            $bit_length = $config['bits'];
            $encoder    = $config['encoder'];

            if ($bit_length === null && strpos($field, 'publisherCustom') !== false) {
                $bit_length = $model[self::SEGMENT_PUBLISHER_TC]['numCustomPurposes'];
            }

            if ($bit_length !== 0) {
                if ($bit_length === null) {
                    $bits = substr($bit_field, $bit_string_index);
                } else {
                    $bits = substr($bit_field, $bit_string_index, $bit_length);
                }

                $model[$segment_type][$field] = self::$encoder($bits, $bit_length);

                if (is_int($bit_length)) {
                    $bit_string_index += $bit_length;
                } elseif (self::$calculated_bit_length !== null) {
                    $bit_string_index += self::$calculated_bit_length;
                } else {
                    throw new Exception('Decoding error');
                }
            }
        }
    }

    private static function parseInt(string $value, ?int $bit_length): int {
        return bindec($value);
    }

    private static function parseDate(string $value, ?int $bit_length): int {
        return bindec($value) * 100;
    }

    private static function parseLang(string $value, ?int $bit_length): string {
        $ascii_start   = 65;
        $mid           = $bit_length / 2;
        $first_letter  = bindec(substr($value, 0, $mid)) + $ascii_start;
        $second_letter = bindec(substr($value, $mid)) + $ascii_start;

        return chr($first_letter) . chr($second_letter);
    }

    private static function parseBool(string $value, ?int $bit_length): bool {
        return $value === '1';
    }

    private static function parseFixedVector(string $value, ?int $bit_length): array {
        $result = [];

        for ($i = 1; $i <= $bit_length; $i++) {
            if ($value[$i - 1] === '1') {
                $result[$i] = $i;
            }
        }

        return $result;
    }

    private static function parseVendorVector(string $value, ?int $bit_length): array {
        $index  = 0;
        $max_id = bindec(substr($value, $index, self::BIT_LENGTH_MAX_ID));
        $index += self::BIT_LENGTH_MAX_ID;

        $encoding_type = bindec(substr($value, $index, self::BIT_LENGTH_ENCODING_TYPE));
        $index += self::BIT_LENGTH_ENCODING_TYPE;

        if ($encoding_type !== self::VECTOR_ENCODING_TYPE_RANGE) {
            $bit_field = substr($value, $index, $max_id);
            $index += $max_id;
            $result = self::parseFixedVector($bit_field, $max_id);
        } else {
            $result = [];

            $num_entries = bindec(substr($value, $index, self::BIT_LENGTH_NUM_ENTRIES));
            $index += self::BIT_LENGTH_NUM_ENTRIES;

            for ($i = 0; $i < $num_entries; $i++) {
                $is_id_range = substr($value, $index, self::BIT_LENGTH_IS_RANGE) === '1';
                $index += self::BIT_LENGTH_IS_RANGE;

                $first_id = bindec(substr($value, $index, self::BIT_LENGTH_VENDOR_ID));
                $index += self::BIT_LENGTH_VENDOR_ID;

                if (!$is_id_range) {
                    $result[$first_id] = $first_id;
                } else {
                    $second_id = bindec(substr($value, $index, self::BIT_LENGTH_VENDOR_ID));
                    $index += self::BIT_LENGTH_VENDOR_ID;

                    for ($j = $first_id; $j <= $second_id; $j++) {
                        $result[$j] = $j;
                    }
                }
            }
        }

        self::$calculated_bit_length = $index;

        return $result;
    }

    private static function parsePurposeRestrictionVector(string $value, ?int $bit_length): array {
        $index = 0;
        $result = [];

        $num_restrictions = bindec(substr($value, $index, self::BIT_LENGTH_NUM_RESTRICTIONS));
        $index += self::BIT_LENGTH_NUM_RESTRICTIONS;

        for ($i = 0; $i < $num_restrictions; $i++) {
            $purpose_id = bindec(substr($value, $index, self::BIT_LENGTH_PURPOSE_ID));
            $index += self::BIT_LENGTH_PURPOSE_ID;

            $restriction_type = bindec(substr($value, $index, self::BIT_LENGTH_RESTRICTION_TYPE));
            $index += self::BIT_LENGTH_RESTRICTION_TYPE;

            $purpose_restriction = compact('purpose_id', 'restriction_type');

            $num_entries = bindec(substr($value, $index, self::BIT_LENGTH_NUM_ENTRIES));
            $index += self::BIT_LENGTH_NUM_ENTRIES;

            for ($j = 0; $j < $num_entries; $j++) {
                $is_range = substr($value, $index, self::BIT_LENGTH_ANY_BOOLEAN) === '1';
                $index += self::BIT_LENGTH_ANY_BOOLEAN;

                $start_or_only_vendor_id = bindec(substr($value, $index, self::BIT_LENGTH_VENDOR_ID));
                $index += self::BIT_LENGTH_VENDOR_ID;

                if (!$is_range) {
                    $result[$start_or_only_vendor_id] = $purpose_restriction;
                } else {
                    $end_vendor_id = bindec(substr($value, $index, self::BIT_LENGTH_VENDOR_ID));
                    $index += self::BIT_LENGTH_VENDOR_ID;

                    if ($end_vendor_id < $start_or_only_vendor_id) {
                        throw new Exception("endVendorId $end_vendor_id is less than startVendorId $start_or_only_vendor_id");
                    }

                    for ($k = $start_or_only_vendor_id; $k <= $end_vendor_id; $k++) {
                        $result[$k] = $purpose_restriction;
                    }
                }
            }
        }

        self::$calculated_bit_length = $index;

        return $result;
    }
}
