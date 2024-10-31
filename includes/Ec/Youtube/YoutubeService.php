<?php
namespace Ec\Youtube;
use Google_Service_YouTube;
class YoutubeService
{
    const ORDER_DATE = 'date';
    const ORDER_RATING = 'rating';
    const ORDER_RELEVANCE = 'relevance';
    const ORDER_VIEW_COUNT = 'viewCount';
    private $googleServiceYoutube;
    public function __construct(Google_Service_YouTube $googleServiceYoutube)
    {
        $this->googleServiceYoutube = $googleServiceYoutube;
    }
    public function searchVideo($q, $limit = 10, array $options = [])
    {
        $searchResponse = $this->googleServiceYoutube->search->listSearch('id,snippet', $options + [
                'q'              => $q,
                'maxResults'     => $limit,
                'order'          => self::ORDER_RELEVANCE,
                'publishedAfter' => '1990-01-01T00:00:00Z',
                'regionCode'     => 'US',                                 'safeSearch'     => 'strict',                 'type'           => 'video',                                                                                             ]);
        $ret = [];
        foreach ($searchResponse['items'] as $item) {
            $video = new Video($item['id']['videoId']);
            $video->setTitle($item['snippet']['title'])
                ->setDescription($item['snippet']['description'])
                ->setPublishedAt(new \DateTime($item['snippet']['publishedAt']));
            $ret[] = $video;
        }
        return $ret;
    }
}
