<?php

/**
 * Aligator
 *
 * ProcessWire Module to render nested tree using callbacks for each item
 *
 *
 */


class Aligator extends WireData implements Module{

    protected $defaultOptions;
    protected $defaultStates;
    public $enableStates;
    public $levels;
    public $collapsed;

    public static function getModuleInfo() {
        return array(
            'title' => 'Aligator',
            'version' => 2,
            'summary' => 'Module to render nested markup from a specific root parent or an PageArray of roots.',
            'href' => 'http://processwire.com',
            'singular' => false,
            'autoload' => false,
            'icon' => 'compass',
        );
    }

    public function init(){

        $this->enableStates = false;
        $this->levels = null;
        $this->collapsed = false;

        $this->defaultStates = array(
            "is_parent" => "parent",
            "is_current" => "current",
            "has_children" => "has_children",
            "is_first" => "first",
            "is_last" => "last",
        );

        $this->defaultOptions = array(
            "selector" => "",
            "callback" => function($item, $level, $states) {
                $classes = $states ? " class='$states'" : "";
                return array(
                    "item" => "<a href='$item->url'>$item->title</a>",
                    "listOpen" => "<li$classes>",
                    "listClose" => "</li>",
                    "wrapperOpen" => "<ul>",
                    "wrapperClose" => "</ul>"
                );
            }
        );

    }

    public function render($root, $options = array()) {
        return $this->renderTree($root, $options, $level = 0);
    }

    /**
     * Recursively render the tree
     * @param  Page  $root     ProcessWire Page or PageArray
     * @param  array   $options  Options array containing configuration and callback for each item/level
     * @param  int   $level  Current level
     * @return string           The generated markup
     */
    protected function renderTree($root, $options = array(), $level) {

        $level++;
        $out = "";
        if($this->levels && $level > $this->levels) return;

        $return = array();
        $selector = "";
        $children = null;
        $states = "";

        if(count($options) && isset($options[$level-1])) {
            $selector = isset($options[$level-1]['selector'])
                            ? $options[$level-1]['selector']
                            : $this->defaultOptions['selector'];
        } else {
            $selector = $this->defaultOptions['selector'];
        }

        if($root instanceof PageArray){
            $children = $root;
        } else {
            $children = $root->children($selector);
        }

        if(!count($children)) return;
        $firstChild = $children->first();

        if(count($options) && isset($options[$level-1])){
            if(isset($options[$level-1]['callback'])) {
                $return = call_user_func_array($options[$level-1]['callback'], array($firstChild, $level, $states));
            } else {
                $return = array();
            }
            $returnDefault = call_user_func_array($this->defaultOptions['callback'], array($firstChild, $level, $states));
            $return = array_merge($returnDefault, $return);
        } else {
            $return = call_user_func_array($this->defaultOptions['callback'], array($firstChild, $level, $states));
        }


        if($return["wrapperOpen"]) {
            $out .= $return["wrapperOpen"];
        } else if($level == 1) {
            $out .= $return["wrapperOpen"];
        } else if($root->numChildren($selector)) {
            $out .= $return["wrapperOpen"];
        }

        foreach($children as $key => $page) {

            $s = "";

            $has_children = false;
            $is_parent = false;
            $is_current = $page === $this->wire("page");
            $isRoot = false;

            if($level == 1){
                $isRoot = $page->children()->has("id=$children");
            }

            $is_parent = !$isRoot && wire("page")->parents->has($page) ? true : false;
            $has_children = !$isRoot && ($this->levels && $level < $this->levels) ? ($page->child($selector)->id ? true : false) : false;

            if($this->enableStates){
                $states = array(
                    'is_parent' => $is_parent ? $this->defaultStates['is_parent'] : "",
                    'is_current' => $is_current ? $this->defaultStates['is_current'] : "",
                    'has_children' => $has_children ? $this->defaultStates['has_children'] : "",
                    'is_first' => $children->first === $page ? $this->defaultStates['is_first'] : "",
                    'is_last' => $page === $children->last || $page === $page->siblings($selector)->last ? $this->defaultStates['is_last'] : "",
                );
                $states = array_filter($states);
                $states = implode(" ", $states);
            }
            if(!$this->collapsed){
                if($has_children) {
                    $s = $this->renderTree($page, $options, $level);
                }
            } else {
                if($has_children && $is_parent) {
                    $s = $this->renderTree($page, $options, $level);
                } else if($is_current) {
                    $s = $this->renderTree($page, $options, $level);
                }
            }

            if(count($options) && isset($options[$level-1])) {
                // $selector = isset($options[$level-1]['selector'])
                //                 ? $options[$level-1]['selector']
                //                 : $this->defaultOptions['selector'];

                if(isset($options[$level-1]['callback'])){
                    $return = call_user_func_array($options[$level-1]['callback'], array($page, $level, $states));
                } else {
                    $return = array();
                }
                $returnDefault = call_user_func_array($this->defaultOptions['callback'], array($page, $level, $states));
                $return = array_merge($returnDefault, $return);

            } else {
                // $selector = $this->defaultOptions['selector'];
                $return = call_user_func_array($this->defaultOptions['callback'], array($page, $level, $states));
            }


            if($this->wire("config")->debug) $debug = "<!-- selector: $selector, level: $level -->";
            $out .= $return["listOpen"] . $return["item"] . $s . $return["listClose"] . $debug;

        }

        if($return["wrapperClose"]) {
            $out .= $return["wrapperClose"];
        } else if($level == 1) {
            $out .= $return["wrapperClose"];;
        } else if($root->numChildren($selector)) {
            $out .= $return["wrapperClose"];;
        }

        return $out;
    }


    public function setDefaultOptions($options = array()){
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    public function setDefaultStates($options = array()){
        if(is_bool($options)){
            $this->enableStates = $options;
            $this->defaultStates = $options ? $this->defaultStates : array();
        } else {
            $this->enableStates = true;
            $this->defaultStates = array_merge($this->defaultStates, $options);
        }
    }

}
