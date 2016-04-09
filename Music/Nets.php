<?php
/**
 * 网易音乐类
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
class Remix_Music_Nets implements Remix_Music_Interface
{
    /**
     * 获取歌曲
     *
     * @param string $id
     */
    public function song($id)
    {
        $url = 'http://music.163.com/api/song/detail/?id=' . $id . '&ids=%5B' . $id . '%5D';
        //$url = 'http://www.yqssgm.com/MusicAPI.php?bgmusic_id=' .$id;
        $result = $this->curl_get($url);

        if (is_null($result)) {
            return;
        }

        /* 解析歌曲 */
        $track = $result['songs'][0];
        $song = $this->parse($track);

        return $song;
    }

    /**
     * 获取列表
     *
     * @param string $id
     * @return string
     */
    public function songs($ids)
    {
        $list = array();

        foreach ($ids as $id) {
            $list[] = $this->song($id);
        }

        return $list;
    }

    /**
     * 获取专辑
     *
     * @param string $id
     */
    public function album($id)
    {
        $url = 'http://music.163.com/api/album/' . $id;
        $result = $this->curl_get($url);

        if (is_null($result)) {
            return;
        }

        /* 解析专辑 */
        $tracks = $result['album']['songs'];
        $album = array();

        foreach ($tracks as $track) {
            $album[] = $this->parse($track);
        }

        return $album;
    }

    /**
     * 获取精选集
     *
     * @param string $id
     */
    public function collect($id)
    {
        $url = 'http://music.163.com/api/playlist/detail?id=' . $id;
        $result = $this->curl_get($url);

        if (is_null($result)) {
            return;
        }

        /* 解析列表 */
        $tracks = $result['result']['tracks'];
        $collect = array();

        foreach ($tracks as $track) {
            $collect[] = $this->parse($track);
        }

        return $collect;
    }

    /**
     * 请求
     *
     * @param string $url
     */
    public function http($url)
    {
			  
        $client = Typecho_Http_Client::get();

        $client->setHeader('Cookie', 'appver=2.0.2')
        ->setHeader('Referer', 'http://music.163.com')
        ->setTimeout(5)
        ->send($url);

        if (200 === $client->getResponseStatus()) {
            $response = Json::decode($client->getResponseBody(), true);
            if (200 === $response['code']) {
                unset($response['code']);
                return $response;
            }
            return;
        }
        return;
    }
    /**
     * 自定义的curl
     */
    public function curl_get($url)
    {
        $refer = "http://music.163.com/";
        $header[] = "Cookie: " . "appver=2.0.2";
        $ch = curl_init();
        
        $porxy = Typecho_Widget::widget('Widget_Options')->plugin('Remix')->proxyAddNet;
        $port = Typecho_Widget::widget('Widget_Options')->plugin('Remix')->proxyPortNet;
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $refer);
			  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        if ($porxy == '0.0.0.0') {
        } else {
			      curl_setopt($ch, CURLOPT_PROXY, $porxy);//代理服务器地址
			      curl_setopt($ch, CURLOPT_PROXYPORT, $port);//代理服务器端口
			      //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式
			      curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式 
			  }
			  
        $output = Json::decode(curl_exec($ch), true);
        curl_close($ch);
        return $output;
    }

    /**
     * 解析歌曲
     *
     * @param string $track
     */
    protected function parse($track)
    {
    	  $src = str_replace('http://m', 'http://p', $track['mp3Url']);
        $authors = array();

        foreach ($track['artists'] as $artist) {
            $authors[] = $artist['name'];
        }

        $author = implode(',', $authors);
        
        if ( empty($src) )
        {
        	  $track['name'] = '(无版权停播) '.$track['name'];
        }
        
        $song = array(
            'id'     => $track['id'],
            'title'  => $track['name'],
            'author' => $author,
            'cover'  => $track['album']['picUrl'],
            'src'    => $src
        );
        $song = array_map('trim', $song);

        return $song;
    }
}
