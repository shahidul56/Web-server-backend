<?php
class Push
{
    private $title;
    private $data;
    private $is_background;
    private $flag;

    function __construct()
    {

    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setIsBackground($is_background)
    {
        $this->is_background = $is_background;
    }

    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    public function getPush()
    {
        $res                  = array();
        $res['title']         = $this->title;
        $res['is_background'] = $this->is_background;
        $res['flag']          = $this->flag;
        $res['data']          = $this->data;

        return $res;
    }
}
