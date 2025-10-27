<?php
/*
 * Portal Controller
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
 * @version    7.x Last Update: 2025-10-26
 * @filesource /lib/controller.php
 */

namespace bizuno;

require(BIZBOOKS_ROOT.'model/functions.php'); // Core Bizuno functions
require(BIZUNO_ASSETS.'autoload.php'); // Load the libraries
bizAutoLoad('lib/model.php','getUserCookie', 'function'); // portal specific
bizAutoLoad('lib/api.php',  'portalApi');
bizAutoLoad('lib/view.php', 'portalView');
bizAutoLoad(BIZBOOKS_ROOT.'model/msg.php',     'messageStack');
bizAutoLoad(BIZBOOKS_ROOT.'model/io.php',      'io');
bizAutoLoad(BIZBOOKS_ROOT.'model/db.php',      'db');
bizAutoLoad(BIZBOOKS_ROOT.'model/mail.php',    'bizunoMailer');
bizAutoLoad(BIZBOOKS_ROOT.'model/manager.php', 'mgrJournal');
bizAutoLoad(BIZBOOKS_ROOT.'locale/cleaner.php','cleaner');

class portalCtl
{
    public  $layout       = []; // Holds the structure for the output display
    private $bizTimer     = 60 * 60 * 8; // reload business cache every 8 hours
    private $userValidated= false;
    private $needsInstall = false;
    private $needsMigrate = false;
    private $creds;
    private $route;
 
    function __construct()
    {
        global $msgStack, $io, $cleaner, $portal;
        $msgStack= new messageStack();
        $io      = new io();
        $cleaner = new cleaner();
        $portal  = new portal();

// Uncomment these lines to force a user logout on page refresh.
//bizSetCookie('bizunoUser',    '', 0);
//bizSetCookie('bizunoSession', '', 0);

        $this->creds = getUserCookie();
        $this->userValidated = !empty($this->creds) ? true : false; // validate user
        $this->route = $this->cleanBizRt();
        $this->setDOM(); // load the applicable GUI
        $scope   = $this->getScope(); // get scope
        switch ($scope) { // act accordingly
            case 'api':    $this->goAPI();     break; // API request, may be logged in or guest
            case 'auth':   $this->goAuth();    break; // Normal operation for authorized users
            default:
            case 'guest':  $this->goGuest();   break; // Shows login screen, handles API requests and other things when user is not logged in 
            case 'install':$this->goInstall(); break; // Shows install screen, after verifying credentials
            case 'migrate':$this->goMigrate(); break; // Shows migrate screen after verifying credentials
        }
        new view($this->layout);
    }
    private function setDOM()
    {
        global $html5;
        $modsEasy= ['administrate','api','bizuno','common','contacts','inventory','payment','phreebooks','phreeform','quality','shipping'];
        bizAutoLoad(BIZBOOKS_ROOT.'view/main.php', 'view');
        $ui = !in_array($this->route['module'], $modsEasy) ? 'jQueryUI' : 'easyUI';
        bizAutoLoad(BIZBOOKS_ROOT."view/$ui/html5.php", 'html5');
        $html5 = new html5();
    }

