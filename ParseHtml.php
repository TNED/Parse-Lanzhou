<?php

namespace app\parse\controller;

use think\Controller;

/***
 * Class ParseHtml https://github.com/keygle/html-parser详情
 * @package app\parse\controller
 */
class ParseHtml extends Controller
{
    protected $node = null;
    protected $lFind = array();

    public function __construct($html = null) {
        parent::__construct();
        if ($html !== null) {
            if ($html instanceof \DOMNode) {
                $this->node = $html;
            } else {
                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->strictErrorChecking = false;
                if (@$dom->loadHTML($html)) {
                    $this->node = $dom;
                } else {
                    throw new \Exception('load html error');
                }
            }
        }
    }
    //这里是Abstract
    public function __destruct() {
        $this->clearNode($this->node);
    }

    protected function match($exp, $pattern, $value) {
        $pattern = strtolower($pattern);
        $value = strtolower($value);
        switch ($exp) {
            case '=' :
                return ($value === $pattern);
            case '!=' :
                return ($value !== $pattern);
            case '^=' :
                return preg_match("/^" . preg_quote($pattern, '/') . "/", $value);
            case '$=' :
                return preg_match("/" . preg_quote($pattern, '/') . "$/", $value);
            case '*=' :
                if ($pattern [0] == '/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/" . $pattern . "/i", $value);
        }
        return false;
    }

    protected function parse_selector($selector_string) {
        $pattern = '/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)["\']?(.*?)["\']?)?\])?([\/, ]+)/is';
        preg_match_all($pattern, trim($selector_string) . ' ', $matches, PREG_SET_ORDER);
        $selectors = array();
        $result = array();
        foreach ($matches as $m) {
            $m [0] = trim($m [0]);
            if ($m [0] === '' || $m [0] === '/' || $m [0] === '//')
                continue;
            if ($m [1] === 'tbody')
                continue;
            list ($tag, $key, $val, $exp, $no_key) = array($m [1], null, null, '=', false);
            if (!empty ($m [2])) {
                $key = 'id';
                $val = $m [2];
            }
            if (!empty ($m [3])) {
                $key = 'class';
                $val = $m [3];
            }
            if (!empty ($m [4])) {
                $key = $m [4];
            }
            if (!empty ($m [5])) {
                $exp = $m [5];
            }
            if (!empty ($m [6])) {
                $val = $m [6];
            }
            // convert to lowercase
            $tag = strtolower($tag);
            $key = strtolower($key);
            // elements that do NOT have the specified attribute
            if (isset ($key [0]) && $key [0] === '!') {
                $key = substr($key, 1);
                $no_key = true;
            }
            $result [] = array($tag, $key, $val, $exp, $no_key);
            if (trim($m [7]) === ',') {
                $selectors [] = $result;
                $result = array();
            }
        }
        if (count($result) > 0) {
            $selectors [] = $result;
        }
        return $selectors;
    }

    protected function clearNode(&$node) {
        if (!empty($node->child)) {
            foreach ($node->child as $child) {
                $this->clearNode($child);
            }
        }
        unset($node);
    }
    //这里是DOM的开始区域
    protected function ParseHtmlDom($node = null) {
        if ($node !== null) {
            if ($node instanceof \DOMNode) {
                $this->node = $node;
            } else {
                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->strictErrorChecking = false;
                if (@$dom->loadHTML($node)) {
                    $this->node = $dom;
                } else {
                    throw new \Exception('load html error');
                }
            }
        }
    }

    public function findBreadthFirst($selector, $idx = null) {
        if (empty($this->node->childNodes)) {
            return false;
        }
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0) {
            return false;
        }
        $found = array();
        for ($c = 0; $c < $count; $c++) {
            if (($level = count($selectors [$c])) === 0) {
                return false;
            }
            $need_to_search = iterator_to_array($this->node->childNodes);
            $search_level = 1;
            while (!empty($need_to_search)) {
                $temp = array();
                foreach ($need_to_search as $search) {
                    if ($search_level >= $level) {
                        $rs = $this->seek($search, $selectors [$c], $level - 1);
                        if ($rs !== false && $idx !== null) {
                            if ($idx == count($found)) {
                                return new self($rs);
                            } else {
                                $found[] = new self($rs);
                            }
                        } elseif ($rs !== false) {
                            $found[] = new self($rs);
                        }
                    }
                    $temp[] = $search;
                    array_shift($need_to_search);
                }
                foreach ($temp as $temp_val) {
                    if (!empty($temp_val->childNodes)) {
                        foreach ($temp_val->childNodes as $val) {
                            $need_to_search[] = $val;
                        }
                    }
                }
                $search_level++;
            }
        }
        if ($idx !== null) {
            if ($idx < 0) {
                $idx = count($found) + $idx;
            }
            if (isset($found[$idx])) {
                return $found[$idx];
            } else {
                return false;
            }
        }
        return $found;
    }

