<?php
/**
* jMailer : based on PHPMailer - PHP email class
* Class for sending email using either
* sendmail, PHP mail(), SMTP, or files for tests.  Methods are
* based upon the standard AspEmail(tm) classes.
*
* @package     jelix
* @subpackage  utils
* @author      Laurent Jouanneau
* @contributor Kévin Lepeltier, GeekBay, Julien Issler
* @copyright   2006-2016 Laurent Jouanneau
* @copyright   2008 Kévin Lepeltier, 2009 Geekbay
* @copyright   2010-2015 Julien Issler
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require(LIB_PATH.'phpMailer/class.phpmailer.php');
require(LIB_PATH.'phpMailer/class.smtp.php');
require(LIB_PATH.'phpMailer/class.pop3.php');


/**
 * jMailer based on PHPMailer - PHP email transport class
 * @package jelix
 * @subpackage  utils
 * @author Laurent Jouanneau
 * @contributor Kévin Lepeltier
 * @copyright   2006-2008 Laurent Jouanneau
 * @copyright   2008 Kévin Lepeltier
 * @since 1.0b1
 * @see PHPMailer
 */
class jMailer extends PHPMailer {

    /**
     * the selector of the template used for the mail.
     * Use the Tpl() method to change this property
     * @var string
     */
    protected $bodyTpl = '';

    protected $defaultLang;

    /**
     * the path of the directory where to store mails
     * if mailer is file.
    */
    public $filePath = '';

    /**
     * indicates if mails should be copied into files, so the developer can verify that all mails are sent.
     */
    protected $copyToFiles = false;

    protected $htmlImageBaseDir = '';

    protected $html2textConverter = false;

    /**
     * initialize some member
     */
    function __construct(){
        $config = jApp::config();
        $this->defaultLang = $config->locale;
        $this->CharSet = $config->charset;
        $this->Mailer = $config->mailer['mailerType'];
        if ($config->mailer['mailerType']) {
            $this->Mailer = $config->mailer['mailerType'];
        }
        $this->Hostname = $config->mailer['hostname'];
        $this->Sendmail = $config->mailer['sendmailPath'];
        $this->Host = $config->mailer['smtpHost'];
        $this->Port = $config->mailer['smtpPort'];
        $this->Helo = $config->mailer['smtpHelo'];
        $this->SMTPAuth = $config->mailer['smtpAuth'];
        $this->SMTPSecure = $config->mailer['smtpSecure'];
        $this->Username = $config->mailer['smtpUsername'];
        $this->Password = $config->mailer['smtpPassword'];
        $this->Timeout = $config->mailer['smtpTimeout'];
        if ($config->mailer['webmasterEmail'] != '') {
            $this->From = $config->mailer['webmasterEmail'];
        }

        $this->FromName = $config->mailer['webmasterName'];
        $this->filePath = jApp::varPath($config->mailer['filesDir']);

        $this->copyToFiles = $config->mailer['copyToFiles'];

        parent::__construct(true);

    }

    /**
     * Sets Mailer to store message into files instead of sending it
     * useful for tests.
     * @return void
     */
    public function IsFile() {
        $this->Mailer = 'file';
    }


    /**
     * Find the name and address in the form "name<address@hop.tld>"
     * @param string $address
     * @param string $kind One of 'to', 'cc', 'bcc', or 'ReplyTo'
     * @return array( $name, $address )
     */
    function getAddrName($address, $kind = false) {
        if (preg_match ('`^([^<]*)<([^>]*)>$`', $address, $tab )) {
            $name = $tab[1];
            $addr = $tab[2];
        }
        else {
            $name = '';
            $addr = $address;
        }
        if ($kind) {
            $this->addAnAddress($kind, $addr, $name);
        }
        return array($addr, $name);
    }

    protected $tpl = null;

    /**
     * Adds a Tpl référence.
     * @param string $selector
     * @param boolean $isHtml  true if the content of the template is html.
     *                 IsHTML() is called.
     * @param false|callable  an html2text converter when the content is html.
     * By default, it uses the converter of jMailer, html2textKeepLinkSafe(). (since 1.6.17)
     * @param string $basedir Absolute path to a base directory to prepend to relative paths to images (since 1.6.17)
     * @return jTpl the template object.
     */
    public function Tpl( $selector, $isHtml = false, $html2textConverter = false, $htmlImageBaseDir='') {
        $this->bodyTpl = $selector;
        $this->tpl = new jTpl();
        $this->isHTML($isHtml);
        $this->html2textConverter = $html2textConverter;
        $this->htmlImageBaseDir = $htmlImageBaseDir;
        return $this->tpl;
    }