    private function getScope()
    {
        global $db;
        msgDebug("\nEntering getScope");
        if (!defined('BIZPORTAL')) { msgDebug("\nBIZPORTAL not defined, returning guest"); return 'guest'; } // Path to db not defined, needs install and creds set
        $creds= defined('BIZPORTAL') ? BIZPORTAL : [];
        $db   = new db($creds);
        if (!$db->connected) { msgDebug("\nDB not connected, returning guest"); return 'guest'; }
        if ('portal'==$this->route['module'] && 'api'==$this->route['page'])         { msgDebug("\nAPI Request, returning api");         return 'api'; }
        if ( $this->userValidated &&  dbTableExists(BIZUNO_DB_PREFIX.'address_book')) { msgDebug("\nNeed to migrate, returning migrate"); return 'migrate'; }
        if ( $this->userValidated &&  dbTableExists(BIZUNO_DB_PREFIX.'common_meta'))  { msgDebug("\nNormal operation, returning auth");   return 'auth'; }
        if (!$this->userValidated && !dbTableExists(BIZUNO_DB_PREFIX.'configuration')){ msgDebug("\nNeed to install, returning install"); return 'install'; }
        msgDebug("\nFall through, returning guest.");
        return 'guest';
    }
    /**
     * Handles requests when user has not been authenticated
     * @param type $layout
     */
    private function goGuest()
    {
        $portal = new portalView();
        $portal->login($this->layout);
    }
    /**
     * Handles requests when user has not been authenticated
     * @param type $layout
     */
    private function goAPI()
    {
        $portal = new portalApi();
        $method = $this->route['method'];
        if (method_exists($portal, $method)) {
            msgDebug("\nProcessing API request {$method}");
            $portal->$method($this->layout);
            return;
        }
        msgDebug("\nAPI request {$method} WAS NOT FOUND!");
        $guest = new portalView(); // Fall through to login screen
        $guest->login($this->layout);
    }
    private function goInstall()
    {
        $portal = new portalView();
        $portal->install($this->layout);
    }
    private function goMigrate()
    {
        $portal = new portalView();
        $portal->migrate($this->layout);
    }
    private function goAuth()
    {
        $this->getCodex();
        compose($this->route['module'], $this->route['page'], $this->route['method'], $this->layout);
    }
    private function getCodex()
    {
        global $mixer, $portal, $bizunoUser;
        bizAutoLoad(BIZBOOKS_ROOT.'locale/currency.php','currency');
        bizAutoLoad(BIZBOOKS_ROOT.'model/encrypter.php','encryption');
        $this->loadLanguage(); // Just load the minimal language for the portal operation, more can be loaded as needed
        $mixer   = new encryption();
        $portal  = new portal();
        $bizunoUser= $this->setGuestCache();
        $this->validateCookie(); // Validates sign in status
        $this->initUserCache();
        $this->initBusinessCache();
        $this->cacheValidate();
        $this->validateVersion();
    }

    public function setGuestCache()
    {
        msgDebug("\nEntering setGuestCache");
        return [
            'profile'   => ['userID'=>0, 'email'=>'', 'language'=>'en_US'],
            'business'  => ['bizID' =>defined('BIZUNO_BIZID') ? BIZUNO_BIZID : 0],
            'dashboards'=> []];
    }

    private function validateCookie()
    {
        msgDebug("\nEntering validateCookie.");
        // typical case, cookie not expired, now have user, email and role
        if (is_array($this->creds) && sizeof($this->creds)==5 && $this->creds[4]==$_SERVER['REMOTE_ADDR']) {
            setUserCache('profile', 'userID',  $this->creds[0]);
            setUserCache('profile', 'psID',    $this->creds[1]);
            setUserCache('profile', 'email',   $this->creds[2]);
            setUserCache('profile', 'userRole',$this->creds[3]);
            $this->userValidated = true;
        }
        setlocale(LC_COLLATE,getUserCache('profile', 'language'));
        setlocale(LC_CTYPE,  getUserCache('profile', 'language'));
        msgDebug("\nLeaving validateUser with user validated = ".($this->userValidated?'true':'false'));
    }

    private function initUserCache()
    {
        if (empty($this->userValidated)) { return; }
        $roleID = getUserCache('profile', 'userRole');
        msgDebug("\nEntering initUserCache with roleID = $roleID");
        $profile = array_replace(getUserCache('profile'), getMetaContact(getUserCache('profile', 'userID'), 'user_profile'));
        setUserCache('profile', '', $profile);
        $role = dbMetaGet($roleID, 'bizuno_role');
        setUserCache('role', '', $role);
        msgDebug("\nLeaving initUserCache with administrate = ".getUserCache('role', 'administrate'));
    }

