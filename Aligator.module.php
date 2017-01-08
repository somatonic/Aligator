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
    protected $defaultConfig;

    public static function getModuleInfo() {
        return array(
            'title' => 'Aligator',
            'version' => 2,
            'summary' => 'Module to render nested markup from a specific root parent or an PageArray of roots.',
            'href' => 'http://processwire.com',
            'singular' => true,
            'autoload' => false,
            'icon' => 'compass',
        );
    }

    public function init(){

        // set the default options
        $this->defaultOptions = array(
            "selector" => "",
            "callback" => function($item, $level, $wire){
                return array(
                    "item" => "<a href='$item->url'>" . $item->title . "</a>",
                    "listOpen" => "<li>",
                    "listClose" => "</li>",
                    "wrapperOpen" => "<ul>",
                    "wrapperClose" => "</ul>"
                );
            }
        );

    }

    /**
     * Recursively render the tree
     * @param  Page  $root     ProcessWire Page or PageArray
     * @param  array   $options  Options array containing configuration and callback for each item/level
     * @param  int  $limit    Maximum level to render the tree
     * @param  boolean $collapse Whether to render only the active page branch or all open
     * @return string           The generated markup
     */
    public function render($root, $options = array(), $limit = null, $collapse = false) {

        static $level = 0;
        $level++;
        $out = "";
        if($limit && $level > $limit) return;

        $wire = $this->wire;

        $return = array();
        $selector = "";
        $children = null;

        if(count($options) && isset($options[$level-1])) {

            $selector = isset($options[$level-1]['selector']) ? $options[$level-1]['selector'] : "";

            if($root instanceof PageArray){
                $children = $root;
            } else {
                $children = $root->children($selector);
            }

            if(!count($children)) return;
            $firstChild = $children->first();
            if(!$firstChild->id) return;
            $return = isset($options[$level-1]['callback']) ? call_user_func_array($options[$level-1]['callback'], array($firstChild, $level, $wire)) : array();
            $returnDefault = call_user_func_array($this->defaultOptions['callback'], array($firstChild, $level, $wire));
            $return = array_merge($returnDefault, $return);

        } else {

            if($root instanceof PageArray){
                $children = $root;
            } else {
                $children = $root->children($selector);
            }

            if(!count($children)) return;
            $firstChild = $children->first();
            if(!$firstChild->id) return;
            $return = call_user_func_array($this->defaultOptions['callback'], array($firstChild, $level, $wire));
        }

        if($return["wrapperOpen"]) {
            $out .= $return["wrapperOpen"];
        } else if($level == 1) {
            $out .= "<ul>";
        } else if($root->numChildren($selector)) {
            $out .= "<ul>";
        }


        foreach($children as $key => $page) {
            $s = "";

            $is_parent = wire("page")->parents->has($page);
            $is_current = $page === wire("page");

            if(!$collapse){
                if($page->numChildren($selector)) {
                    $s = $this->render($page, $options, $limit, $collapse);
                    $level--;
                }
            } else {
                if($page->numChildren($selector) && $is_parent) {
                    $s = $this->render($page, $options, $limit, $collapse);
                    $level--;
                } else if($is_current){
                    $s = $this->render($page, $options, $limit, $collapse);
                    $level--;
                }
            }

            if(count($options) && isset($options[$level-1])) {
                $selector = isset($options[$level-1]['selector']) ? $options[$level-1]['selector'] : "";
                $return = isset($options[$level-1]['callback']) ? call_user_func_array($options[$level-1]['callback'], array($page, $level, $wire)) : array();
                $returnDefault = call_user_func_array($this->defaultOptions['callback'], array($page, $level, $wire));
                $return = array_merge($returnDefault, $return);
            } else {
                $return = call_user_func_array($this->defaultOptions['callback'], array($page, $level, $wire));
            }
            $out .= $return["listOpen"] . $return["item"] . $s . $return["listClose"];

        }

        if($return["wrapperOpen"]) {
            $out .= $return["wrapperClose"];
        } else if($level == 1) {
            $out .= "</ul>";
        } else if($root->numChildren($selector)) {
            $out .= "</ul>";
        }

        return $out;
    }


    public function setDefaultOptions($options = array()){
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

}