    /**
     * Creates message and assigns Mailer. If the message is
     * not sent successfully then it returns false.  Use the ErrorInfo
     * variable to view description of the error.
     * @return bool
     */
    function Send() {

        if (isset($this->bodyTpl) && $this->bodyTpl != "") {
            if ($this->tpl == null)
                $this->tpl = new jTpl();
            $mailtpl = $this->tpl;
            $metas = $mailtpl->meta( $this->bodyTpl , ($this->ContentType == 'text/html'?'html':'text') );

            if (isset($metas['Subject']) && is_string($metas['Subject'])) {
                $this->Subject = $metas['Subject'];
            }

            if (isset($metas['Priority']) && is_numeric($metas['Priority'])) {
                $this->Priority = $metas['Priority'];
            }
            $mailtpl->assign('Priority', $this->Priority );

            if (isset($metas['Sender']) && is_string($metas['Sender'])) {
                $this->Sender = $metas['Sender'];
            }
            $mailtpl->assign('Sender', $this->Sender );

            foreach (array('to'=>'to',
                         'cc'=>'cc',
                         'bcc'=>'bcc',
                         'ReplyTo'=>'Reply-To') as $prop=>$propName) {
                if (isset($metas[$prop])) {
                    if (is_array($metas[$prop])) {
                        foreach ($metas[$prop] as $val) {
                            $this->getAddrName($val, $propName);
                        }
                    }
                    else if (is_string($metas[$prop])) {
                        $this->getAddrName($metas[$prop], $propName);
                    }
                }
                $mailtpl->assign($prop, $this->$prop );
            }

            if (isset($metas['From'])) {
                $adr = $this->getAddrName($metas['From']);
                $this->setFrom($adr[0], $adr[1]);
            }

            $mailtpl->assign('From', $this->From );
            $mailtpl->assign('FromName', $this->FromName );

            if ($this->ContentType == 'text/html') {
                $converter = $this->html2textConverter ? $this->html2textConverter: array($this, 'html2textKeepLinkSafe');
                $this->msgHTML($mailtpl->fetch( $this->bodyTpl, 'html'), $this->htmlImageBaseDir, $converter);
            }
            else
                $this->Body = $mailtpl->fetch( $this->bodyTpl, 'text');
        }

        return parent::Send();
    }

    public function CreateHeader() {
        if ($this->Mailer == 'file') {
            // to have all headers in the file, like cc, bcc...
            $this->Mailer = 'sendmail';
            $headers = parent::CreateHeader();
            $this->Mailer = 'file';
            return $headers;
        }
        else {
            return parent::CreateHeader();
        }
    }

    /**
     * store mail in file instead of sending it
     * @access public
     * @return bool
     */
    protected function FileSend($header, $body) {
        return jFile::write ($this->getStorageFile(), $header.$body);
    }

    protected function getStorageFile() {
        return rtrim($this->filePath,'/').'/mail.'.jApp::coord()->request->getIP().'-'.date('Ymd-His').'-'.uniqid(mt_rand(), true);
    }

    function SetLanguage($lang_type = 'en', $lang_path = 'language/') {
        $lang = explode('_', $lang_type);
        return parent::SetLanguage($lang[0], $lang_path);
    }

    protected function lang($key) {
      if(count($this->language) < 1) {
        $this->SetLanguage($this->defaultLang); // set the default language
      }
      return parent::lang($key);
    }

    protected function sendmailSend($header, $body) {
        if ($this->copyToFiles)
            $this->copyMail($header, $body);
        return parent::SendmailSend($header, $body);
    }

    protected function MailSend($header, $body) {
        if ($this->copyToFiles)
            $this->copyMail($header, $body);
        return parent::MailSend($header, $body);
    }

    protected function smtpSend($header, $body) {
        if ($this->copyToFiles)
            $this->copyMail($header, $body);
        return parent::SmtpSend($header, $body);
    }

    protected function copyMail($header, $body) {
        $dir = rtrim($this->filePath,'/').'/copy-'.date('Ymd').'/';
        if (isset(jApp::coord()->request))
            $ip = jApp::coord()->request->getIP();
        else $ip = "no-ip";
        $filename = $dir.'mail-'.$ip.'-'.date('Ymd-His').'-'.uniqid(mt_rand(), true);
        jFile::write ($filename, $header.$body);
    }


    /**
     * Convert HTML content to Text.
     *
     * Basically, it removes all tags (strip_tags). For <a> tags, it puts the
     * link in parenthesis, except <a> elements having the "notexpandlink".
     * class.
     * @param string $html
     * @return string
     * @since 1.6.17
     */
    public function html2textKeepLinkSafe($html) {
        $regexp = "/<a\\s[^>]*href\\s*=\\s*([\"\']??)([^\" >]*?)\\1([^>]*)>(.*)<\/a>/siU";
        if(preg_match_all($regexp, $html, $matches, PREG_SET_ORDER)) {
            foreach($matches as $match) {
                if (strpos($match[3], "notexpandlink") !== false) {
                    continue;
                }
                // keep space inside parenthesis, because some email client my
                // take parenthesis as part of the link
                $html = str_replace($match[0], $match[4].' ( '.$match[2].' )', $html);
            }
        }
        $html = preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $html);

        return html_entity_decode(
            trim(strip_tags($html)),
            ENT_QUOTES,
            $this->CharSet
        );
    }
}
