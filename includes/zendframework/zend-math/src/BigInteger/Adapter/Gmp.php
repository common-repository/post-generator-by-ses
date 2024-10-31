<?php
namespace Zend\Math\BigInteger\Adapter;
use Zend\Math\BigInteger\Exception;
class Gmp implements AdapterInterface
{
    public function init($operand, $base = null)
    {
        $sign    = (strpos($operand, '-') === 0) ? '-' : '';
        $operand = ltrim($operand, '-+');
        if (null === $base) {
            if (preg_match('#^(?:([1-9])\.)?([0-9]+)[eE]\+?([0-9]+)$#', $operand, $m)) {
                if (! empty($m[1])) {
                    if ($m[3] < mb_strlen($m[2], '8bit')) {
                        return false;
                    }
                } else {
                    $m[1] = '';
                }
                $operand = str_pad(($m[1] . $m[2]), ($m[3] + 1), '0', STR_PAD_RIGHT);
            } else {
                $base = 0;
            }
        }
        set_error_handler(function () {
        }, \E_WARNING);
        $res = gmp_init($sign . $operand, $base);
        restore_error_handler();
        if ($res === false) {
            return false;
        }
        return gmp_strval($res);
    }
    public function add($leftOperand, $rightOperand)
    {
        $result = gmp_add($leftOperand, $rightOperand);
        return gmp_strval($result);
    }
    public function sub($leftOperand, $rightOperand)
    {
        $result = gmp_sub($leftOperand, $rightOperand);
        return gmp_strval($result);
    }
    public function mul($leftOperand, $rightOperand)
    {
        $result = gmp_mul($leftOperand, $rightOperand);
        return gmp_strval($result);
    }
    public function div($leftOperand, $rightOperand)
    {
        if ($rightOperand == 0) {
            throw new Exception\DivisionByZeroException(
                "Division by zero; divisor = {$rightOperand}"
            );
        }
        $result = gmp_div_q($leftOperand, $rightOperand);
        return gmp_strval($result);
    }
    public function pow($operand, $exp)
    {
        $result = gmp_pow($operand, $exp);
        return gmp_strval($result);
    }
    public function sqrt($operand)
    {
        $result = gmp_sqrt($operand);
        return gmp_strval($result);
    }
    public function abs($operand)
    {
        $result = gmp_abs($operand);
        return gmp_strval($result);
    }
    public function mod($leftOperand, $modulus)
    {
        $result = gmp_mod($leftOperand, $modulus);
        return gmp_strval($result);
    }
    public function powmod($leftOperand, $rightOperand, $modulus)
    {
        $result = gmp_powm($leftOperand, $rightOperand, $modulus);
        return gmp_strval($result);
    }
    public function comp($leftOperand, $rightOperand)
    {
        return gmp_cmp($leftOperand, $rightOperand);
    }
    public function intToBin($int, $twoc = false)
    {
        $nb         = chr(0);
        $isNegative = (strpos($int, '-') === 0);
        $int        = ltrim($int, '+-0');
        if (empty($int)) {
            return $nb;
        }
        if ($isNegative && $twoc) {
            $int = gmp_sub($int, '1');
        }
        $hex  = gmp_strval($int, 16);
        if (mb_strlen($hex, '8bit') & 1) {
            $hex = '0' . $hex;
        }
        $bytes = pack('H*', $hex);
        $bytes = ltrim($bytes, $nb);
        if ($twoc) {
            if (ord($bytes[0]) & 0x80) {
                $bytes = $nb . $bytes;
            }
            return $isNegative ? ~$bytes : $bytes;
        }
        return $bytes;
    }
    public function binToInt($bytes, $twoc = false)
    {
        $isNegative = ((ord($bytes[0]) & 0x80) && $twoc);
        $sign = '';
        if ($isNegative) {
            $bytes = ~$bytes;
            $sign  = '-';
        }
        $result = gmp_init($sign . bin2hex($bytes), 16);
        if ($isNegative) {
            $result = gmp_sub($result, '1');
        }
        return gmp_strval($result);
    }
    public function baseConvert($operand, $fromBase, $toBase = 10)
    {
        if ($fromBase == $toBase) {
            return $operand;
        }
        if ($fromBase < 2 || $fromBase > 62) {
            throw new Exception\InvalidArgumentException(
                "Unsupported base: {$fromBase}, should be 2..62"
            );
        }
        if ($toBase < 2 || $toBase > 62) {
            throw new Exception\InvalidArgumentException(
                "Unsupported base: {$toBase}, should be 2..62"
            );
        }
        if ($fromBase <= 36 && $toBase <= 36) {
            return gmp_strval(gmp_init($operand, $fromBase), $toBase);
        }
        $sign    = (strpos($operand, '-') === 0) ? '-' : '';
        $operand = ltrim($operand, '-+');
        $chars = self::BASE62_ALPHABET;
        if ($fromBase !== 10) {
            $decimal = '0';
            for ($i = 0, $len = mb_strlen($operand, '8bit'); $i < $len; $i++) {
                $decimal = gmp_mul($decimal, $fromBase);
                $decimal = gmp_add($decimal, strpos($chars, $operand[$i]));
            }
        } else {
            $decimal = gmp_init($operand);
        }
        if ($toBase == 10) {
            return gmp_strval($decimal);
        }
        $result = '';
        do {
            list($decimal, $remainder) = gmp_div_qr($decimal, $toBase);
            $pos    = gmp_strval($remainder);
            $result = $chars[$pos] . $result;
        } while (gmp_cmp($decimal, '0'));
        return $sign . $result;
    }
}
