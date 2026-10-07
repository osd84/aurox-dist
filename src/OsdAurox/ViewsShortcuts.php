<?php

namespace OsdAurox;

class ViewsShortcuts
{
    public static function ListThisDirView($dir) {

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                echo "<h1>Index of " . basename($dir) . "</h1>";
                echo "<ul>";
                while (($file = readdir($dh)) !== false) {
                    if ($file !== '.' && $file !== '..') {
                        echo "<li><a href=\"{$file}\">{$file}</a></li>";
                    }
                }
                echo "</ul>";
                closedir($dh);
            } else {
                echo "Unable to open directory.";
            }
        } else {
            echo "Not a directory.";
        }
    }
}