<?php
namespace Ec\Utils;
class StringUtils
{
    public static function currencySymbolToUsdMultiplier($symbol)
    {
        $ret = [
            '£' => 0.77,            '€' => 0.86,
            '$' => 1.0,
            '₹' => 70,         ];
        return $ret[$symbol] ?: null;
    }
    public static function getCurrencySymbol($currencyCode)
    {
        $map= [
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€',
            'AED' => 'د.إ.‏',
            'AFN' => '؋',
            'ALL' => 'Lekë',
            'AMD' => '֏',
            'ANG' => 'NAf.',
            'AOA' => 'Kz',
            'ARS' => '$',
            'A$' => '$',
            'AWG' => 'Afl.',
            'AZN' => '₼',
            'BAM' => 'КМ',
            'BBD' => '$',
            'BDT' => '৳',
            'BGN' => 'лв.',
            'BHD' => 'د.ب.‏',
            'BIF' => 'FBu',
            'BMD' => '$',
            'BND' => '$',
            'BOB' => 'Bs',
            'BOV' => 'BOV',
            'R$' => 'R$',
            'BSD' => '$',
            'BTN' => 'Nu.',
            'BWP' => 'P',
            'BYN' => 'Br',
            'BZD' => '$',
            'CA$' => '$',
            'CDF' => 'FC',
            'CHE' => 'CHE',
            'CHF' => 'CHF',
            'CHW' => 'CHW',
            'CLF' => 'CLF',
            'CLP' => '$',
            'CNH' => 'CNH',
            'CN¥' => '¥',
            'COP' => '$',
            'COU' => 'COU',
            'CRC' => '₡',
            'CUC' => 'CUC',
            'CUP' => '$',
            'CVE' => '​',
            'CZK' => 'Kč',
            'DJF' => 'Fdj',
            'DKK' => 'kr.',
            'DOP' => 'RD$',
            'DZD' => 'د.ج.‏',
            'EGP' => 'ج.م.‏',
            'ERN' => 'Nfk',
            'ETB' => 'ብር',
            '€' => '€',
            'FJD' => '$',
            'FKP' => '£',
            '£' => '£',
            'GEL' => '₾',
            'GHS' => 'GH₵',
            'GIP' => '£',
            'GMD' => 'D',
            'GNF' => 'FG',
            'GTQ' => 'Q',
            'GYD' => '$',
            'HK$' => 'HK$',
            'HNL' => 'L',
            'HRK' => 'HRK',
            'HTG' => 'G',
            'HUF' => 'Ft',
            'IDR' => 'Rp',
            '₪' => '₪',
            '₹' => '₹',
            'IQD' => 'د.ع.‏',
            'IRR' => 'IRR',
            'ISK' => 'ISK',
            'JMD' => '$',
            'JOD' => 'د.أ.‏',
            '¥' => '￥',
            'KES' => 'Ksh',
            'KGS' => 'сом',
            'KHR' => '៛',
            'KMF' => 'CF',
            'KPW' => 'KPW',
            '₩' => '₩',
            'KWD' => 'د.ك.‏',
            'KYD' => '$',
            'KZT' => '₸',
            'LAK' => '₭',
            'LBP' => 'ل.ل.‏',
            'LKR' => 'රු.',
            'LRD' => '$',
            'LSL' => 'LSL',
            'LYD' => 'د.ل.‏',
            'MAD' => 'د.م.‏',
            'MDL' => 'L',
            'MGA' => 'Ar',
            'MKD' => 'ден',
            'MMK' => 'K',
            'MNT' => '₮',
            'MOP' => 'MOP$',
            'MRO' => 'أ.م.‏',
            'MUR' => 'Rs',
            'MWK' => 'MK',
            'MX$' => '$',
            'MXV' => 'MXV',
            'MYR' => 'RM',
            'MZN' => 'MTn',
            'NAD' => '$',
            'NGN' => '₦',
            'NIO' => 'C$',
            'NOK' => 'kr',
            'NPR' => 'नेरू',
            'NZ$' => '$',
            'OMR' => 'ر.ع.‏',
            'PAB' => 'B/.',
            'PEN' => 'S/',
            'PGK' => 'K',
            'PHP' => '₱',
            'PKR' => 'Rs',
            'PLN' => 'zł',
            'PYG' => 'Gs.',
            'QAR' => 'ر.ق.‏',
            'RON' => 'RON',
            'RSD' => 'RSD',
            'RUB' => '₽',
            'RWF' => 'RF',
            'SAR' => 'ر.س.‏',
            'SBD' => '$',
            'SCR' => 'SR',
            'SDG' => 'ج.س.',
            'SEK' => 'kr',
            'SGD' => '$',
            'SHP' => '£',
            'SLL' => 'Le',
            'SOS' => 'S',
            'SRD' => '$',
            'SSP' => '£',
            'STN' => 'STN',
            'SYP' => 'ل.س.‏',
            'SZL' => 'E',
            'THB' => 'THB',
            'TJS' => 'сом.',
            'TND' => 'د.ت.‏',
            'TOP' => 'T$',
            'TRY' => '₺',
            'TTD' => '$',
            'NT$' => '$',
            'TZS' => 'TSh',
            'UAH' => '₴',
            'UGX' => 'USh',
            '$' => '$',
            'USN' => 'USN',
            'UYI' => 'UYI',
            'UYU' => '$',
            'UZS' => 'сўм',
            'VEF' => 'Bs.',
            '₫' => '₫',
            'VUV' => 'VT',
            'WST' => 'WS$',
            'FCFA' => 'FCFA',
            'EC$' => '$',
            'CFA' => 'CFA',
            'CFPF' => 'FCFP',
            'YER' => 'ر.ي.‏',
            'ZAR' => 'R',
            'ZMW' => 'K',
        ];
        return isset($map[$currencyCode]) ? $map[$currencyCode] : null;
    }
    public static function shortenToMaxWords($string, $maxWords, $options = [])
    {
        $pieces = explode(' ', $string);
        if (count($pieces) <= $maxWords) {
            return $string;
        }
        return join(' ', array_slice($pieces, 0, $maxWords));
    }
    public static function urlRemoveQueryString($url)
    {
        $pieces = parse_url($url);
        return $pieces['scheme'] . '://' . $pieces['host'] . $pieces['path'];
    }
    public static function isRobot($userAgent)
    {
        if (!$userAgent =='Symfony BrowserKit') {
            return false;
        }
        return preg_match('/Googlebot|bingbot|bot|index|spider|crawl|wget|slurp|Mediapartners-Google/i', $userAgent) ? true : false;
    }
    public static function convertToFloat($number)
    {
        if (!$number) {
            return null;
        }
        $dotPosition = strpos($number, '.');
        $commaPosition = strpos($number, ',');
        if (preg_match('/,\d{3}/', $number)) {
            $number = str_replace(',', '', $number);
        }
        if ($dotPosition === false && preg_match('/,\d{2}$/', $number)) {
            $number = str_replace(',', '.', $number);
        }
        if ($commaPosition === false && preg_match('/.\d{3}$/', $number)) {
            $number = str_replace('.', '', $number);
        }
        if ($commaPosition !== false && $dotPosition !== false && $dotPosition < $commaPosition && preg_match('/.\d{2}$/', $number)) {
            $number = str_replace('.', '', $number);
            $number = str_replace(',', '.', $number);
        }
        return (float) $number;
    }
    public static function seemsSpam($content)
    {
        if (preg_match('/\[url=/', $content)) {
            return true;
        }
        if (preg_match('/<a/', $content)) {
            return true;
        }
        if (preg_match('#https?://#', $content)) {
            return true;
        }
        return false;
    }
    public static function debugUsingIteration($element)
    {
        $ret = [];
        foreach ($element as $k=>$v) {
            $ret[$k] = $v;
        }
        return $ret;
    }
    public static function validBase64($string)
    {
        $decoded = base64_decode($string, true);
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) {
            return false;
        }
        if (!base64_decode($string, true)) {
            return false;
        }
        if (base64_encode($decoded) != $string) {
            return false;
        }
        return true;
    }
    public static function compressObject($item, $mode = 'base64gz')
    {
        switch ($mode) {
           case 'base64gz':
               return base64_encode(gzcompress(serialize($item), 9));
           default:
               throw new \RuntimeException(__METHOD__ . ' 2nd method not supported');
       }
    }
    public static function decompressObject($item, $mode = 'base64gz')
    {
        switch ($mode) {
            case 'base64gz':
                return @unserialize(gzuncompress(base64_decode($item)));
            default:
                throw new \RuntimeException(__METHOD__ . ' 2nd method not supported');
        }
    }
    public static function cleanQueryString($q, $maxChars = null)
    {
        $q = strip_tags($q);
        $q = preg_replace('/[\/\\"\'\!:,\.\(\)\[\]\{\}]/', ' ', $q);
        $q = preg_replace('!\s+!', ' ', $q);
        $q = trim($q, '-,.:" ');
        if ($maxChars) {
            $q = substr($q, 0, $maxChars);
        }
        return $q;
    }
}
