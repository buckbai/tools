<?php
/**
 * Async Curl
 *
 * Created by PhpStorm.
 * User: buck
 * Date: 2017/10/23
 * Time: 13:24
 */

class CurlMulti
{
    private $mh;
    private $closure;
    private $closureArgs = [];
    private $chs = [];
    private $actualCountChs = 0;

    public function __construct(int $num = 0)
    {
        $this->mh = curl_multi_init();
        if (!$num) {
            return;
        }
        $this->initChs($num);
    }

    /**
     * '''closure signature'''
     * function ($ch [,$args]) {}
     * @param Closure $closure
     */
    public function setClosure(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Set args for a group of chs
     * @param $chs
     * @param array ...$args
     */
    public function setClosureArgs($chs, ...$args)
    {
        foreach ($chs as $ch) {
            $this->closureArgs[$ch] = $args;
        }
    }

    public function __destruct()
    {
        foreach ($this->chs as $ch) {
            curl_multi_remove_handle($this->mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($this->mh);
    }

    /**
     * Set url and options for ch respectively.
     *
     * If the count of $urls not equal to the ch, it will auto adjust the ch counts in the mh.
     * @param array|string $urls correspond to options
     * @param array $options can only contain one element as array or just a array of curl options for all urls
     * @return array all keys of $this->chs
     */
    public function setHandles($urls, array $options)
    {
        $this->ensureParams($urls, $options);
        $this->clearHandles();
        $this->adjustChs(count($urls));

        foreach (array_values($urls) as $i => $url) {
            curl_setopt($this->chs[$i], CURLOPT_URL, $url);
            curl_setopt_array($this->chs[$i], isset($options[$i]) ? $options[$i] : $options[0]);
            curl_multi_add_handle($this->mh, $this->chs[$i]);
            $this->actualCountChs++;
        }
        return range(0, $this->actualCountChs);
    }

    /**
     * Add handles but not more than $this->chs.
     *
     * @param array|string $urls
     * @param array $options
     * @return array keys of $this->chs this added
     */
    public function addHandles($urls, array $options)
    {
        $this->ensureParams($urls, $options);
        $this->adjustChs(count($urls));

        $start = $this->actualCountChs;
        foreach (array_values($urls) as $i => $url) {
            curl_setopt($this->chs[$this->actualCountChs], CURLOPT_URL, $url);
            curl_setopt_array($this->chs[$this->actualCountChs], isset($options[$i]) ? $options[$i] : $options[0]);
            curl_multi_add_handle($this->mh, $this->chs[$this->actualCountChs]);
            $this->actualCountChs++;
        }
        return range($start, $this->actualCountChs);
    }

    public function exec()
    {
        $active = null;

        do {
            $mrc = curl_multi_exec($this->mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->mh) == -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($this->mh, $active);
                while ($this->closure && false !== ($info = curl_multi_info_read($this->mh))) {
                    $keyCh = array_search($info['handle'], $this->chs);
                    // use $this when call closure
                    ($this->closure)($info['handle'], ...$this->closureArgs[$keyCh]);
                }
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        $this->clearHandles();
    }

    /**
     * Iterate it to get every ch's return.
     *
     * If the Iterator is not end, must call $this->clearHandles explicitly
     * @return Generator
     */
    public function getContents()
    {
        for ($i = 0; $i < $this->actualCountChs; $i++) {
            yield curl_multi_getcontent($this->chs[$i]);
        }
    }

    public function getContent($ch)
    {
        if (in_array($ch, $this->chs)) {
            return curl_multi_getcontent($ch);
        }
    }

    /**
     * After get all of the returns, must remove the ch which in mh.
     */
    public function clearHandles()
    {
        for ($i = 0; $i < $this->actualCountChs; $i++) {
            curl_multi_remove_handle($this->mh, $this->chs[$i]);
        }
    }

    public function errorInfo()
    {
        foreach ($this->chs as $i => $ch) {
            if (($curlError = curl_error($ch)) != '') {
                echo "error $i: ", $curlError, PHP_EOL;
            }
        }
    }


    /**
     * @param $num
     */
    private function initChs($num)
    {
        for ($i = 0; $i < $num; $i++) {
            $this->chs[] = curl_init();
        }
    }

    /**
     * Make sure $urls be array, $options two-dimension.
     *
     * @param mixed $urls
     * @param array $options
     * @throws Exception
     */
    private function ensureParams(&$urls, array &$options)
    {
        if (is_string($urls)) {
            $urls = [$urls];
        }
        $countOptions = count($options);
        $countUrls = count($urls);

        if (!$countUrls || !$countOptions) {
            throw new Exception('urls and options can not be empty.');
        }

        if (is_array(current($options))) {
            if ($countOptions != $countUrls) {
                throw new Exception('urls not match options.');
            }
        }
        $options = [$options];
    }

    private function adjustChs(int $need)
    {
        if (($need = $this->actualCountChs + $need - count($this->chs)) > 0) {
            $this->initChs($need);
        }
    }
}