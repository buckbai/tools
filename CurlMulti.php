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
    private $chs = [];
    private $actualCountChs = 0;

    public function __construct($num = null)
    {
        $this->mh = curl_multi_init();
        if (null === $num) {
            return;
        }
        $this->initCh($num);
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
     * set url and options for ch respectively.
     *
     * if the count of $urls less than the ch, it will auto adjust the ch counts in the mh.
     * @param array $urls correspond to options
     * @param array $options can only contain one element as array or just a array of options for all urls
     * @throws Exception
     */
    public function setHandles(array $urls, array $options = [])
    {
        $countUrls = count($urls);
        $countOptions = count($options);

        // check options
        if (!empty($options)) {
            if (is_array(current($options))) {
                if ($countOptions == 1) {
                    $option = current($options);
                } elseif ($countOptions != $countUrls) {
                    throw new Exception('urls not match options.');
                }
            } else {
                $option = $options;
            }
        }

        // make sure $this->chs count equal $urls
        if (($needChs = $countUrls - count($this->chs)) > 0) {
            $this->initCh($needChs);
        }
        $this->actualCountChs = $countUrls;

        foreach (array_values($urls) as $i => $url) {
            curl_setopt($this->chs[$i], CURLOPT_URL, $url);
            if (isset($option)) {
                curl_setopt_array($this->chs[$i], $option);
            } else {
                curl_setopt_array($this->chs[$i], $options[$i]);
            }
            curl_multi_add_handle($this->mh, $this->chs[$i]);
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
        for ($i = 0; $i < $this->actualCountChs; $i++) {
            yield curl_multi_getcontent($this->chs[$i]);
        }
        $this->clearHandles();
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
    private function initCh($num)
    {
        for ($i = 0; $i < $num; $i++) {
            $this->chs[] = curl_init();
        }
    }

    /**
     * after get all of the returns, must remove the ch which in mh.
     */
    private function clearHandles()
    {
        for ($i = 0; $i < $this->actualCountChs; $i++) {
            curl_multi_remove_handle($this->mh, $this->chs[$i]);
        }
    }
}