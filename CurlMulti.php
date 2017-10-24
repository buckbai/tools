<?php
/**
 * Created by PhpStorm.
 * User: buck
 * Date: 2017/10/23
 * Time: 13:24
 */

class CurlMulti
{
    private $mh;
    private $ch = [];
    private $actualNum;

    public function __construct($num, array $options)
    {
        $this->mh = curl_multi_init();
        $this->actualNum = $num;
        for ($i = 0; $i < $num; $i++) {
            $this->ch[$i] = curl_init();
            curl_setopt_array($this->ch[$i], $options);
        }
    }

    public function __destruct()
    {
        foreach ($this->ch as $ch) {
            curl_multi_remove_handle($this->mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($this->mh);
    }

    /**
     * set the url for ch respectively.
     *
     * if the count of $urls less than the ch, it will auto adjust the ch counts in the mh.
     * @param array $urls
     */
    public function setUrls(array $urls)
    {
        if (count($this->ch) >= ($this->actualNum = count($urls))) {
            foreach ($urls as $k => $url) {
                curl_setopt($this->ch[$k], CURLOPT_URL, $url);
                curl_multi_add_handle($this->mh, $this->ch[$k]);
            }
        } else {
            echo 'warning! ch counts are less than urls.', PHP_EOL;
            exit();
        }
    }

    /**
     * after get all of the returns, must remove the ch which in mh.
     */
    public function clearUrls()
    {
        for ($i = 0; $i < $this->actualNum; $i++) {
            curl_multi_remove_handle($this->mh, $this->ch[$i]);
        }
    }

    public function exec()
    {
        $active = null;
        //execute the handles
        do {
            $mrc = curl_multi_exec($this->mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->mh) == -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($this->mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }

    /**
     * Iterate it to get every ch's return.
     * @return Generator
     */
    public function getContents()
    {
        for ($i = 0; $i < $this->actualNum; $i++) {
            yield curl_multi_getcontent($this->ch[$i]);
        }
    }

    public function errorInfo()
    {
        foreach ($this->ch as $k => $ch) {
            if (($curlError = curl_error($ch)) != '') {
                echo "error $k: ", $curlError, PHP_EOL;
            }
        }
    }
}