<?php
namespace Ec\Youtube;
class Video
{
    protected $id;
    protected $title;
    protected $description;
    protected $publishedAt;
    public function __construct($id)
    {
        $this->id = $id;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    public function setPublishedAt(\DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }
    public function getLink()
    {
        return 'https://www.youtube.com/watch?v=' . $this->id;
    }
}
