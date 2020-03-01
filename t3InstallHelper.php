<?PHP

/** 
* Class t3InstallHelper
* 
* @date      23.02.2020
* @copyright MIT License 
* @author    Daniel Rueegg Winterthur CH
* 
* Built to set up Typo3 installations on hosted servers without ssh-access.
*
**/

class t3InstallHelper {

    /**
    * Property strVersion
    *
    * @var string
    */
    Public $strVersion = '2.17';

    /**
    * Property strDocupassword
    *  essential for successfull login to this tool!
    *  
    *  create a new password: 
    *  use first the command 'sha1 Passwort' to create a new hash.
    *  Copy it and and paste the created hash here.
    *
    * @var string
    */
    Private $strDocupassword = 'b858cb282617fb0956d960215c8e84d1ccf909c6';// blank (1 char)

    /**
    * Property strSecretPreauthKey
    *  essential for successfull preauthLogin!
    *
    * @var string
    */
    Private $strSecretPreauthKey = 'theSecretKeyFromMBA-ITforIntranetSekII';

    /**
    * Property strAuthSeparer
    *  essential for successfull preauthLogin!
    *  empty or a single char Like - _ / | , ;
    *
    * @var string
    */
    Private $strAuthSeparer = '-';

    /**
    * Property aIngredients
    *  the field-order is a secret and essential for successfull preauthLogin!
    *
    * @var array
    */
    Private $aIngredients = [
                'school'    => 'shortSchoolname',
                'role'      => 'student',
                'timestamp' => 0,
                'account'   => 'vorname.nachname',
                'preauth'   => 'getGenerated',
            ];

    /**
    * Property Felder
    *
    * @var array
    */
    Private $Felder = [
        'aktion'    => [
                        'typ'          => 'select' ,   
                        'lab'          => 'Aktion' ,            
                        'listen'       => 'aktList' , 
                        'standardwert' => 'l'
                        ],
        'subdomains'=> [
                        'typ'          => 'text' ,   
                        'lab'          => 'Dom&auml;nenliste',  
                        'tiptext'      => ' URLs mit Preauth.' ,   
                        'standardwert' => 'https://subdomain.mydomain.ch'
                        ],
        'linkdatei' => [
                        'typ'          => 'text' ,     
                        'lab'          => 'Linkdatei' ,         
                        'tiptext'      => '1. ../t3Sources/typo3_src-9.5.5 |
                                          2. typo3_src/typo3 | 3. typo3_src/index.php' , 
                        'standardwert' => '../t3Sources/typo3_src-9.5.5'
                        ],
        'symlink'   => [
                        'typ'          => 'text' , 
                        'lab'          => 'Symlink' ,           
                        'tiptext'      => '1. typo3_src | 2. typo3 | 3. index.php' ,  
                        'standardwert' => 'typo3_src'           
                        ],
        'original'  => [
                        'typ'    => 'select' ,   
                        'lab'    => 'Original-Datei' ,    
                        'listen' => 'fileList'
                        ],
        'subpfad'   => [
                        'typ'    => 'select' , 
                        'lab'    => 'zu Verzeichnis' ,    
                        'listen' => 'subDirList'
                        ],
        'username'  => [
                        'typ'          => 'text' ,     
                        'lab'          => 'Benutzername',       
                        'tiptext'      => ' eigener Vor- und Nachname',    
                        'standardwert' => 'vorname.nachname'
                        ],
        'passwort'  => [
                        'typ'     => 'text' ,     
                        'lab'     => 'neues Passwort',     
                        'tiptext' => ' wird&nbsp;verschl&uuml;sselt&nbsp;'
                        ],
        'pwd'       => [
                        'typ'     => 'password' , 
                        'lab'     => 'Passwort',
                        'tiptext' => ' Das Initialpassord ist ein Leerschlag'
                        ],
        'fileinfotext'    => [
                        'typ'   =>'label' , 
                        'lab'   => 'Hinweis' , 
                        'text'  => 'Zeigt alle Dateien im aktuellen Pfad und Aktionen, 
                                    die mit t3InstallHelp ausgef&uuml;hrt werden k&ouml;nnen.'
                        ],
        'passinfo'    => [
                        'typ'  => 'label' , 
                        'lab'  => '' , 
                        'text' => ''
                        ]
    ];

