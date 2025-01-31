<?php
class Twig_TokenParserBroker implements Twig_TokenParserBrokerInterface
{
    protected $parser;
    protected $parsers = [];
    protected $brokers = [];
    public function __construct($parsers = [], $brokers = [], $triggerDeprecationError = true)
    {
        if ($triggerDeprecationError) {
            @trigger_error('The ' . __CLASS__ . ' class is deprecated since version 1.12 and will be removed in 2.0.', E_USER_DEPRECATED);
        }
        foreach ($parsers as $parser) {
            if (!$parser instanceof Twig_TokenParserInterface) {
                throw new LogicException('$parsers must a an array of Twig_TokenParserInterface.');
            }
            $this->parsers[$parser->getTag()] = $parser;
        }
        foreach ($brokers as $broker) {
            if (!$broker instanceof Twig_TokenParserBrokerInterface) {
                throw new LogicException('$brokers must a an array of Twig_TokenParserBrokerInterface.');
            }
            $this->brokers[] = $broker;
        }
    }
    public function addTokenParser(Twig_TokenParserInterface $parser)
    {
        $this->parsers[$parser->getTag()] = $parser;
    }
    public function removeTokenParser(Twig_TokenParserInterface $parser)
    {
        $name = $parser->getTag();
        if (isset($this->parsers[$name]) && $parser === $this->parsers[$name]) {
            unset($this->parsers[$name]);
        }
    }
    public function addTokenParserBroker(self $broker)
    {
        $this->brokers[] = $broker;
    }
    public function removeTokenParserBroker(self $broker)
    {
        if (false !== $pos = array_search($broker, $this->brokers)) {
            unset($this->brokers[$pos]);
        }
    }
    public function getTokenParser($tag)
    {
        if (isset($this->parsers[$tag])) {
            return $this->parsers[$tag];
        }
        $broker = end($this->brokers);
        while (false !== $broker) {
            $parser = $broker->getTokenParser($tag);
            if (null !== $parser) {
                return $parser;
            }
            $broker = prev($this->brokers);
        }
    }
    public function getParsers()
    {
        return $this->parsers;
    }
    public function getParser()
    {
        return $this->parser;
    }
    public function setParser(Twig_ParserInterface $parser)
    {
        $this->parser = $parser;
        foreach ($this->parsers as $tokenParser) {
            $tokenParser->setParser($parser);
        }
        foreach ($this->brokers as $broker) {
            $broker->setParser($parser);
        }
    }
}