    private function initBusinessCache()
    {
        global $currencies;
        msgDebug("\nEntering initBusinessCache");
        if ($this->needsInstall) { $this->cacheReload('guest'); }
        else { // normal operation
            msgDebug("\nBizuno is installed, loading cache");
            loadBusinessCache();
            if (biz_date('Y-m-d') > getModuleCache('phreebooks', 'fy', 'period_end')) { periodAutoUpdate(false); }
            date_default_timezone_set(getModuleCache('bizuno', 'settings', 'locale', 'timezone'));
        }
        if ($this->needsMigrate) { $this->cacheReload('migrate'); } // limit the dashboard list to just the portal
        $currencies = new currency(); // Needs PhreeBooks cache loaded to properly initialize otherwise defaults to USD
    }

    private function validateVersion()
    {
        $dbVer = getModuleCache('bizuno', 'properties', 'version');
        msgDebug("\nValidating installed Bizuno version ".MODULE_BIZUNO_VERSION." to db version: $dbVer");
        if (empty(getUserCache('business', 'bizID')) || empty(getUserCache('profile', 'email'))) { $this->cacheReload('guest'); return; } // not logged in
        if (version_compare($dbVer, MODULE_BIZUNO_VERSION) < 0) {
            msgDebug("\nDB is downlevel, upgrading!");
            bizAutoLoad(BIZBOOKS_ROOT.'controllers/bizuno/install/upgrade.php');
            bizunoUpgrade();
        }
    }

    private function cacheValidate()
    {
        if (empty($this->userValidated)) { return; }
        $cacheExp = bizCacheExpGet();
        msgDebug("\nCache expiration time = $cacheExp and time = ".time());
        if ($cacheExp < time()) { // cache expired
            msgDebug("\n  Cache expired! reloading...");
            $this->cacheReload();
        } else { msgDebug("\n  Cache is valid! NOT reloading..."); }
    }

    private function cacheReload($mode='user')
    {
        msgDebug("\nEntering reloadCache");
        bizAutoLoad(BIZBOOKS_ROOT.'model/registry.php', 'bizRegistry');
        $registry = new bizRegistry();
        $registry->initRegistry(getUserCache('profile', 'email'), getUserCache('business', 'bizID'));
        bizCacheExpSet(time() + $this->bizTimer);
    }

    private function loadLanguage($lang='en_US')
    {
        global $bizunoLang;
        $getLang = clean('bizunoLang', ['format'=>'cmd', 'default'=>'en_US'], 'get');
        if ($getLang<>'en_US') { $lang = $getLang; }
        $bizunoLang = loadBaseLang($lang);
    }

    private function cleanBizRt()
    {
        $value = isset($_GET['bizRt']) ? preg_replace("/[^a-zA-Z0-9\/]/", '', $_GET['bizRt']) : '';
        if (substr_count($value, '/') != 2) { // check for valid structure, else home
            msgDebug("\nNo path sent, overriding with userValidated = ".$this->userValidated);
            $_GET['menuID'] = 'home';
            $value = 'bizuno/main/bizunoHome';
        }
        $temp = explode('/', $value, 3);
        if (!$this->userValidated && !in_array($temp[1], ['api'])) { // not logged in or not installed, restrict to portal api class
            msgDebug("\nNot logged in or not installed, restrict to parts of module bizuno");
            $temp = ['bizuno', 'main', 'bizunoHome'];
        }
        $GLOBALS['bizunoModule'] = $temp[0];
        $GLOBALS['bizunoPage']   = $temp[1];
        $GLOBALS['bizunoMethod'] = preg_replace("/[^a-zA-Z0-9\_\-]/", '', $temp[2]); // remove illegal characters
        return ['module'=>$GLOBALS['bizunoModule'] , 'page'=>$GLOBALS['bizunoPage'], 'method'=>$GLOBALS['bizunoMethod'] ];
    }
}
