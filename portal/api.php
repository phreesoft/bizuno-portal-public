<?php
/*
 * Bizuno Public - Portal API
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
 * @version    7.x Last Update: 2025-06-20
 * @filesource /portal/api.php
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
        $fBad = $eBad = $pBad = $fn = false;
        $parts= explode('/', clean('src', 'path_rel', 'get'), 2);
        if (defined('BIZUNO_DATA') && !empty(BIZUNO_DATA)) {
            if (!empty($parts[1])) {
                $io   = new io(); // needs BIZUNO_DATA
                $fn   = (empty($parts[0]) ? BIZUNO_REPO : BIZUNO_DATA).$parts[1];
                $ext  = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                $fBad = !file_exists($fn) ? true : false;
                $pBad = !$io->validatePath($parts[1]) ? true : false;
                $validExts = array_merge($io->getValidExt('image'), $io->getValidExt('script'));
                $eBad = !in_array($ext, $validExts) ? true : false;
            } else { $fBad = true; }
        } else { $fBad = true; }

        if ($eBad || $pBad || $fBad) { $fn = BIZUNO_LOGO; }
        // Send out the image
        header("Accept-Ranges: bytes");
        header("Content-Type: ".$this->getMimeType($fn));
        if ($fn<>BIZUNO_LOGO) {
            header("Content-Length: ".filesize($fn));
            header("Last-Modified: " .date(DATE_RFC2822, filemtime($fn)));
        }
        readfile($fn);
        exit();
    }

    /**
     * Executes an EDI cron to poll ALL EDI sources for new orders.
     * @param array $layout
     * 
     * command: https://biz.mydomain.com?bizRt=ispPortal/api/ediCron
     */
    public function ediCron(&$layout=[])
    {
        return msgAdd('EDI service not available on self-hosted servers!');
    }
    
    private function getMimeType($filename)
    {
        $ext = strtolower(substr($filename, strrpos($filename, '.')+1));
        switch ($ext) {
            case "aiff":
            case "aif":  return "audio/aiff";
            case "avi":  return "video/msvideo";
            case "bmp":
            case "gif":
            case "png":
            case "tiff": return "image/$ext";
            case "css":  return "text/css";
            case "csv":  return "text/csv";
            case "doc":
            case "dot":  return "application/msword";
            case "docx": return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
            case "dotx": return "application/vnd.openxmlformats-officedocument.wordprocessingml.template";
            case "docm": return "application/vnd.ms-word.document.macroEnabled.12";
            case "dotm": return "application/vnd.ms-word.template.macroEnabled.12";
            case "gz":
            case "gzip": return "application/x-gzip";
            case "html":
            case "htm":
            case "php":  return "text/html";
            case "jpg":
            case "jpeg":
            case "jpe":  return "image/jpg";
            case "js":   return "text/javascript";
            case "json": return "application/json";
            case "mp3":  return "audio/mpeg3";
            case "mov":  return "video/quicktime";
            case "mpeg":
            case "mpe":
            case "mpg":  return "video/mpeg";
            case "pdf":  return "application/pdf";
            case "pps":
            case "pot":
            case "ppa":
            case "ppt":  return "application/vnd.ms-powerpoint";
            case "pptx": return "application/vnd.openxmlformats-officedocument.presentationml.presentation";
            case "potx": return "application/vnd.openxmlformats-officedocument.presentationml.template";
            case "ppsx": return "application/vnd.openxmlformats-officedocument.presentationml.slideshow";
            case "ppam": return "application/vnd.ms-powerpoint.addin.macroEnabled.12";
            case "pptm": return "application/vnd.ms-powerpoint.presentation.macroEnabled.12";
            case "potm": return "application/vnd.ms-powerpoint.template.macroEnabled.12";
            case "ppsm": return "application/vnd.ms-powerpoint.slideshow.macroEnabled.12";
            case "rtf":  return "application/rtf";
            case "svg":  return "image/svg+xml";
            case "swf":  return "application/x-shockwave-flash";
            case "txt":  return "text/plain";
            case "tar":  return "application/x-tar";
            case "wav":  return "audio/wav";
            case "wmv":  return "video/x-ms-wmv";
            case "xla":
            case "xlc":
            case "xld":
            case "xll":
            case "xlm":
            case "xls":
            case "xlt":
            case "xlt":
            case "xlw":  return "application/vnd.ms-excel";
            case "xlsx": return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
            case "xltx": return "application/vnd.openxmlformats-officedocument.spreadsheetml.template";
            case "xlsm": return "application/vnd.ms-excel.sheet.macroEnabled.12";
            case "xltm": return "application/vnd.ms-excel.template.macroEnabled.12";
            case "xlam": return "application/vnd.ms-excel.addin.macroEnabled.12";
            case "xlsb": return "application/vnd.ms-excel.sheet.binary.macroEnabled.12";
            case "xml":  return "application/xml";
            case "zip":  return "application/zip";
            default:
                if (function_exists(__NAMESPACE__.'\mime_content_type')) { # if mime_content_type exists use it.
                    $m = mime_content_type($filename);
                } else {    # if nothing left try shell
                    if (strstr($_SERVER['HTTP_USER_AGENT'], 'Windows')) { # Nothing to do on windows
                        return ""; # Blank mime display most files correctly especially images.
                    }
                    if (strstr($_SERVER['HTTP_USER_AGENT'], 'Macintosh')) { $m = trim(exec('file -b --mime '.escapeshellarg($filename))); }
                    else { $m = trim(exec('file -bi '.escapeshellarg($filename))); }
                }
                $m = explode(";", $m);
                return trim($m[0]);
        }
    }
}