    public function findDepthFirst($selector, $idx = null) {
        if (empty($this->node->childNodes)) {
            return false;
        }
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0) {
            return false;
        }
        for ($c = 0; $c < $count; $c++) {
            if (($level = count($selectors [$c])) === 0) {
                return false;
            }
            $this->search($this->node, $idx, $selectors [$c], $level);
        }
        $found = $this->lFind;
        $this->lFind = array();
        if ($idx !== null) {
            if ($idx < 0) {
                $idx = count($found) + $idx;
            }
            if (isset($found[$idx])) {
                return $found[$idx];
            } else {
                return false;
            }
        }
        return $found;
    }

    public function getPlainText() {
        return $this->text($this->node);
    }

    public function getAttr($name) {
        $oAttr = $this->node->attributes->getNamedItem($name);
        if (isset($oAttr)) {
            return $oAttr->nodeValue;
        }
        return null;
    }

    protected function search(&$search, $idx, $selectors, $level, $search_levle = 0) {
        if ($search_levle >= $level) {
            $rs = $this->seek($search, $selectors, $level - 1);
            if ($rs !== false && $idx !== null) {
                if ($idx == count($this->lFind)) {
                    $this->lFind[] = new self($rs);
                    return true;
                } else {
                    $this->lFind[] = new self($rs);
                }
            } elseif ($rs !== false) {
                $this->lFind[] = new self($rs);
            }
        }
        if (!empty($search->childNodes)) {
            foreach ($search->childNodes as $val) {
                if ($this->search($val, $idx, $selectors, $level, $search_levle + 1)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function text(&$node) {
        return $node->textContent;
    }

    protected function seek($search, $selectors, $current) {
        if (!($search instanceof \DOMElement)) {
            return false;
        }
        list ($tag, $key, $val, $exp, $no_key) = $selectors [$current];
        $pass = true;
        if ($tag === '*' && !$key) {
            exit('tag为*时，key不能为空');
        }
        if ($tag && $tag != $search->tagName && $tag !== '*') {
            $pass = false;
        }
        if ($pass && $key) {
            if ($no_key) {
                if ($search->hasAttribute($key)) {
                    $pass = false;
                }
            } else {
                if ($key != "plaintext" && !$search->hasAttribute($key)) {
                    $pass = false;
                }
            }
        }
        if ($pass && $key && $val && $val !== '*') {
            if ($key == "plaintext") {
                $nodeKeyValue = $this->text($search);
            } else {
                $nodeKeyValue = $search->getAttribute($key);
            }
            $check = $this->match($exp, $val, $nodeKeyValue);
            if (!$check && strcasecmp($key, 'class') === 0) {
                foreach (explode(' ', $search->getAttribute($key)) as $k) {
                    if (!empty ($k)) {
                        $check = $this->match($exp, $val, $k);
                        if ($check) {
                            break;
                        }
                    }
                }
            }
            if (!$check) {
                $pass = false;
            }
        }
        if ($pass) {
            $current--;
            if ($current < 0) {
                return $search;
            } elseif ($this->seek($this->getParent($search), $selectors, $current)) {
                return $search;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function getParent($node) {
        return $node->parentNode;
    }
}