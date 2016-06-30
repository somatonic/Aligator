# Aligator

Processwire Module to render a nested tree starting from a single root or an array of pages.

Aligator is similar to MarkupSimpleNavigation but has a different approach of how to define the markup for your menu. It doesn't assume any markup or classes. It's up to you to define them where needed. It's less plug and play and requires some more advanced knowledge of ProcessWire, as some additional setup and coding is needed. But allows for powerful and easier customization without using hooks.

Aligator uses callback functions to archive this. Additionally a selector can be used to filter the children for your navigation.

**Note**: This module is a fun project trying to find simple configurable method to render navigations. It's a work in progress and there might be major changes to how the module works.

## How does it work

The options array can contain a configuration for each level separately. So each entry represents a level.

```php
$menuOptions = array(
    array(...), // level 1
    array(...), // level 2
    array(...), // level 3
);
```

Now Aligator has some default configuration that is used in case you don't specify any custom options, or the level it is rendering has no present configuration.

The render method accepts four arguments and returns the markup as a string:

```php
$markup = $modules->Aligator->render(root, options, maxlevels, collapsed);
```

* `root` - A root page or an array of pages (PageArray)
* `options` - Array of options
* `maxlevels` - How many levels the module should render
* `collapsed` - When set to true it will only render the current active root branch

So most basic call without options could be:

```php
$markup = $modules->Aligator->render($pages->get("/"), array(), 3);
```

## The Options Array

The options array is meant as a configuration for each level and item and contains two entries

`selector`

Is a regular ProcessWire selector used to filter children for this level

`callback`

Is an anonymous function that will get called for each page the module renders. It recieves two arguments for you to use: `item` and `level` where $item would be the current rendered child page object and $level the current level.

The callback function must return an associative array containing the markups for the current entry and level. Since this is a function, you can use your own logic and conditions to determine the markup you want to render.

So looking at the default options we have this:

```php
$menuOptions = array(
    array(
        "selector" => "",
        "callback" => function($item, $level){
            // any code here to determine the output
            return array(
                "item" => "<a href='$item->url'>$item->title</a>",
                "listOpen" => "<li>",
                "listClose" => "</li>",
                "wrapperOpen" => "<ul>",
                "wrapperClose" => "</ul>"
            );
        }
    )
);
```

The array you return in the callback contains various predefined keys to define the markup. They're pretty self explanatory. If you omit any of these in the returned array, the module will take the default.


## Default options

You can overwrite the default options the module uses by using the setDefaultOptions() method:

```php
$nav = $modules->Aligator;
$nav->setDefaultOptions(array(
        "selector" => "",
        "callback" => function($item, $level){
            return array(
                "item" => "<a href='$item->url'>$item->title</a>",
                "listOpen" => "<li>",
                "listClose" => "</li>",
                "wrapperOpen" => "<ol>",
                "wrapperClose" => "</ol>",
            );
        }
    )
);
```


## Example

Here we set the default options. This will be used to render the markup when the level it renders isn't defined in the options array. In this example we set only the first level (1 entry) in the options. It will overwrite the default here only using a different "wrapperOpen". After that the default will be used to render the further levels.

```php
$nav = $modules->Aligator;

$nav->setDefaultOptions(array(
        "selector" => "template=basic-page",
        "callback" => function($item, $level){
            $class = $item === wire("page") ? " current" : "";
            $class .= wire("page")->parents->has($item) ? " parent" : "";
            if($level < 3) $class .= $item->numChildren("template=basic-page") ? " has_children" : "";
            return array(
                "item" => "<a href='$item->url'>$item->title</a>",
                "listOpen" => "<li class='level$level$class'>",
                "listClose" => "</li>",
                "wrapperOpen" => "<ul class='dropdown$level'>",
                "wrapperClose" => "</ul>",
            );
        }
    )
);

$menuOptions = array(
    array( // overwrite for first level
        "selector" => "",
        "callback" => function($item, $level){
            return array(
                "wrapperOpen" => "<ul class='mainnav'>"
            );
        },
    )
);

$root = $pages->get("/");
$markup = $nav->render($root, $menuOptions, $levels = 3, $collaped = false);
```


## Example2

Here we let the default options the module has and specify explicit the options for each level.

```php
$menuOptions = array(
    array( // level 1
        "selector" => "template=basic-page|domain_root",
        "callback" => function($item, $level){
            $class = $item === wire("page") ? " current" : "";
            $class .= wire("page")->parents->has($item) ? " parent" : "";
            $class .= $item->numChildren("template=basic-page") ? " has_children" : "";
            return array(
                "item" => "<a href='$item->url'>$item->title $item->template</a>",
                "listOpen" => "<li class='level$level$class'>",
                "listClose" => "</li>",
                "wrapperOpen" => "<ul class='mainnav'>",
                "wrapperClose" => "</ul>",
            );
        },
    ),
    array( // level 2
        "selector" => "template=basic-page",
        "callback" => function($item, $level){
            $class = $item === wire("page") ? " current" : "";
            $class .= wire("page")->parents->has($item) ? " parent" : "";
            $class .= $item->numChildren("template=basic-page") ? " has_children" : "";
            return array(
                "item" => "<a href='$item->url'>$item->title</a>",
                "listOpen" => "<li class='level$level$class'>",
                "listClose" => "</li>",
                "wrapperOpen" => "<ul class='dropdown2'>",
                "wrapperClose" => "</ul>",
            );
        },
    ),
    array( // level 3
        "selector" => "template=basic-page|entry",
        "callback" => function($item, $level){
            $class = $item === wire("page") ? " current" : "";
            $class .= wire("page")->parents->has($item) ? " parent" : "";
            return array(
                "item" => "<a href='$item->url'>$item->title</a>",
                "listOpen" => "<li class='level$level$class'>",
                "listClose" => "</li>",
                "wrapperOpen" => "<ul class='dropdown3'>",
                "wrapperClose" => "</ul>",
            );
        },
    ),
);

$root = $pages->get("/");
$markup = $nav->render($root, $menuOptions, $levels = 3, $collaped = false);
```

## What else

Nothing else. With these spare examples you should be able grasp the concept and use it and render navigations like crazy.

Any feedback or improvements are welcome.