    /**
    * Property Aktionen
    *
    * @var array
    */
    Private $Aktionen = [
        'u'=>[ 'titel'=>'Typo3-Datei entpacken',  'felder'=>'pwd,original,subpfad',    'script' => 'actUnzip' ,      'autorun' => 0 ] , 
        'l'=>[ 'titel'=>'Symlink erzeugen',       'felder'=>'pwd,linkdatei,symlink',   'script' => 'actLink' ,       'autorun' => 0 ] ,
        'd'=>[ 'titel'=>'Symlink l&ouml;schen',   'felder'=>'pwd,symlink',             'script' => 'actDeletelink' , 'autorun' => 0 ] , 
        'f'=>[ 'titel'=>'Dateiliste',             'felder'=>'pwd,fileinfotext',        'script' => 'actFileInfo' ,   'autorun' => 1 ] , 
        'a'=>[ 'titel'=>'Preauth Links anzeigen', 'felder'=>'pwd,username,subdomains', 'script' => 'actPreauth' ,    'autorun' => 0 ] ,
        'p'=>[ 'titel'=>'Passwort &auml;ndern',   'felder'=>'pwd,passwort,passinfo',   'script' => 'actPassword' ,   'autorun' => 0 ]
    ];

    /**
    * Property mim
    *
    * @var array
    */
    Private $mim = [
            'zip' => [ 'cmd' => 'unzip' ,     'opt' => '-d' ],
            'tgz' => [ 'cmd' => 'tar -zxvf' , 'opt' => '-C' ],
            'gz'  => [ 'cmd' => 'tar -zxvf' , 'opt' => '-C' ]
    ];

    /**
    * Property Form
    *
    * @var array
    */
    Private $Form = ['charset'=>'ISO-8859-1','name'=>'installform'];

    /**
    * Property backlink
    *
    * @var string
    */
    Private $backlink = '<p>&larr; <a href="/">zu Server Root</a></p>';
    
    /**
    * Property req
    *
    * @var array
    */
    Private $req = null;
    
    /**
    * Property Pfade
    *
    * @var array
    */
    Private $Pfade = ['original'=>'','basis'=>''];
    
    /**
    * Property configFileName
    *
    * @var string
    */
    Private $configFileName = 't3InstallHelper_config.php';
    
    /**
     * main
     *   initiate script
     *   returns string with final HTML code
     *
     * @return string
     */
    Public function main(){
    
        $this->setUpVariales();
        
        // if ok was clicked then run data-Action
        if( isset($_POST['ok']) || $this->Aktionen[$this->req['aktion']]['autorun'] ){
                $actionResult = $this->runAction();
        }

        // create the input form part of html document
        $bodyOut = $this->htmFormular();

        // if ok was clicked then append result from data-Action, wrap it as html div and append to form.
        if( isset($actionResult) ){
                $bodyOut .= $actionResult;
        }

        // output header , form, runAction()-result and footer
        $htmlOut = $this->wrapAsHtml($bodyOut);
        
        return $htmlOut;
        
    }
    
    /**
     * setUpVariales
     *
     * @return boolean
     */
    Private function setUpVariales(){
        // detect paths
        $this->Pfade['original'] = rtrim( dirname(__FILE__), "/")."/";
        $this->Pfade['basis'] = rtrim($_SERVER['DOCUMENT_ROOT'],"/")."/";
        
        // overwrite defaults with own
        if( file_exists($this->configFileName) ) include_once($this->configFileName);

        // get incomed values
        foreach(array_keys($this->Felder) as $inVar){
                if(!empty($_REQUEST[$inVar])){
                    $this->req[$inVar] = $_REQUEST[$inVar];
                }
        }
        
        // set default values
        if(!isset($this->req['aktion'])){
            $this->req['aktion'] = $this->Felder['aktion']['standardwert'];
        }
        
        //  add last action or default value
        if( isset($_REQUEST['lastaction']) && !empty($_REQUEST['lastaction']) ){
            $this->req['lastaction'] = $_REQUEST['lastaction'];
        }else{
            $this->req['lastaction'] = $this->req['aktion'];
        }
        return true;
    }
    
    /**
     * writeConfig
     *
     * @return boolean
     */
    Private function writeConfig(){
        
        $aFieldsToStore = [
            'aktion'     => ['standardwert'],
            'subdomains' => ['standardwert'],
            'linkdatei'  => ['standardwert','tiptext'],
            'pwd'    => ['tiptext'],
            'subdomains' => ['standardwert'],
        ];
        $strDocument = '## password for this script. For a blank char like " " set the value b858cb282617fb0956d960215c8e84d1ccf909c6' . "\n";
        $strDocument .= '$this->strDocupassword = ' . "'" . $this->strDocupassword . "';\n";
        $strDocument .= "\n## preauth\n";
        $strDocument .= '$this->strSecretPreauthKey = ' . "'" . $this->strSecretPreauthKey . "';\n";
        $strDocument .= '$this->strAuthSeparer = ' . "'" . $this->strAuthSeparer . "';\n";
        $strDocument .= '$this->backlink = ' . "'" . $this->backlink . "';\n";
        
        $strDocument .= '$this->aIngredients = [];' . "\n";
        foreach( $this->aIngredients as $fieldname => $content ){
            $strDocument .= '$this->aIngredients'."['" . $fieldname . "'] = '" . $content . "';\n";
        }
        
        $strDocument .= "\n## own settings\n";
        
        foreach( $aFieldsToStore as $fieldname => $aTypen){
            foreach( $aTypen as $typ){
                $strDocument .= '$this->Felder'."['" . $fieldname . "']['" . $typ . "'] = '" . $this->Felder[$fieldname][$typ] . "';\n";
            }
        }
        
        file_put_contents( $this->configFileName , "<?PHP\n" . $strDocument . "?>" );
    }
    
