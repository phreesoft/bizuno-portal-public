<?php
/*
 * Bizuno Public - Portal view
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
 * @filesource /portal/view.php
 */

namespace bizuno;

class portalView
{
    private $errors = '';
    public  $lang;

    function __construct()
    {
        $iso = !empty($_COOKIE['bizunoEnv']['lang']) ? $_COOKIE['bizunoEnv']['lang'] : 'en_US';
        $lang = [];
        include("lib/locale/$iso.php"); // replace $lang
        $this->lang = $lang;
    }

    /******************************* Guest Methods *************************************/
    public function login(&$layout=[])
    {
        msgDebug("\nEntering guest/login.");
        // if POST vars are set then try to log in else show form
        if (isset($_POST['bizUser']) && isset($_POST['bizPass'])) {
            msgDebug("\nCredentials sent, trying to validate.");
            if ($this->validateUser($layout)) { // if validated, return to load home page
                $layout = ['type'=>'guest','jsReady'=>['reload'=>"location.reload();"]];
                return;
            }
        }
        // Show login form
        $logo = ['label'=>getModuleCache('bizuno','properties','title'),'attr'=>['type'=>'img','src'=>BIZBOOKS_URL_FS.'0/view/images/bizuno.png','height'=>48]];
        $html = '<div>'.html5('', $logo).'</div>
    <div class="text">'.$this->lang['welcome'].'</div>
    <div class="field"><input type="text" name="bizUser" placeholder="'.$this->lang['email'].'"> </div>
    <div class="field"><input type="password" name="bizPass" placeholder="'.$this->lang['password'].'"></div>
    <div class="field"><select name="bizLang"><option value="en_US">English (US)</option></select></div>
    <button>'.$this->lang['signin'].'</button>'; // removed icons <div class="fas fa-envelope"></div> AND <div class="fas fa-lock"></div>
        if (!empty($this->errors)) { $html .= '<div class="error">'.$this->errors.'</div>'; }
        $layout = ['type'=>'guest',
            'divs'  => ['body' =>['order'=>50,'type'=>'divs','classes'=>['login-form'],'divs'=>[
                'formBOF' => ['order'=>20,'type'=>'form',  'key' =>'frmLogin'],
                'body'    => ['order'=>51,'type'=>'html',  'html'=>$html],
                'formEOF' => ['order'=>90,'type'=>'html',  'html'=>"</form>"]]]],
            'forms' => ['frmLogin'=>['attr'=>['type'=>'form','method'=>'post']]]];
    }

    /**
     * Validates credentials for a user to log in. ONLY EXECUTED AT THE PORTAL!
     * @global type $portal
     * @param type $layout
     * @return type
     */
    public function validateUser(&$layout=[])
    {
        global $portal;
        msgDebug("\nEntering validateUser.");
        if ($_SERVER['SERVER_NAME']<>BIZUNO_PORTAL) { return $this->lang['err_illegal_access']; }
        $userID = preg_replace("/[^a-zA-Z0-9\-\_\.\@]/", '', $_POST['bizUser']); // email address
        $userPW = trim(stripslashes($_POST['bizPass']));
        if (!empty($userID) && !empty($userPW)) {
            $portal->useOauth = false;
            $portal->restHeaders = ['email'=>$userID, 'pass'=>$userPW, 'bizID'=>BIZUNO_BIZID];
            $resp = $portal->restRequest('get', PHREESOFT_URL, 'biz_hosted/get_rights');
            if (empty($resp['userID'])) {
                $this->errors = $this->lang['err_invalid_creds'];
                return;
            }
            $user = ['userID'=>0, 'psID'=>$resp['userID'], 'userEmail'=>$resp['userEmail'], 'userRole'=>$resp['userRole'], 'userName'=>$resp['userName']];
            setUserCookie($user);
            setModuleCache('common', 'client', '', $resp);
            $data = ['content'=>['action'=>'eval','actionData'=>"loadSessionStorage(); window.location='https://".BIZUNO_PORTAL."';"]];
            $layout = array_replace_recursive($layout, $data);
            return true;
        }
    }
    
