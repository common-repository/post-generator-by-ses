<?php
class Twig_Lexer implements Twig_LexerInterface
{
    protected $tokens;
    protected $code;
    protected $cursor;
    protected $lineno;
    protected $end;
    protected $state;
    protected $states;
    protected $brackets;
    protected $env;
    protected $filename;
    protected $options;
    protected $regexes;
    protected $position;
    protected $positions;
    protected $currentVarBlockLine;
    private $source;
    const STATE_DATA = 0;
    const STATE_BLOCK = 1;
    const STATE_VAR = 2;
    const STATE_STRING = 3;
    const STATE_INTERPOLATION = 4;
    const REGEX_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
    const REGEX_NUMBER = '/[0-9]+(?:\.[0-9]+)?/A';
    const REGEX_STRING = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    const REGEX_DQ_STRING_DELIM = '/"/A';
    const REGEX_DQ_STRING_PART = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
    const PUNCTUATION = '()[]{}?:.,|';
    public function __construct(Twig_Environment $env, array $options = [])
    {
        $this->env = $env;
        $this->options = array_merge([
            'tag_comment' => ['{#', '#}'],
            'tag_block' => ['{%', '%}'],
            'tag_variable' => ['{{', '}}'],
            'whitespace_trim' => '-',
            'interpolation' => ['#{', '}'],
        ], $options);
        $this->regexes = [
            'lex_var' => '/\s*' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_variable'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_variable'][1], '/') . '/A',
            'lex_block' => '/\s*(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_block'][1], '/') . ')\n?/A',
            'lex_raw_data' => '/(' . preg_quote($this->options['tag_block'][0] . $this->options['whitespace_trim'], '/') . '|' . preg_quote($this->options['tag_block'][0], '/') . ')\s*(?:end%s)\s*(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_block'][1], '/') . ')/s',
            'operator' => $this->getOperatorRegex(),
            'lex_comment' => '/(?:' . preg_quote($this->options['whitespace_trim'], '/') . preg_quote($this->options['tag_comment'][1], '/') . '\s*|' . preg_quote($this->options['tag_comment'][1], '/') . ')\n?/s',
            'lex_block_raw' => '/\s*(raw|verbatim)\s*(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_block'][1], '/') . ')/As',
            'lex_block_line' => '/\s*line\s+(\d+)\s*' . preg_quote($this->options['tag_block'][1], '/') . '/As',
            'lex_tokens_start' => '/(' . preg_quote($this->options['tag_variable'][0], '/') . '|' . preg_quote($this->options['tag_block'][0], '/') . '|' . preg_quote($this->options['tag_comment'][0], '/') . ')(' . preg_quote($this->options['whitespace_trim'], '/') . ')?/s',
            'interpolation_start' => '/' . preg_quote($this->options['interpolation'][0], '/') . '\s*/A',
            'interpolation_end' => '/\s*' . preg_quote($this->options['interpolation'][1], '/') . '/A',
        ];
    }
    public function tokenize($code, $name = null)
    {
        if (!$code instanceof Twig_Source) {
            @trigger_error(sprintf('Passing a string as the $code argument of %s() is deprecated since version 1.27 and will be removed in 2.0. Pass a Twig_Source instance instead.', __METHOD__), E_USER_DEPRECATED);
            $this->source = new Twig_Source($code, $name);
        } else {
            $this->source = $code;
        }
        if (((int) ini_get('mbstring.func_overload')) & 2) {
            @trigger_error('Support for having "mbstring.func_overload" different from 0 is deprecated version 1.29 and will be removed in 2.0.', E_USER_DEPRECATED);
        }
        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        } else {
            $mbEncoding = null;
        }
        $this->code = str_replace(["\r\n", "\r"], "\n", $this->source->getCode());
        $this->filename = $this->source->getName();
        $this->cursor = 0;
        $this->lineno = 1;
        $this->end = strlen($this->code);
        $this->tokens = [];
        $this->state = self::STATE_DATA;
        $this->states = [];
        $this->brackets = [];
        $this->position = -1;
        preg_match_all($this->regexes['lex_tokens_start'], $this->code, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches;
        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;
                case self::STATE_BLOCK:
                    $this->lexBlock();
                    break;
                case self::STATE_VAR:
                    $this->lexVar();
                    break;
                case self::STATE_STRING:
                    $this->lexString();
                    break;
                case self::STATE_INTERPOLATION:
                    $this->lexInterpolation();
                    break;
            }
        }
        $this->pushToken(Twig_Token::EOF_TYPE);
        if (!empty($this->brackets)) {
            list($expect, $lineno) = array_pop($this->brackets);
            throw new Twig_Error_Syntax(sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
        }
        if ($mbEncoding) {
            mb_internal_encoding($mbEncoding);
        }
        return new Twig_TokenStream($this->tokens, $this->source);
    }
    protected function lexData()
    {
        if ($this->position == count($this->positions[0]) - 1) {
            $this->pushToken(Twig_Token::TEXT_TYPE, substr($this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }
        $position = $this->positions[0][++$this->position];
        while ($position[1] < $this->cursor) {
            if ($this->position == count($this->positions[0]) - 1) {
                return;
            }
            $position = $this->positions[0][++$this->position];
        }
        $text = $textContent = substr($this->code, $this->cursor, $position[1] - $this->cursor);
        if (isset($this->positions[2][$this->position][0])) {
            $text = rtrim($text);
        }
        $this->pushToken(Twig_Token::TEXT_TYPE, $text);
        $this->moveCursor($textContent . $position[0]);
        switch ($this->positions[1][$this->position][0]) {
            case $this->options['tag_comment'][0]:
                $this->lexComment();
                break;
            case $this->options['tag_block'][0]:
                                if (preg_match($this->regexes['lex_block_raw'], $this->code, $match, null, $this->cursor)) {
                                    $this->moveCursor($match[0]);
                                    $this->lexRawData($match[1]);
                                } elseif (preg_match($this->regexes['lex_block_line'], $this->code, $match, null, $this->cursor)) {
                                    $this->moveCursor($match[0]);
                                    $this->lineno = (int) $match[1];
                                } else {
                                    $this->pushToken(Twig_Token::BLOCK_START_TYPE);
                                    $this->pushState(self::STATE_BLOCK);
                                    $this->currentVarBlockLine = $this->lineno;
                                }
                break;
            case $this->options['tag_variable'][0]:
                $this->pushToken(Twig_Token::VAR_START_TYPE);
                $this->pushState(self::STATE_VAR);
                $this->currentVarBlockLine = $this->lineno;
                break;
        }
    }
    protected function lexBlock()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_block'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::BLOCK_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }
    protected function lexVar()
    {
        if (empty($this->brackets) && preg_match($this->regexes['lex_var'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::VAR_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }
    protected function lexExpression()
    {
        if (preg_match('/\s+/A', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);
            if ($this->cursor >= $this->end) {
                throw new Twig_Error_Syntax(sprintf('Unclosed "%s".', self::STATE_BLOCK === $this->state ? 'block' : 'variable'), $this->currentVarBlockLine, $this->source);
            }
        }
        if (preg_match($this->regexes['operator'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::OPERATOR_TYPE, preg_replace('/\s+/', ' ', $match[0]));
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::NAME_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor)) {
            $number = (float) $match[0];
            if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                $number = (int) $match[0];
            }
            $this->pushToken(Twig_Token::NUMBER_TYPE, $number);
            $this->moveCursor($match[0]);
        } elseif (false !== strpos(self::PUNCTUATION, $this->code[$this->cursor])) {
            if (false !== strpos('([{', $this->code[$this->cursor])) {
                $this->brackets[] = [$this->code[$this->cursor], $this->lineno];
            } elseif (false !== strpos(')]}', $this->code[$this->cursor])) {
                if (empty($this->brackets)) {
                    throw new Twig_Error_Syntax(sprintf('Unexpected "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
                }
                list($expect, $lineno) = array_pop($this->brackets);
                if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}')) {
                    throw new Twig_Error_Syntax(sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
                }
            }
            $this->pushToken(Twig_Token::PUNCTUATION_TYPE, $this->code[$this->cursor]);
            ++$this->cursor;
        } elseif (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::STRING_TYPE, stripcslashes(substr($match[0], 1, -1)));
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = ['"', $this->lineno];
            $this->pushState(self::STATE_STRING);
            $this->moveCursor($match[0]);
        } else {
            throw new Twig_Error_Syntax(sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
        }
    }
    protected function lexRawData($tag)
    {
        if ('raw' === $tag) {
            @trigger_error(sprintf('Twig Tag "raw" is deprecated since version 1.21. Use "verbatim" instead in %s at line %d.', $this->filename, $this->lineno), E_USER_DEPRECATED);
        }
        if (!preg_match(str_replace('%s', $tag, $this->regexes['lex_raw_data']), $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor)) {
            throw new Twig_Error_Syntax(sprintf('Unexpected end of file: Unclosed "%s" block.', $tag), $this->lineno, $this->source);
        }
        $text = substr($this->code, $this->cursor, $match[0][1] - $this->cursor);
        $this->moveCursor($text . $match[0][0]);
        if (false !== strpos($match[1][0], $this->options['whitespace_trim'])) {
            $text = rtrim($text);
        }
        $this->pushToken(Twig_Token::TEXT_TYPE, $text);
    }
    protected function lexComment()
    {
        if (!preg_match($this->regexes['lex_comment'], $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor)) {
            throw new Twig_Error_Syntax('Unclosed comment.', $this->lineno, $this->source);
        }
        $this->moveCursor(substr($this->code, $this->cursor, $match[0][1] - $this->cursor) . $match[0][0]);
    }
    protected function lexString()
    {
        if (preg_match($this->regexes['interpolation_start'], $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = [$this->options['interpolation'][0], $this->lineno];
            $this->pushToken(Twig_Token::INTERPOLATION_START_TYPE);
            $this->moveCursor($match[0]);
            $this->pushState(self::STATE_INTERPOLATION);
        } elseif (preg_match(self::REGEX_DQ_STRING_PART, $this->code, $match, null, $this->cursor) && strlen($match[0]) > 0) {
            $this->pushToken(Twig_Token::STRING_TYPE, stripcslashes($match[0]));
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            list($expect, $lineno) = array_pop($this->brackets);
            if ('"' != $this->code[$this->cursor]) {
                throw new Twig_Error_Syntax(sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
            }
            $this->popState();
            ++$this->cursor;
        } else {
            throw new Twig_Error_Syntax(sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
        }
    }
    protected function lexInterpolation()
    {
        $bracket = end($this->brackets);
        if ($this->options['interpolation'][0] === $bracket[0] && preg_match($this->regexes['interpolation_end'], $this->code, $match, null, $this->cursor)) {
            array_pop($this->brackets);
            $this->pushToken(Twig_Token::INTERPOLATION_END_TYPE);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }
    protected function pushToken($type, $value = '')
    {
        if (Twig_Token::TEXT_TYPE === $type && '' === $value) {
            return;
        }
        $this->tokens[] = new Twig_Token($type, $value, $this->lineno);
    }
    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
        $this->lineno += substr_count($text, "\n");
    }
    protected function getOperatorRegex()
    {
        $operators = array_merge(
            ['='],
            array_keys($this->env->getUnaryOperators()),
            array_keys($this->env->getBinaryOperators())
        );
        $operators = array_combine($operators, array_map('strlen', $operators));
        arsort($operators);
        $regex = [];
        foreach ($operators as $operator => $length) {
            if (ctype_alpha($operator[$length - 1])) {
                $r = preg_quote($operator, '/') . '(?=[\s()])';
            } else {
                $r = preg_quote($operator, '/');
            }
            $r = preg_replace('/\s+/', '\s+', $r);
            $regex[] = $r;
        }
        return '/' . implode('|', $regex) . '/A';
    }
    protected function pushState($state)
    {
        $this->states[] = $this->state;
        $this->state = $state;
    }
    protected function popState()
    {
        if (0 === count($this->states)) {
            throw new Exception('Cannot pop state without a previous state.');
        }
        $this->state = array_pop($this->states);
    }
}
class_alias('Twig_Lexer', 'Twig\Lexer', false);