    /**
     * wrapAsHtml
     *  enritch the content with different wrap if logged in
     *
     * @param string $content
     * @return string
     */
    Private function wrapAsHtml( $content ){
        $loginTest = $this->loginTest();
        $tit = $loginTest > 0 ? 't3installHelp | ' . $this->Aktionen[$this->req['aktion']]['titel']: 'Login to t3 Install Helper';
        
        $header = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n  \"http://www.w3.org/TR/html4/loose.dtd\">";
        $header.= "<html><head><title>".$tit."</title></head>";
        
        $protocoll = 'localhost' == $_SERVER['SERVER_NAME'] ? 'http://' : 'https://';
        $URL = $protocoll . $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
        
        $body = '  <body>' . "\n";
        $body.= '    <div style="margin:10px auto;max-width:1150px;">' . "\n";
        $body.= '      <div style="min-width:570px;max-width:1150px;border:1px solid #AAA;border-radius:6px;margin:20px 5px; background:#e9edef; padding:10px 8px;">' . "\n";
        $body.= '        <h2 style="margin:0 0 10px 0;padding: 0 0 5px 0; border-bottom:thin solid #aaa;">' . "\n";
        $body.= '          <span style="font-variant-caps: small-caps;">t3 Install Helper</span>' . "\n";
        $body.= '          <span style="font-size:75%;font-weight:normal;">v' . $this->strVersion . '</span>' . "\n";
        $body.= '          <span style="font-size:50%;font-weight:normal;"> &copy;' . date('Y') . ' MIT Daniel R&uuml;egg</span>' . "\n";
        
        if( $loginTest > 0 ) {
                $body.= '          <span style="font-size:75%;font-weight:normal;"><a href="'. $URL. '">Logout</a></span>' . "\n";
                $body.= '          <p style="font-size:50%;font-weight:normal;padding:0;margin:5px 0;">' . "\n";
                $body.= '          Diese Datei';
                $body.= ' &raquo;' . pathinfo( __FILE__ , PATHINFO_BASENAME). '&laquo;';
                $body.= ' nach Gebrauch vom Webspace entfernen!';
                $body.= ' Standort: ' . dirname( __FILE__ ) . '/';
                $body.= '</p>' . "\n";
        }
        $body.= "        </h2>\n";

        if( $loginTest <= 0 ) $body.= $this->backlink . "\n";

        $body.= "\n" . $content . "\n";
        
        if( $loginTest > 0 ) {
                $body.= '<p style="border-top:1px solid #aaa;font-size:80%;padding:10px 0 0 0;margin:15px 0 0 0;font-style:italic;;font-weight:normal;">';
                $body.= ' Built to set up Typo3 installations on hosted servers without ssh-access';
                $body.= '</p>' . "\n";
        }
        
        $body.= '      </div>' . "\n";
        $body.= '    </div>' . "\n";
        $body.= '  </body>' . "\n";
        $footer = '</html>' . "\n";
        
        return $header.$body.$footer;
    }
    
    /**
     * htmFormular
     *  input elements with different content if logged in
     *
     * @return string
     */
    Private function htmFormular(){
        $felder = explode(",",$this->Aktionen[$this->req['aktion']]['felder']);
        $protocoll = 'localhost' == $_SERVER['SERVER_NAME'] ? 'http://' : 'https://';
        $URL = $protocoll . $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
        $formularKopf = "\n<form action='".$URL."' id='".$this->Form['name']."' name='".$this->Form['name']."' enctype='multipart/form-data' method='post' enctype='multipart/form-data' method='post' accept-charset='".$this->Form['charset']."'> \n";

        $isLoggedIn = $this->loginTest();
        if( $isLoggedIn <= 0 ){
            // display login formular
                $formularBody = $this->formFeldRow('pwd');
                $formularBody .= "\n<tr>\n<td colspan='2'>";
                if( $isLoggedIn < 0 && !isset($_POST['ok']) ) $formularBody.= '<label for="pwd">Passwort falsch! </label>';
                $formularBody.= "\n</td>\n</tr>";
                $formularBody.= "\n<tr><td></td><td><input type='submit' name='login' value='Login'></td></tr>";

        }else{
            // display a action
                $formularBody = "\n<tr>\n<td>\n\t<label title='aktion' for='aktion'>";
                $formularBody.= "".$this->Felder['aktion']['lab']."</label>\n</td>\n<td>";
                $formularBody.= "".$this->formFeldObj('aktion')."";
                $formularBody.= "\n<input type='submit' name='chng' value='Wechseln'>";
                $formularBody.= "\n<input type='hidden' name='lastaction' value='" . $this->req['aktion'] . "'>";
                $formularBody.= "\n</td>";
                $formularBody.= "\n</tr>";
                $formularBody.="\n<tr>\n<th align='left' colspan='2'><h2>";
                $formularBody.= $this->Aktionen[ $this->req['aktion'] ]['titel'];
                $formularBody.="</h2>\n</td>\n</tr>";
                foreach( $felder as $fld){
                    $formularBody.=$this->formFeldRow($fld);
                }
                if( !$this->Aktionen[ $this->req['aktion'] ]['autorun'] ) $formularBody.="\n<tr><td></td><td><input type='submit' name='ok' value='Ok'></td></tr>";

        }
        $formularEnde = $this->formHidden($this->req['aktion']);
        $formularEnde .= "\n</form>\n";

        return $formularKopf . "\n<table border='0'>" . $formularBody . "\n</table>" . $formularEnde . "\n";
    }
    