    /******************************* Install Methods *************************************/
    public function install(&$layout=[])
    {
        msgDebug("\nEntering controllers/install");
        $title = defined('BIZUNO_BIZTITLE') ? BIZUNO_BIZTITLE : $this->lang['my_business'];
        if (isset($_POST['biz_title']) && isset($_POST['biz_chart'])) { // check for post to start install
            msgDebug("\nEntering controllers/installBizuno");
            if ($_SERVER['SERVER_NAME']<>BIZUNO_PORTAL) { return msgAdd("Illegal login access!"); }
            $creds = getUserCookie();
            setUserCache('profile', 'psID',  $creds[1]); // PhreeSoft user ID
            setUserCache('profile', 'email', $creds[2]); // User email
            bizAutoLoad(BIZBOOKS_ROOT.'controllers/bizuno/install/install.php', 'bizInstall');
            $installer = new bizInstall();
            $installer->installBizuno($layout);
            return;
        }
        // Show install form
        $logo = ['label'=>getModuleCache('bizuno','properties','title'),'attr'=>['type'=>'img','src'=>BIZBOOKS_URL_FS.'0/view/images/bizuno.png','height'=>48]];
        $html = '<div>'.html5('', $logo).'</div>
    <div class="info">'.$this->lang['install_intro'].'</div><br />
    <div class="info">'.$this->lang['biz_title'].'</div><div class="field"><input type="text" name="biz_title" value="'.$title.'"> </div>
    <div class="info">'.$this->lang['currency'].'</div><div class="field">'.$this->getSelCur().'</div>
    <div class="info">'.$this->lang['fiscal_year'].'</div><div class="field">'.$this->getSelFY().'</div>
    <div class="info">'.$this->lang['chart_of_accounts'].'</div><div class="field">'.$this->getSelChart().'</div>
    <div class="info">'.$this->lang['time_zone'].'</div><div class="field">'.$this->getSelZone().'</div>
    <button>'.$this->lang['install'].'</button>';
        if (!empty($this->errors)) { $html .= '<div class="error">'.$this->errors.'</div>'; }
        msgDebug("\nStarting to generate layout");
        $layout = ['type'=>'guest',
            'divs'  => ['body' =>['order'=>50,'type'=>'divs','classes'=>['login-form'],'divs'=>[
                'formBOF'=> ['order'=>20,'type'=>'form','key' =>'frmInstall'],
                'body'   => ['order'=>51,'type'=>'html','html'=>$html],
                'formEOF'=> ['order'=>90,'type'=>'html','html'=>"</form>"]]]],
            'forms' => ['frmInstall'=>['attr'=>['type'=>'form','method'=>'post']]]];
        msgDebug("\nReturning layout ".print_r($layout, true));
    }

    private function getSelCur()
    {
        msgDebug("\nEntering getSelCur");
        $html = '<select name="biz_currency">';
        $opts = viewCurrencySel($this->locale);
        foreach ($opts as $opt) { $html .= '<option value="'.$opt['id'].'"'.($opt['id']==$this->defCur?' selected':'').'>'.$opt['text'].'</option>'; }
        $html.= '</select>';
        msgDebug(" ... and returning $html");
        return $html;
    }
    private function getSelFY()
    {
        msgDebug("\nEntering getSelFY");
        $year = biz_date('Y');
        for ($i=2; $i>=0; $i--) { $years[] = ['id'=>$year - $i, 'text'=>$year - $i]; }
        $html = '<select name="biz_fy">';
        foreach ($years as $opt) { $html .= '<option value="'.$opt['id'].'"'.($opt['id']==$year?' selected':'').'>'.$opt['text'].'</option>'; }
        $html.= '</select>';
        msgDebug(" ... and returning $html");
        return $html;
    }
    private function getSelChart()
    {
        msgDebug("\nEntering getSelChart");
        $html = '<select name="biz_chart">';
        $opts = localeLoadCharts();
        foreach ($opts as $opt) { $html .= '<option value="'.$opt['id'].'"'.($opt['id']==$this->defChart?' selected':'').'>'.$opt['text'].'</option>'; }
        $html.= '</select>';
        msgDebug(" ... and returning $html");
        return $html;
    }
    private function getSelZone()
    {
        msgDebug("\nEntering getSelZone");
        $html = '<select name="biz_timezone">';
        $opts = viewTimeZoneSel($this->locale);
        foreach ($opts as $opt) { $html .= '<option value="'.$opt['id'].'"'.($opt['id']==$this->guessTimeZone($this->locale)?' selected':'').'>'.$opt['text'].'</option>'; }
        $html.= '</select>';
        msgDebug(" ... and returning $html");
        return $html;
    }
    /**
     * try to guess time zone by client ip
     * @return string
     */
    private function guessTimeZone($locale=[])
    {
        // @TODO - THIS CAN BE VERY SLOW!!!, fo rnow return Eastern time zone. Need to find a faster way
return 'America/New_York'; 
        if (empty($locale)) { $locale= localeLoadDB(); }
        $ipInfo= file_get_contents('http://ip-api.com/json/'.$_SERVER['REMOTE_ADDR']);
        $data  = json_decode($ipInfo);
        $output= 'America/New_York';
        if (empty($data->timezone)) { return $output; }
        foreach ($locale->Timezone as $value) {
            if ($data->timezone == $value->Code) { $output = $value->Code;  break; }
        }
        return $output;
    }
    /**
     * For new installations, tests the user submitted credentials to make a db connection
     * @global \bizuno\db $db
     * @param type $creds
     * @return boolean
     */
    private function installTestDB()
    {
        global $db;
        if (!defined('BIZPORTAL')) { $this->errors .= 'DB credentials have not been defined, install cannot continue!'; return ;}
        $myDB = BIZPORTAL;
        $myDB['name'] = $bizCreds['bizDB'];
        msgDebug("\nConnecting to db with creds = ".print_r($myDB, true));
        $db = new db($myDB);
        if (!$db->connected) { $this->errors .= 'Bizuno cannot connect to your DB, please check your credentials!'; return; }
        return true;
    }
    
