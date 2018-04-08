<?php
/**
 * Created by PhpStorm.
 * User: buck
 * Date: 2018/1/11
 * Time: 16:49
 */

class Algorithm
{
    public $sequence;


    public function __construct()
    {
        $this->generateUnsort();
        echo 'unsort sequence: ', implode(', ', $this->sequence), PHP_EOL;
    }

    public function __call($name, $arguments)
    {
        $start = microtime(true);
        if (method_exists($this, $name)) {
            $result = $this->{$name}($this->sequence, $arguments);
        } else {
            echo 'method not exist!';
            return;
        }
        echo $name, ' use: ', 1e6 * round(microtime(true) - $start, 9), ' ms', PHP_EOL;
        echo 'sort sequence: ', implode(', ', $result), PHP_EOL;
        return $result;
    }

    private function swap(&$a, &$b)
    {
        $temp = $b;
        $b = $a;
        $a = $temp;
    }

    private function generateUnsort($len = 20)
    {
        $i = 0;
        $unsort = [];
        while ($i++ < $len) {
            $unsort[] = random_int(0, 999);
        }

        $this->sequence = $unsort;
    }

    private function quick($sequence)
    {
        $len = count($sequence);

        if ($len <= 1) {
            return $sequence;
        }

        $pivot = end($sequence);
        $left = $right = [];
        for ($i = 0; $i < $len - 1; $i++) {
            if ($sequence[$i] > $pivot) {
                $right[] = $sequence[$i];
            } else {
                $left[] = $sequence[$i];
            }
        }

        $left = $this->{__FUNCTION__}($left);
        $right = $this->{__FUNCTION__}($right);

        return array_merge($left, [$pivot], $right);
    }

    private function merge($sequence)
    {
        $len = count($sequence);
        if ($len <= 1) {
            return $sequence;
        }

        $index = $len >> 1 + ($len & 1);
        $left = array_slice($sequence, 0, $index);
        $right = array_slice($sequence, $index);

        $left = $this->{__FUNCTION__}($left);
        $right = $this->{__FUNCTION__}($right);

        $sort = [];
        while ($left && $right) {
            $sort[] = $left[0] <= $right[0] ? array_shift($left) : array_shift($right);
        }
        $sort = array_merge($sort, $left, $right);
        return $sort;
    }

    private function bubble($sequence)
    {
        $len = count($sequence);
        for ($i = $len - 1; $i > 0; $i--) {
            for ($j = 0; $j < $i; $j++) {
                if ($sequence[$j] > $sequence[$j + 1]) {
                    $this->swap($sequence[$j], $sequence[$j + 1]);
                }
            }
        }
        return $sequence;
    }

    private function insert($sequence)
    {
        $len = count($sequence);
        for ($i = 1; $i < $len; $i++) {
            $insert = $sequence[$i];
            for ($j = $i - 1; $j >= 0 && $insert < $sequence[$j]; $j--) {
                $sequence[$j + 1] = $sequence[$j];
            }
            $sequence[$j + 1] = $insert;
        }
        return $sequence;
    }

    public function halfSearch($sequence, $search)
    {
        $start = 0;
        $end = count($sequence) - 1;
        if ($search > $sequence[$end] || $search < $sequence[$start]) {
            return false;
        }
        while ($start <= $end) {
            $half = floor(($start + $end) / 2);
            if ($search > $sequence[$half]) {
                $start = $half + 1;
            } elseif ($search < $sequence[$half]) {
                $end = $half - 1;
            } else {
                return $half;
            }
        }
        return false;
    }

    public function test($arr, $search)
    {
        $len = count($arr);
        $start = 0;
        $end = $len - 1;
        if ($search > $arr[$end] || $search < $arr[$start]) {
            return;
        }

        while ($start < $end) {
            $mid = ($start + $end) >> 1;
            if ($search < $arr[$mid]) {
                $end = $mid;
            } elseif ($search > $arr[$mid]) {
                $start = $mid;
            } else {
                return $mid;
            }
        }
        return;
    }

}