    /**
     * formHidden
     *  puts unused fields in hidden-Fields to remember variables
     *
     * @param string $aktion
     * @return string
     */
    Private function formHidden($aktion){
        $felder = explode( "," , $this->Aktionen[$aktion]['felder'] );
        $noFld=['aktion'];
        $hid = [];
        foreach(array_keys($this->Felder) as $hf){
                if( $hf == $felder[array_search( $hf , $felder)] )continue;
                if( $hf == $noFld[array_search( $hf , $noFld)] )continue;
                $hid[]="<input type='hidden' name='".$hf."' value='". ( isset($this->req[$hf]) ? $this->req[$hf] : '' ) ."'>";
        }
        $strHidden = @implode("\n\t",$hid);
        return $strHidden;
    }
    
    /**
     * formFeldRow
     *   outputs a complete table-row for input-elemnent including label
     *
     * @param string $fld
     * @return string
     */
    Private function formFeldRow( $fld ){
        if( $fld == 'pwd' && $this->loginTest() > 0 ){
            $row = "<tr><td></td><td>".$this->formFeldObj($fld)."</td></tr>";
        }else{
            if(isset($this->Felder[$fld]['lab'])){
                    $lab = "\n\t<label title='".$fld."' for='".$fld."'>".$this->Felder[$fld]['lab']."</label>";
            }else{
                    $lab = "\n\t<label title='".$fld."' for='".$fld."'>".$fld."</label>";
            }
            $row = "\n<tr>\n<td width='120'>".$lab."\n</td>\n<td>".$this->formFeldObj($fld)."\n</td>\n</tr>";
        }
        return $row;
    }
    
    /**
     * formFeldObj
     *   returns a input-element formatted as given in settings Felder
     *
     * @param string $fld
     * @return string
     */
    Private function formFeldObj( $fld ){
        $opts = '';
        switch($this->Felder[$fld]['typ']){
        case "select":
            $FldListe= $this->formFeldListCnt($this->Felder[$fld]['listen']);
            if(!is_array($FldListe))return $this->Pfade['original'];
            if(!isset($this->req[$fld])){if(isset($this->Felder[$fld]['standardwert']) ) $isSel[$this->Felder[$fld]['standardwert'] ]=" selected";}else{$isSel[ $this->req[$fld] ]=" selected";}
            foreach(array_keys($FldListe) as $oNr){ if( isset($FldListe[$oNr]) ) $opts.="\n\t\t<option value='".$oNr."'".( isset($isSel[$oNr]) ? $isSel[$oNr] : '' ).">".$FldListe[$oNr]."</option>";}
            $tiptext = isset($this->Felder[$fld]['tiptext']) ? ' ' . $this->Felder[$fld]['tiptext'] . '' : '' ;
            return "\n\t<select name='".$fld."' id='".$fld."'>".$opts."\n\t</select>".$tiptext."";
        break;
        case "password":
           $loggedIn = $this->loginTest();
            if(!isset($this->req[$fld])){
                    $defValue = isset($this->Felder[$fld]['standardwert']) ? $this->Felder[$fld]['standardwert'] : '';
            }else{
                    $defValue = $loggedIn == 1 ? $this->req[$fld] : ''; // protect from attack by " /> <input value="...
            }
            $entry = "\n\t";
            if( $loggedIn == 1 ){
                $entry.= '<input type="hidden" name="'.$fld.'" id="'.$fld.'" value="'.$defValue.'">';
            }else{
                $entry.= '<input size="40" type="text" title="'.$defValue.'" name="'.$fld.'" id="'.$fld.'" value="">';
                $entry.= isset($this->Felder[$fld]['tiptext']) ? $this->Felder[$fld]['tiptext'] : '' ;
            }
            
            return $entry;
        break;
        case "label":
            $entry = isset($this->Felder[$fld]['text']) ? $this->Felder[$fld]['text'] : '';
            $entry.= isset($this->Felder[$fld]['tiptext']) ? $this->Felder[$fld]['tiptext'] : '' ;
            return $entry;
        break;
        case "text":
        default:
            if(!isset($this->req[$fld])){$defValue = isset($this->Felder[$fld]['standardwert']) ? $this->Felder[$fld]['standardwert'] : '';}else{$defValue = $this->req[$fld];}
            return "\n\t<input size='50' type='text' name='".$fld."' id='".$fld."' value='".$defValue."'>" . ( isset($this->Felder[$fld]['tiptext']) ? $this->Felder[$fld]['tiptext'] : ''  );
        break;
        }
    }
    
