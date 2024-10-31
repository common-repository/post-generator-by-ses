<?php
namespace Zend\Math\BigInteger\Adapter;
use Zend\Math\BigInteger\Exception;
class Bcmath implements AdapterInterface
{
    public function init($operand, $base = null)
    {
        $sign    = (strpos($operand, '-') === 0) ? '-' : '';
        $operand = ltrim($operand, '-+');
        if (null === $base) {
            if (preg_match('#^([1-9][0-9]*)$#', $operand, $m)) {
                $base    = 10;
                $operand = $m[1];
            } elseif (preg_match('#^(0[0-7]+)$#', $operand, $m)) {
                $base    = 8;
                $operand = $m[1];
            } elseif (preg_match('#^(?:0x)?([0-9a-f]+)$#', strtolower($operand), $m)) {
                $base    = 16;
                $operand = $m[1];
            } elseif (preg_match('#^([1-9]?\.?[0-9]+)[eE]\+?([0-9]+)$#', $operand, $m)) {
                $base    = 10;
                $operand = bcmul($m[1], bcpow('10', $m[2]));
            } else {
                return false;
            }
        }
        if ($base != 10) {
            $operand = $this->baseConvert($operand, $base, 10);
        }
        $prod = bcmul($operand, '1');
        if (bccomp($operand, $prod) !== 0) {
            return false;
        }
        return $sign . $operand;
    }
    public function add($leftOperand, $rightOperand)
    {
        return bcadd($leftOperand, $rightOperand, 0);
    }
    public function sub($leftOperand, $rightOperand)
    {
        return bcsub($leftOperand, $rightOperand, 0);
    }
    public function mul($leftOperand, $rightOperand)
    {
        return bcmul($leftOperand, $rightOperand, 0);
    }
    public function div($leftOperand, $rightOperand)
    {
        if ($rightOperand == 0) {
            throw new Exception\DivisionByZeroException(
                "Division by zero; divisor = {$rightOperand}"
            );
        }
        $result = bcdiv($leftOperand, $rightOperand, 0);
        return $result;
    }
    public function pow($operand, $exp)
    {
        return bcpow($operand, $exp, 0);
    }
    public function sqrt($operand)
    {
        return bcsqrt($operand, 0);
    }
    public function abs($operand)
    {
        return ltrim($operand, '-');
    }
    public function mod($leftOperand, $rightOperand)
    {
        return bcmod($leftOperand, $rightOperand);
    }
    public function powmod($leftOperand, $rightOperand, $modulus)
    {
        return bcpowmod($leftOperand, $rightOperand, $modulus, 0);
    }
    public function comp($leftOperand, $rightOperand)
    {
        return bccomp($leftOperand, $rightOperand, 0);
    }
    public function intToBin($operand, $twoc = false)
    {
        $nb = chr(0);
        $isNegative = (strpos($operand, '-') === 0);
        $operand    = ltrim($operand, '+-0');
        if (empty($operand)) {
            return $nb;
        }
        if ($isNegative && $twoc) {
            $operand = bcsub($operand, '1');
        }
        $bytes = '';
        while (bccomp($operand, '0', 0) > 0) {
            $temp    = bcmod($operand, '16777216');
            $bytes   = chr($temp >> 16) . chr($temp >> 8) . chr($temp) . $bytes;
            $operand = bcdiv($operand, '16777216', 0);
        }
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
        if ($isNegative) {
            $bytes = ~$bytes;
        }
        $len = (mb_strlen($bytes, '8bit') + 3) & 0xfffffffc;
        $bytes = str_pad($bytes, $len, chr(0), STR_PAD_LEFT);
        $result = '0';
        for ($i = 0; $i < $len; $i += 4) {
            $result = bcmul($result, '4294967296');
            $result = bcadd($result, 0x1000000 * ord($bytes[$i]) +
                    ((ord($bytes[$i + 1]) << 16) |
                     (ord($bytes[$i + 2]) << 8) |
                      ord($bytes[$i + 3])));
        }
        if ($isNegative) {
            $result = bcsub('-' . $result, '1');
        }
        return $result;
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
        $sign    = (strpos($operand, '-') === 0) ? '-' : '';
        $operand = ltrim($operand, '-+');
        $chars = self::BASE62_ALPHABET;
        if ($fromBase == 10) {
            $decimal = $operand;
        } else {
            $decimal = '0';
            for ($i = 0, $len  = mb_strlen($operand, '8bit'); $i < $len; $i++) {
                $decimal = bcmul($decimal, $fromBase);
                $remainder = ($fromBase <= 36)
                    ? base_convert($operand[$i], $fromBase, 10)
                    : strpos($chars, $operand[$i]);
                $decimal = bcadd($decimal, $remainder, 0);
            }
        }
        if ($toBase == 10) {
            return $decimal;
        }
        $result = '';
        do {
            $remainder = bcmod($decimal, $toBase);
            $decimal   = bcdiv($decimal, $toBase);
            $intermediate = ($toBase <= 36)
                ? base_convert($remainder, 10, $toBase)
                : $chars[$remainder];
            $result = $intermediate . $result;
        } while (bccomp($decimal, '0'));
        return $sign . $result;
    }
}
