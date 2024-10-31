<?php
namespace Zend\Math\BigInteger\Adapter;
interface AdapterInterface
{
    const BASE62_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public function init($operand, $base = null);
    public function add($leftOperand, $rightOperand);
    public function sub($leftOperand, $rightOperand);
    public function mul($leftOperand, $rightOperand);
    public function div($leftOperand, $rightOperand);
    public function pow($operand, $exp);
    public function sqrt($operand);
    public function abs($operand);
    public function mod($leftOperand, $modulus);
    public function powmod($leftOperand, $rightOperand, $modulus);
    public function comp($leftOperand, $rightOperand);
    public function intToBin($int, $twoc = false);
    public function binToInt($bytes, $twoc = false);
    public function baseConvert($operand, $fromBase, $toBase = 10);
}
