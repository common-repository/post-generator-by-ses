<?php
if (!defined('PHP_VERSION_ID')) {
        $RandomCompatversion = array_map('intval', explode('.', PHP_VERSION));
    define(
        'PHP_VERSION_ID',
        $RandomCompatversion[0] * 10000
        + $RandomCompatversion[1] * 100
        + $RandomCompatversion[2]
    );
    $RandomCompatversion = null;
}
if (PHP_VERSION_ID >= 70000) {
    return;
}
if (!defined('RANDOM_COMPAT_READ_BUFFER')) {
    define('RANDOM_COMPAT_READ_BUFFER', 8);
}
$RandomCompatDIR = dirname(__FILE__);
require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'byte_safe_strings.php';
require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'cast_to_int.php';
require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'error_polyfill.php';
if (!is_callable('random_bytes')) {
    if (extension_loaded('libsodium')) {
                if (PHP_VERSION_ID >= 50300 && is_callable('\\Sodium\\randombytes_buf')) {
            require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_libsodium.php';
        } elseif (method_exists('Sodium', 'randombytes_buf')) {
            require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_libsodium_legacy.php';
        }
    }
    if (DIRECTORY_SEPARATOR === '/') {
                        $RandomCompatUrandom = true;
        $RandomCompat_basedir = ini_get('open_basedir');
        if (!empty($RandomCompat_basedir)) {
            $RandomCompat_open_basedir = explode(
                PATH_SEPARATOR,
                strtolower($RandomCompat_basedir)
            );
            $RandomCompatUrandom = (array() !== array_intersect(
                array('/dev', '/dev/', '/dev/urandom'),
                $RandomCompat_open_basedir
            ));
            $RandomCompat_open_basedir = null;
        }
        if (
            !is_callable('random_bytes')
            &&
            $RandomCompatUrandom
            &&
            @is_readable('/dev/urandom')
        ) {
                        require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_dev_urandom.php';
        }
                $RandomCompat_basedir = null;
    } else {
        $RandomCompatUrandom = false;
    }
    if (
        !is_callable('random_bytes')
        &&
                (DIRECTORY_SEPARATOR === '/' || PHP_VERSION_ID >= 50307)
        &&
                        (
            DIRECTORY_SEPARATOR !== '/' ||
            (PHP_VERSION_ID <= 50609 || PHP_VERSION_ID >= 50613)
        )
        &&
        extension_loaded('mcrypt')
    ) {
                require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_mcrypt.php';
    }
    $RandomCompatUrandom = null;
    if (
        !is_callable('random_bytes')
        &&
        extension_loaded('com_dotnet')
        &&
        class_exists('COM')
    ) {
        $RandomCompat_disabled_classes = preg_split(
            '#\s*,\s*#',
            strtolower(ini_get('disable_classes'))
        );
        if (!in_array('com', $RandomCompat_disabled_classes)) {
            try {
                $RandomCompatCOMtest = new COM('CAPICOM.Utilities.1');
                if (method_exists($RandomCompatCOMtest, 'GetRandom')) {
                                        require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_com_dotnet.php';
                }
            } catch (com_exception $e) {
                            }
        }
        $RandomCompat_disabled_classes = null;
        $RandomCompatCOMtest = null;
    }
    if (!is_callable('random_bytes')) {
        function random_bytes($length)
        {
            unset($length);             throw new Exception(
                'There is no suitable CSPRNG installed on your system'
            );
            return '';
        }
    }
}
if (!is_callable('random_int')) {
    require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_int.php';
}
$RandomCompatDIR = null;
