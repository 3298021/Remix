<?php
/**
 * 歌曲地址解析
 *
 * @package Minty
 * @author shingchi
 */
class Minty_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /** 虾米API地址 */
    const API_URL = 'http://m.xiami.com/web/get-songs?type=0';

    /** 设置缓存键前缀 */
    const SONG_CACHE_PREFIX = 'xiami-song-';
    const LIST_CACHE_PREFIX = 'xiami-list-';
    const ALBUM_CACHE_PREFIX = 'xiami-album-';
    const COLLECT_CACHE_PREFIX = 'xiami-collect-';

    /** 缓存对象 */
    private static $cache;

    /** 虾米TOKEN */
    private $token;

    /** 虾米TOKEN */
    private $config;

    /** 请求头信息 */
    private $headers;

    /**
     * 构造方法
     *
     * @access public
     * @var void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 设置请求头信息 */
        $this->setHeaders();

        /** 获取虾米TOKEN */
        $this->getToken();
    }

    /**
     * 获取单曲
     *
     * @access public
     * @param string $id 歌曲ID
     * @return string
     */
    public function song()
    {
        $id = $this->request->get('id');
        $key = static::SONG_CACHE_PREFIX . $id;
        $message = $this->getCache($key);

        if (empty($message)) {
            $url = static::API_URL . '&rtype=song&id=' . $id . '&_xiamitoken=' . $this->token;
            $result = $this->send($url);
            $message = $this->parseResult($result);

            $this->setCache($key, $message);
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取列表
     *
     * @access public
     * @return string
     */
    public function songs()
    {
        $ids = explode(',', $this->request->get('id'));
        $ids = array_map('trim', $ids);
        $size = count($ids) - 1;
        $key = static::LIST_CACHE_PREFIX . $ids[0] . '-' . $ids[$size];
        $message = $this->getCache($key);

        if (empty($message)) {
            $message = array();

            foreach ($ids as $i => $id) {
                $url = static::API_URL . '&rtype=song&id=' . $id . '&_xiamitoken=' . $this->token;
                $result = $this->send($url);
                $result = $this->parseResult($result);
                $message[$i] = $result[0];
            }

            $this->setCache($key, $message);
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取专辑
     *
     * @access public
     * @return string
     */
    public function album()
    {
        $id = $this->request->get('id');
        $key = static::ALBUM_CACHE_PREFIX . $id;
        $message = $this->getCache($key);

        if (empty($message)) {
            $url = static::API_URL . '&rtype=album&id=' . $id . '&_xiamitoken=' . $this->token;
            $result = $this->send($url);
            $message = $this->parseResult($result);

            $this->setCache($key, $message);
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取精选集
     *
     * @access public
     * @return string
     */
    public function collect()
    {
        $id = $this->request->get('id');
        $key = static::COLLECT_CACHE_PREFIX . $id;
        $message = $this->getCache($key);

        if (empty($message)) {
            $url = static::API_URL . '&rtype=collect&id=' . $id . '&_xiamitoken=' . $this->token;
            $result = $this->send($url);
            $message = $this->parseResult($result);

            $this->setCache($key, $message);
        }

        $this->response->throwJson($message);
    }

    /**
     * 发送请求
     *
     * @access private
     * @param string $url 请求地址
     * @return array
     */
    private function send($url)
    {
        $client = Typecho_Http_Client::get();
        $headers = $this->headers;
        $headers['Cookie'] = '_xiamitoken=' . $this->token . '; visit=1';

        foreach ($headers as $key => $value) {
            $client->setHeader($key, $value);
        }

        $client->setTimeout(50)->send($url);

        if (200 !== $client->getResponseStatus()) {
            return;
        }
        return Json::decode($client->getResponseBody(), true);
    }

    /**
     * 设置头信息
     *
     * @access private
     * @return viod
     */
    private function setHeaders()
    {
        $this->headers = array(
            'Host' => 'm.xiami.com',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_1_2 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D257 Safari/9537.53',
            'Proxy-Connection' => 'keep-alive',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-FORWARDED-FOR' => '42.156.140.238',
            'CLIENT-IP' => '42.156.140.238',
            'Referer' => 'http://m.xiami.com'
        );
    }

    /**
     * 获取TOKEN
     *
     * @access private
     * @return viod
     */
    private function getToken()
    {
        $token = $this->getCache('xiamiToken');

        if (!empty($token)) {
            $this->token = $token;
            return;
        }

        $client = Typecho_Http_Client::get();

        foreach ($this->headers as $key => $value) {
            $client->setHeader($key, $value);
        }

        $client->send('http://m.xiami.com');
        $responseCookies = $client->getResponseHeader('set-cookie');
        $cookies = explode(';', $responseCookies);

        foreach ($cookies as $cookie) {
            list($key, $value) = explode('=', $cookie);
            if ('_xiamitoken' == $key) {
                $this->token = $value;
            }
            break;
        }

        $this->setCache('xiamiToken', $this->token, 360000);
    }

    /**
     * 解析结果
     *
     * @access private
     * @param array $result 返回数据
     * @return array
     */
    private function parseResult($result = NULL)
    {
        if (isset($result['status']) && 'ok' == $result['status'] && !empty($result['data'])) {
            return $result['data'];
        }

        return;
    }

    /**
     * 获取缓存对象
     *
     * @access private
     * @return Memcache
     */
    private static function getCacheInstance()
    {
        if (empty(static::$cache)) {
            static::$cache = new Memcache();
        }

        return static::$cache;
    }

    /**
     * 设置缓存
     *
     * @access private
     * @param string $key 键
     * @param mixed $value 值
     */
    private function setCache($key, $value, $expire = 21600)
    {
        $cache = static::getCacheInstance();

        $cache->connect('127.0.0.1', 11211);
        $cache->set($key, serialize($value), 0, $expire);
        $cache->close();
    }

    /**
     * 取出缓存
     *
     * @access private
     * @param string $key 键
     * @return array
     */
    private function getCache($key)
    {
        $cache = static::getCacheInstance();

        $cache->connect('127.0.0.1', 11211);
        $data = $cache->get($key);
        $cache->close();

        if ($data) {
            return unserialize($data);
        }

        return;
    }

    /**
     * 删除缓存
     *
     * @access private
     * @param string $key 键
     */
    private function delCache($key)
    {
        $cache = static::getCacheInstance();

        $cache->connect('127.0.0.1', 11211);
        $cache->delete($key);
        $cache->close();
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        Typecho_Widget::widget('Widget_Options')->to($options);

        $siteParts = parse_url($options->siteUrl);
        $refParts = parse_url($this->request->getReferer());

        if (!$this->request->isAjax() || $siteParts['host'] != $refParts['host']) {
            throw new Typecho_Widget_Exception(_t('请求的地址不合法'), 403);
        }

        $this->on($this->request->is('do=song'))->song();
        $this->on($this->request->is('do=list'))->songs();
        $this->on($this->request->is('do=album'))->album();
        $this->on($this->request->is('do=collect'))->collect();
    }
}