    /**
     * formFeldListCnt
     *  Returns lists as array for different purposes.
     *  Used to fill select-options in select-elements.
     *
     * @param string $lstNam
     * @return array
     */
    Private function formFeldListCnt( $lstNam ){
        $outArr = [];
        switch($lstNam){
        case "aktList":
            foreach(array_keys($this->Aktionen) as $akt){
                $outArr[$akt]=$this->Aktionen[$akt]['titel'];
            }
            return $outArr;
        break;
        case "fileList":
            $rootpfad=$this->Pfade['original'];
            $Fils = $this->formFeldListZeigPfad( $rootpfad );
            foreach($Fils as $fil){
                if(filetype($rootpfad.$fil)!='file') continue;
                $mime = pathinfo($fil,PATHINFO_EXTENSION);
                if( !isset( $this->mim[$mime]['cmd'] ) ) continue;
                $outArr[$fil]=$fil;
            }
            return $outArr;
        break;
            case "subDirList":
                $rootpfad=$this->Pfade['original'];
                $Fils = $this->formFeldListZeigPfad( $rootpfad );
                $actalPath = trim(str_replace( $this->Pfade['basis'] , '' , $this->Pfade['original']) , '/');
                $outArr['.'] = $actalPath . '/ (aktueller Scriptpfad)';
                if(is_array($Fils)){
                        sort($Fils);
                        foreach($Fils as $fil){
                                if(filetype($rootpfad.$fil)!='dir'){continue;}
                                $outArr[$actalPath . '/' . $fil] = $actalPath . '/' . $fil . '';
                        }
                }
                return $outArr;
            case "dirList":
                $rootpfad=$this->Pfade['basis'];
                $Fils = $this->formFeldListZeigPfad( $rootpfad );
                if(is_array($Fils)){
                        sort($Fils);
                        foreach($Fils as $fil){
                                if(substr($fil,0,4)=='_vti'){continue;}
                                if($fil=='_private'){continue;}
                                if($fil=='cgi-bin'){continue;}
                                if($fil==$outArr['.']){continue;}
                                if(filetype($rootpfad.$fil)!='dir'){continue;}
                                $outArr[$fil] = $fil ;
                        }
                }
                return $outArr;
            break;
        }
    }
    
    /**
     * Helper formFeldListZeigPfad
     *  helper for formFeldListCnt()
     *  returns an array conatining the file-list from given path
     *
     * @param string $zPfad
     * @return array
     */
    Private function formFeldListZeigPfad( $zPfad ) {
            $aFiles = [];
            if(!file_exists($zPfad)) return false;
            $verz0 = opendir ( $zPfad );
            while ( $file = readdir ( $verz0 ) ){
                if ($file !="." && $file !="..") $aFiles[]= $file;
            };
            closedir ( $verz0 );
            return $aFiles;
    }
    
    /**
     * loginTest
     *  returns 
     *   0 if not logged in AND NO login-button clicked
     *   1 if logged in
     *  -1 if login failed
     *
     * @return int
     */
    Private function loginTest(){
//         if( 'da39a3ee5e6b4b0d3255bfef95601890afd80709' == $this->strDocupassword ) return 1;
        if( !isset($this->req['pwd']) ) return 0;
        if( $this->req['pwd'] && sha1($this->req['pwd']) == $this->strDocupassword ) return 1;
        return -1;
    }
    
