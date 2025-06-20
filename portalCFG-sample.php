<?php
/*
 * Bizuno Public - Portal configuration file
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
 * @filesource /portalCFG.php
 */

namespace bizuno;

define('SCRIPT_START_TIME', microtime(true));

// Business Identifier, Random Alpha-Numeric value, cannot be 0
define('BIZUNO_BIZID', '0123456789');

// Path to location where your documents and media are stored, should point to a private folder unreachable via browser
define('BIZUNO_DATA', $_SERVER['PHP_DOCUMENT_ROOT'].'/private/MyData/');

// Randomly generated key used to encrypt session cookie, 16 alpha-num characters, randomly generated
define('BIZUNO_KEY', '0123456789012345');

// Database credentials
define('BIZUNO_DB_PREFIX', '');
define('BIZPORTAL', [
    'type'=>'mysql',
    'host'=>'localhost',
    'name'=>'DbName',
    'user'=>'DbUser',
    'pass'=>'DbPassword',
    'prefix'=>BIZUNO_DB_PREFIX,
]);

// URL paths
define('BIZUNO_PORTAL',  $_SERVER['SERVER_NAME']);
define('BIZUNO_SRVR',   'https://'.BIZUNO_PORTAL.'/');
define('BIZUNO_SCRIPTS', BIZUNO_SRVR.'assets/'); // pulled from a shared server

// File system paths
define('BIZUNO_PATH',    $_SERVER['PHP_DOCUMENT_ROOT'].'/web/');
define('BIZUNO_REPO',    BIZUNO_PATH); // Path to repository where Bizuno library is located
define('BIZUNO_ASSETS',  BIZUNO_PATH.'/vendor/');

// Initialize Bizuno Constants
require(BIZUNO_REPO.'bizunoCFG.php');
