<?php
namespace ZendService\Amazon;
class Query extends Amazon
{
    protected $_search = [];
    protected $_searchIndex = null;
    public function __call($method, $args)
    {
        if (strtolower($method) === 'asin') {
            $this->_searchIndex = 'asin';
            $this->_search['ItemId'] = $args[0];
            return $this;
        }
        if (strtolower($method) === 'category') {
            $this->_searchIndex = $args[0];
            $this->_search['SearchIndex'] = $args[0];
        } elseif (isset($this->_search['SearchIndex']) || $this->_searchIndex !== null
            || $this->_searchIndex === 'asin'
        ) {
            $this->_search[$method] = $args[0];
        } else {
            throw new Exception\RuntimeException('You must set a category before setting the search parameters');
        }
        return $this;
    }
    public function search()
    {
        if ($this->_searchIndex === 'asin') {
            return $this->itemLookup($this->_search['ItemId'], $this->_search);
        }
        return $this->itemSearch($this->_search);
    }
}