    /**
     * runAction
     *  detect and run an action
     *
     * @return string with result for debug-purpose
     */
    Private function runAction(){
        $content = '';
        if( $this->loginTest() < 0 ){
            $content = 'Passwort fehler';
        }else{
            $action = $this->Aktionen[$this->req['aktion']]['script'];
            if( method_exists( $this , $action ) ) {
                $workResult = $this->$action();
                if( !isset($workResult) ) {
                    $content = "Aktion ".$this->Aktionen[$this->req['aktion']]['titel']. ": nicht gelungen ";
                }else{
                    $content = "Aktion <i>".$this->Aktionen[$this->req['aktion']]['titel']."</i>".trim(" ".$workResult);
                }
            }else{
                $content = "Aktion [".$this->req['aktion']. "] &raquo;".$this->Aktionen[$this->req['aktion']]['titel']. "&laquo; nicht vorhanden ";
            }
        }
        
        if($this->req['lastaction'] == $this->req['aktion']  || $this->Aktionen[$this->req['aktion']]['autorun']){
            $bodyOut = "\n" . "\n" . '<div style="border-top:thin solid #ccc; padding:10px 0 0 0;margin:10px 0 0 0;" >';
            $bodyOut .= "\n" . "\n" . $content;
            $bodyOut .= "\n" . "\n" . '</div>' . "\n";
        }else{
            $bodyOut = "\n" . "\n" . '<div style="border-top:thin solid #ccc; padding:15px 0 0 0;margin:10px 0 0 0;font-style:italic;" >';
            $bodyOut .= 'Aktion gewechselt zu: &raquo;' . $this->Aktionen[ $this->req['aktion'] ]['titel'] . '&laquo;';
            $bodyOut .= "\n" . "\n" . '</div>' . "\n";
        }
        
        return $bodyOut;
    }
    
    /**
     * Action actUnzip
     *
     * @return string with result for debug-purpose
     */
    Private function actUnzip(){
        //if(empty($this->req['neupfad']))return "nicht m&ouml;glich ohne umbenennen! (".$this->Felder['neupfad']['lab'].")";
        if ( !isset( $this->Pfade['original'] ) ) return '. »Pfad nicht gefunden«';
        if ( !isset( $this->req['original'] ) ) return '. »Keine passende Original-Datei vorhanden«';
        
        $ext = pathinfo( $this->Pfade['original'].$this->req['original'],PATHINFO_EXTENSION);
        
        if($this->req['subpfad']=='.'){//  hier entpackt 
            $rootpath= $this->Pfade['original']; 
        }elseif($this->req['subpfad']=='/'){
            $rootpath = $this->Pfade['basis'];
        }else{
            $rootpath = $this->Pfade['basis'].ltrim($this->req['subpfad'],"/")."/" ;
        }
        if (!file_exists($rootpath)) return '. Fehler - Pfad nicht gefunden: ' . $rootpath . ' '; //{mkdir($rootpath);}
        
        $command = $this->mim[$ext]['cmd']." ".$this->Pfade['original'].$this->req['original']." " . $this->mim[$ext]['opt'] . " ".$rootpath."";
        
        $op = exec( $command , $aExecResult);
        if(count($aExecResult) > 1){
            $outText =  ". Antwort: ". count($aExecResult) . " Dateien entpackt nach " . $rootpath . ".";
        }else{
            $outText = ". Keine Aktion, vielleicht aufgrund eines existenten Verzeichnisses? <br />Antwort: [" . ( isset($aExecResult[0]) ? $aExecResult[0] : "0" ) . "]";
        }
        
        return  $outText;
    }
    
    /**
     * Action actLink
     *
     * @return string with result for debug-purpose
     */
    Private function actLink(){
        if( !isset($this->req['linkdatei']) || empty($this->req['linkdatei']) ){
            return ". Fehler: 'Linkdatei' darf nicht leer sein.";
        }elseif( !isset($this->req['symlink']) || empty($this->req['symlink']) ){
            return ". Fehler: 'Symlink' darf nicht leer sein.";
        }elseif( strpos( ' ' . $this->req['symlink'] , '..' ) ){
            return ". Fehler: 'Symlink' darf nur im aktuellen Pfad oder einem Unterpfad erstellt werden. <br />Symlink Enth&auml;lt &raquo;..&laquo;!";
        }

        $link = trim( $this->req['symlink'] , '/' );
        // if the new link is in a deeper directory then prepend '../'
         $aLink = explode( '/' , $link );
         $relatedOrignPath = $this->req['linkdatei'];
        if( count($aLink) > 1 ){ 
                $relatedOrignPath =  str_repeat( '../' , count($aLink)-1 ) . $this->req['linkdatei'];
        }
        // then rename the symlink to his basename
        $basenameLink = array_pop( $aLink );
        
        $aShrinkBase = explode( '/' , trim( dirname(__FILE__) , '/' ) );
        
        $aOrig = explode( '/' , $this->req['linkdatei'] );
        $aTempBaseDir = [];
        foreach( $aOrig as $pathPart ){
            if( $pathPart == '..' ){ 
                $aTempBaseDir[] = array_pop($aShrinkBase) ; 
            }else{
                $aTempBaseDir[] = $pathPart ; 
            }
        }
        $lastIdx = count( $aTempBaseDir );
        $tempShrDir = implode( '/' , $aShrinkBase );
        
        $strPartName = '';
        foreach( $aOrig as $pathPart ){
            $lastIdx -= 1;
            if( $pathPart == '..' ){ 
                $tBaseDir = $aTempBaseDir[$lastIdx] ;
            }else{
                $strPartName .= '/' . $pathPart ; 
            }
        }
        $pathToOrigin = '/' . rtrim( $tempShrDir , '/' ) .''. $strPartName;
        
        $tempBaseDir = '/' . trim( dirname(__FILE__) , '/' ) . '/' ;
        
        if(!file_exists($pathToOrigin) ){
            return ". Fehler: 'linkdatei' existiert nicht: ".$pathToOrigin;
        }elseif(!file_exists(dirname($tempBaseDir.$link)) ){
            return ". Fehler: Directory f&uuml;r 'symlink'  existiert nicht: " . dirname($tempBaseDir.$link);
        }elseif( file_exists($tempBaseDir.$link) || is_link($tempBaseDir.$link) ){
            return ". Fehler: Datei existiert (".filetype($tempBaseDir.$link)."): ".$tempBaseDir.$link."";
        }
        
        chdir( dirname($tempBaseDir.$link) );
        symlink( $relatedOrignPath ,  $basenameLink);
        // also possible by exec:
        // exec( 'ln -s ' . $this->req['linkdatei'] . ' ' . $link );
        return ". ok, gelinkt: ".$tempBaseDir.$link." <br />-> Verweist auf: ".$relatedOrignPath."";
    }
    
