<?php
/**
 * Portal API Interface
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Bizuno to newer
 * versions in the future. If you wish to customize Bizuno for your
 * needs please contact PhreeSoft for more information.
 *
 * @name       Bizuno ERP
 * @author     Dave Premo, PhreeSoft <support@phreesoft.com>
 * @copyright  2008-2025, PhreeSoft, Inc.
 * @license    https://www.gnu.org/licenses/agpl-3.0.txt
 * @version    7.x Last Update: 2025-08-30
 * @filesource /lib/api.php
 */

namespace bizuno;

class portalApi
{
    public $lang;
    
    function __construct()
    {
        $iso = !empty($_COOKIE['bizunoEnv']['lang']) ? $_COOKIE['bizunoEnv']['lang'] : 'en_US';
        $lang = [];
        include("lib/locale/$iso.php"); // replace $lang
        $this->lang = $lang;
    }

    public function fs()
    {
        $fn   = $fBad = $eBad = false;
        $parts= explode('/', clean('src', 'path_rel', 'get'), 2);
        if (defined('BIZUNO_DATA') && !empty(BIZUNO_DATA)) {
            if (!empty($parts[1])) {
                if (strpos($parts[1], '?')!==false) { $parts[1] = substr($parts[1], 0, strpos($parts[1], '?')); }
                $io   = new io(); // needs BIZUNO_DATA
                $fn   = (empty($parts[0]) ? BIZUNO_REPO : BIZUNO_DATA).$parts[1];
                $ext  = strtolower(pathinfo($parts[1], PATHINFO_EXTENSION));
                $fBad = !file_exists($fn) ? true : false;
                $validExts = array_merge($io->getValidExt('image'), $io->getValidExt('script'));
                $eBad = !in_array($ext, $validExts) ? true : false;
            } else { $fBad = true; }
        } else { $fBad = true; }

        if ($eBad || $fBad) { $fn = BIZUNO_LOGO; }
        // Send out the image
        header("Accept-Ranges: bytes");
        header("Content-Type: ".getMimeType($parts[1]));
        if ($fn<>BIZUNO_LOGO) {
            header("Content-Length: ".filesize($fn));
            header("Last-Modified: " .date(DATE_RFC2822, filemtime($fn)));
        }
//msgDebugWrite();
        readfile($fn);
        exit();
    }

    /**
     * Builds the jQuery EasyUI extensions into a single loaded script
     */
    public function easyuiJS()
    {
        $basePath = BIZUNO_REPO.'/scripts/jquery-easyui-ext';
        $output  = '';
        $output .= file_get_contents("$basePath/portal/jquery.portal.js")           ."\n"; // Portal
        $output .= file_get_contents("$basePath/color/jquery.color.js")             ."\n"; // Color
        $output .= file_get_contents("$basePath/edatagrid/jquery.edatagrid.js")     ."\n"; // Editable DataGrid
        $output .= file_get_contents("$basePath/datagrid-filter/datagrid-filter.js")."\n"; // Datagrid Filter
        $output .= file_get_contents("$basePath/datagrid-dnd/datagrid-dnd.js")      ."\n"; // Datagrid Drag-n-Drop Rows
        $output .= file_get_contents("$basePath/texteditor/jquery.texteditor.js")   ."\n"; // Text Editor
        header("Content-type: text/javascript; charset: UTF-8");
        header("Content-Length: ".strlen($output));
        echo $output;
        exit();
    }

    /**
     * Builds the jQuery EasyUI extensions into a single loaded file, remaps icons to proper path on www.bizuno.com
     */
    public function easyuiCSS()
    {
        $basePath = BIZUNO_REPO.'scripts/jquery-easyui-ext';
        $icons   = [];
        $output  = '';
//      $output .= file_get_contents("$basePath/portal/jquery.portal.css")           ."\n"; // No .css file for Portal
//      $output .= file_get_contents("$basePath/color/jquery.color.css")             ."\n"; // No .css file for Color
//      $output .= file_get_contents("$basePath/edatagrid/jquery.edatagrid.css")     ."\n"; // No .css file for Editable DataGrid
//      $output .= file_get_contents("$basePath/datagrid-filter/datagrid-filter.css")."\n"; // No .css file for Datagrid Filter
//      $icons[] = ".icon-justifyright { background: url('$basePath/datagrid-filter/filter.png') center center;}\n"; // already included in core css file
//      $output .= file_get_contents("$basePath/datagrid-dnd/datagrid-dnd.css")      ."\n"; // No .css file for Datagrid Drag-n-Drop Rows
        $output .= file_get_contents("$basePath/texteditor/texteditor.css")   ."\n"; // Text Editor
        $this->mapImagePath($icons, $basePath, 'texteditor'); // map icons for this extension
        $output .= "\n".implode("\n", $icons);
        msgDebugWrite();
        header("Content-type: text/css; charset: UTF-8");
        header("Content-Length: ".strlen($output));
        echo $output;
        exit();
    }

    private function mapImagePath(&$icons, $basePath, $extName)
    {
        $imgPath = "$basePath/$extName/images";
        if (!is_dir($imgPath)) { return; }
        $urlPath = BIZUNO_SCRIPTS."jquery-easyui-ext/$extName";
        $files = scandir($imgPath);
        foreach ($files as $file) {
            if ($file == "." || $file == "..") { continue; }
            $name   = substr($file, 0, strpos($file, '.'));
            $icons[]= ".icon-$name { background: url('$urlPath/images/$file') center center; }\n";
        }
    }

    /**
     * Signs a user out of Bizuno, destroys the cookie
     */
    public function logout(&$layout=[])
    {
        bizClrCookie('bizunoSession');
        $layout = array_replace_recursive($layout, ['type'=>'page', 'jsHead'=>['redir'=>"window.location='".BIZUNO_SRVR."';"]]);
    }

    public function orderAdd(&$layout=[])
    {
        global $portal;
        if (!$portal->portalUserAuth()) { return msgAdd(lang('invalid_access')); } // validate user
        loadBusinessCache();
        compose('api', 'order', 'add', $layout);
    }

    public function bizAPI(&$layout=[])
    {
        $route = isset($_GET['apiRt']) ? preg_replace("/[^a-zA-Z0-9\/]/", '', $_GET['apiRt']) : '';
        if (empty($route) || substr_count($route, '/') != 2) { exit('Illegal Access!'); }
        $apiPath = explode('/', $route, 3);
        loadBusinessCache();
        msgDebug("\nUser has been set, ready to compose with route = ".print_r($route, true));
        compose($apiPath[0], $apiPath[1], $apiPath[2], $layout);
    }
}
