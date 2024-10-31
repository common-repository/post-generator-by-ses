<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class BrowseNode
{
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        $this->BrowseNodeId = $xpath->query('./az:BrowseNodeId/text()', $dom)->item(0)->data;
        $this->Name = $xpath->query('./az:Name/text()', $dom)->item(0)->data;
        $result = $xpath->query('./az:IsCategoryRoot/text()', $dom);
        if ($result->length == 1) {
            $this->IsCategoryRoot = $result->item(0)->data;
        } else {
            $this->IsCategoryRoot = false;
        }
        $this->Children = [];
        $children = $xpath->query('./az:Children/az:BrowseNode', $dom);
        if ($children->length > 0) {
            foreach ($children as $child) {
                $this->Children[] = new BrowseNode($child);
            }
        }
        $this->Items = [];
        $paths = [
            './az:TopSellers/az:TopSeller',
            './az:NewReleases/az:NewRelease',
            './az:TopItemSet/az:TopItem',
        ];
        foreach ($paths as $path) {
            $nodes = $xpath->query($path, $dom);
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $this->Items[] = new BrowseNodeItem($node);
                }
            }
        }
        $this->_dom = $dom;
    }
    public function __toString()
    {
        return sprintf('%s (%s): %d children', $this->BrowseNodeId, $this->Name, count($this->Children));
    }
}