    /**
     * Action actDeletelink
     *
     * @return string with result for debug-purpose
     */
    Private function actDeletelink(){
        if( isset($this->Felder['symlink']['standardwert']) ) unset($this->Felder['symlink']['standardwert']);
        
        if( !isset($this->req['symlink']) || empty($this->req['symlink']) ){
            return ". Fehler: 'Symlink' darf nicht leer sein.";
        }
        
        $link = trim($this->req['symlink'],"/");
        $pathToOrigin = '/' . trim( dirname(__FILE__) , '/' ) . '/';
        if(!file_exists($pathToOrigin.$link) ){
            return ". Fehler: Symlink '".$pathToOrigin.$link."' <br />existiert nicht.";
        }elseif(filetype($pathToOrigin.$link) != 'link' ){
            return ". Fehler, Datei '".$pathToOrigin.$link."' <br />ist kein Link, sondern vom Typ '".filetype($pathToOrigin.$link)."'.";
        }
        
        unlink($pathToOrigin.$link);
        
        return ". ok, Link gel&ouml;scht: ".$pathToOrigin.$link;
    }
    
    /**
     * Action actFileInfo
     *
     * @return string with result for debug-purpose
     */
    Private function actFileInfo(){
        $aOptions = [ 
                'ordner'  => [
                    'style' => 'color:#fff;' ,                   
                    'command' => 'Ziel f&uuml;r Symlink oder zum entpacken'
                ], 
                'aktuell' => [ 
                    'style' => 'color:#aaa;' ,            
                    'command' => 'laufendes Skript oder Konfigurationsdatei' 
                ], 
                'link'    => [ 
                    'style' => 'color:#eb0;' ,                   
                    'command' => 'Symlink l&ouml;schen' 
                ], 
                'datei'   => [ 
                    'style' => 'color:#090;' ,                   
                    'command' => '' 
                ], 
                'aktion'  => [ 
                    'style' => 'color:#c70;font-style:italic;' , 
                    'command' => '' 
                ]
        ];
        
        $iLongestFilename = 0;
        $aFile = [];
        $aFilesInPath = $this->formFeldListZeigPfad( $this->Pfade['original'] );
        foreach( $aFilesInPath as $filename ){
            if( is_link($this->Pfade['original'] .$filename) ){
                $typ = 'link';
                
            }elseif( $this->Pfade['original'] . $filename == __FILE__ || $filename == $this->configFileName ){
                $typ = 'aktuell';
                
            }elseif( is_dir( $this->Pfade['original'] . $filename ) ){
                $typ = 'ordner';
                $filename .= '/';
                
            }else{
                $typ = 'datei';
                
            }
            $aFile[$typ=='ordner'?0:1][$filename] = $typ;
            if( strlen($filename) > $iLongestFilename ) $iLongestFilename = strlen($filename);
        }
        // add 3 points as offset, sort to set order for dir and file
        $iLongestFilename +=3;
        ksort($aFile);
        
        $fileInfo = '<div style="padding:3px;font-size:11pt;font-family: courier,monospace;background:black;color:#e0e0e0;">';
        $fileInfo .= '<i>Dateien in diesem Pfad, [typ] und ';
        $fileInfo .= '<span style="' . $aOptions['aktion']['style'] . '">';
        $fileInfo .= 'Aktionen, die mit diesem Script ausgef&uuml;hrt werden k&ouml;nnen:';
        $fileInfo .= '</span> ';
        $fileInfo .= '</i>';
        $fileInfo .= '<p style="padding:3px;font-family: courier,monospace;background:black;">';
        
        $iLongestType = 0;
        foreach( $aFile as $srt => $aSrtFile ) {
                foreach( $aSrtFile as $file => $typ ) { if( strlen($typ) > $iLongestType ) $iLongestType = strlen($typ); }
        }
        foreach( $aFile as $srt => $aSrtFile ) { 
            if( is_array($aSrtFile) ) ksort($aSrtFile);
            foreach( $aSrtFile as $file => $typ ) { 
                    $mim = pathinfo( $file , PATHINFO_EXTENSION ) ;
                    $aOptions['datei']['command'] = isset($this->mim[$mim]['cmd']) ? 'Datei entpacken mit &raquo;' . $this->mim[$mim]['cmd'] . '&laquo;' : '';
                    $strHintOffset = $iLongestType > strlen($typ) ? $iLongestType+1 - strlen($typ) : 1;
                    $strTabOffset = $iLongestFilename - strlen( $file );
                    $fileInfo .= '<span style="'; 
                    $fileInfo .= $aOptions[$typ]['style']; 
                    if($aOptions[$typ]['command']) $fileInfo .= 'font-weight:bold;'; 
                    $fileInfo .= '">'; 
                    $fileInfo .= $file; 
                    if( $strTabOffset ) $fileInfo .= str_repeat( '.' , $strTabOffset ); 
                    $fileInfo .= '['. trim( ucFirst($typ) ) . ']</span>'; 
                    if($aOptions[$typ]['command'] ) {
                        $charsOffset = str_repeat( '&nbsp;' , $strHintOffset );
                        $fileInfo .= '<span style="' . $aOptions['aktion']['style'] . '">' . $charsOffset . $aOptions[$typ]['command'] . '</span>'; 
                    }
                    $fileInfo .= '<br />';
            }
        }
        $fileInfo .= '</p>';
        $fileInfo .= '<i>Total '. ( isset($aFile[0]) ? count($aFile[0]) : 0 ) . ' Ordner und '. ( isset($aFile[1]) ? count($aFile[1]) : 0 ) . ' Dateien.</i>';
        $fileInfo .= '</div>';
        return $fileInfo;
    }
    
