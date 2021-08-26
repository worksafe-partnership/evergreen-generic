<?php

namespace Evergreen\Generic\App;

use Illuminate\Database\Eloquent\Model;

/*
    use config files, xml inside of these.
*/
class Page extends Model
{
    protected $table = "pages";

    protected $fillable = [
        'id',
        'slug',
        'type',
        'icon',
        'label',
        'roles',
        'parent',
        'permissions'
    ];

    protected function generateMenu()
    {
        $menu = [];
        $pages = Page::whereNotNull("icon")
                     ->whereNull("parent")
                     ->get();
                ;
        foreach ($pages as $page) {
            $item = $page->toArray();
            $item = $this->this->getChildren($page);

        }
    }

// function menu($items) {
//     function menu_recursive($parent_item) {
//         global $items;
//         unset($items[$parent_item['id']]);
//         echo '<div style="padding-left: 15px;">';
//         echo '- '.$parent_item['name'];
//         foreach ($items as $item) {
//             if ($item['parent'] == $parent_item['id']) {
//                 menu_recursive($item);
//             }
//         }
//         echo  '</div>';
//     }
//     foreach ($items as $item) {
//         if ($item['parent'] == 0) menu_recursive($item);
//     }
// }

    protected function getChildren($parent)
    {
        $children = Page::where("parent", "=", $parent->id)
                        ->get()
                    ;
        if (!is_null($children)) {
            $parent['children'] = $children;
            foreach ($children as $child) {
                // if($child->)
            }
        }
        return $parent;
    }
}