    /******************************* Migrate Methods *************************************/
    public function render()
    {
        $data = [
            'divs'  => [
                'body' => ['order'=>50,'type'=>'divs','attr'=>['id'=>'divUpgrade'],'divs'=>[
                    'intro'=> ['order'=>10,'type'=>'html',  'html'=>"<p>".$this->lang['intro']."</p>"],
                    'body' => ['order'=>50,'type'=>'fields','keys'=>['btnGo']]]]],
            'fields'=> [
                'btnGo'=> ['order'=>20, 'attr'=>['type'=>'button','value'=>lang('upgrade')],'styles'=>['cursor'=>'pointer'],
                    'events' => ['onClick'=> "jsonAction('$this->moduleID/admin/migrateBizuno');"]]]];
        return ['data'=>$data];
    }

    /**
     * Upgrade management method
     * @param array $layout - Structure coming in
     * @return modified $layout
     */
    public function migrateBizuno(&$layout=[])
    {
        $dbVer = getModuleCache('bizuno', 'properties', 'version');
        if (version_compare($dbVer, '7.0') >= 0) { return; } // already there
        msgDebug("\nEntering migrateBizuno with dbVersion = $dbVer and MODULE_BIZUNO_VERSION = ".MODULE_BIZUNO_VERSION);
        bizAutoLoad(BIZBOOKS_ROOT.'controllers/bizuno/install/migrate-7.0.php');
        $charts = getModuleCache('phreebooks', 'chart');
        if (empty($charts)) { // if the COA is not present, bail on migrate since pre 7.0 it only survived in the cache
            return msgAdd('The chart of accounts is missing! Bailing');
        }
        $cron = migrateBizunoPrep();
        msgDebug("\nInitializing cron Bizuno migrate with cron = ".print_r($cron, true));
        setModuleCache('bizuno', 'cron', 'migrateBizuno', $cron);
        $layout = array_replace_recursive($layout,['content'=>['action'=>'eval', 'actionData'=>"cronInit('migrateBizuno','$this->moduleID/admin/migrateBizunoNext');"]]);
    }

    /**
     * Next block in the special action cron
     * @global type $io
     */
    public function migrateBizunoNext(&$layout=[])
    {
        msgDebug("\nEntering migrateBizunoNext.");
        bizAutoLoad(BIZBOOKS_ROOT.'controllers/bizuno/install/migrate-7.0.php');
        $cron = getModuleCache('bizuno', 'cron', 'migrateBizuno');
        migrateBizuno($cron);
        msgDebug("\nBack from migrateBizuno with cron = ".print_r($cron, true));
        $ttlRecords = number_format($cron['ttlRecord']);
        $ttlSteps  = $cron['ttlSteps']+1; // because we go past it to stop
        $msg = "Completed Step: {$cron['curStep']} of $ttlSteps<br />Block {$cron['curBlk']} of {$cron['ttlBlk']}<br />Total of $ttlRecords records.<br />";
        if ($cron['curStep']>$cron['ttlSteps']) { // wrap up this iteration
            $msg .= "<p>Database table migrate completed! Press OK to go to your business.</p>";
            $msg .= html5('btnGo', ['events'=>['onClick'=>"window.location='https://".BIZUNO_PORTAL."';"], 'attr'=>['type'=>'button','value'=>lang('finish')], 'styles'=>['cursor'=>'pointer']]);
            msgLog($msg);
            $data = ['content'=>['percent'=>100,'msg'=>$msg,'baseID'=>'migrateBizuno','urlID'=>"$this->moduleID/admin/migrateBizunoNext"]];
            setModuleCache('bizuno', 'properties', 'version', '7.0');
            bizClrCookie('bizunoSession'); // forces a logout
            bizCacheExpClear();
            clearModuleCache('bizuno', 'cron', 'migrateBizuno');
        } else { // return to update progress bar and start next step
            $blkPrcnt= floor(100*($cron['curBlk'])/$cron['ttlBlk']);
            $data = ['content'=>['percent'=>$blkPrcnt,'msg'=>$msg,'baseID'=>'migrateBizuno','urlID'=>"$this->moduleID/admin/migrateBizunoNext"]];
            setModuleCache('bizuno', 'cron', 'migrateBizuno', $cron);
        }
        $layout = array_replace_recursive($layout, $data);
    }
}