    /**
     * Action actPreauth
     *
     * @return string with result for debug-purpose
     */
    Private function actPreauth(){
        if( !isset($this->req['username']) || empty($this->req['username']) || trim($this->req['username']) == '' ) return '. Kein Benutzername angegeben.';
        if( !isset($this->req['subdomains']) ) return '. Kein Url angegeben.';
        
        if( empty( $this->aIngredients['timestamp'] ) ) {
            $this->aIngredients['timestamp'] = ( time() * 1000 );
        }
        
        $this->aIngredients['account'] = $this->req['username'];
        
        $aXingedients = $this->aIngredients;
        unset($aXingedients['preauth']);
        $xDataString = implode( $this->strAuthSeparer , $aXingedients );
        $this->aIngredients['preauth'] = hash_hmac('sha1', $xDataString , $this->strSecretPreauthKey );

        $uri = '';
        $z = 0;
        foreach( $this->aIngredients as $key => $value ){
            ++$z;
            $uri .= 1==$z ? '?' : '&amp;' ;
            $uri .= $key . '=' . $value;
        }
        
        $timeInfo = 'g&uuml;ltig bis um ' . date( 'H:i' , ( ($this->aIngredients['timestamp']/1000) + 600 ) )  . ' Uhr';
        $strForm = ':  <p>Links f&uuml;r &laquo;<b>'.$this->req['username'].'</b>&raquo;, '.$timeInfo.'</p>';
        $subdomains = strpos( $this->req['subdomains'] , ',') ? explode( ',' , $this->req['subdomains'] ) : explode( ' ' , $this->req['subdomains'] ) ;
        foreach( $subdomains as $subdom ) $strForm .= '<a target="_blank" href="' . trim(trim($subdom)) . '/apps/Login.aspx' . $uri . '">' . trim($subdom) . '</a> <br />';

        return " " . $strForm;
        
    }
    
    /**
     * Action actPassword
     *
     * @return string with result for debug-purpose
     */
    Private function actPassword(){
            
        $outtext = '';
        
        if( isset($this->req['passwort']) && strlen($this->req['passwort']) ){

            $newPasswordHash = sha1($this->req['passwort']);
            
            if( $this->strDocupassword == $newPasswordHash ){
                $outtext = ' <p>Passwort bleibt gleich.</p>';
                
            }elseif( $this->loginTest() ){
                $this->strDocupassword = $newPasswordHash;
                $this->writeConfig();
                $outtext .= '<p>OK, das Passwort wurde ge&auml;ndert.';
            }
            
        }

        
        return "\n" . $outtext . "\n";
    }
    
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$frm = new t3InstallHelper();
echo $frm->main();

die();

?>
