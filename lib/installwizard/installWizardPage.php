<?php

/**
* Installation wizard
*
* @package     InstallWizard
* @author      Laurent Jouanneau
* @copyright   2010 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/

/**
 * base class to implement a wizard page
 */
class installWizardPage {
    
    /**
     * The content of the configuration in the corresponding section of the page
     * in the main configuration (install.ini.php)
     * @var array
     */
    protected $config;
    
    /**
     * the content of the locales file corresponding to the current lang
     * @var array
     */
    protected $locales = array();
    
    /**
     * list of errors or other data which will be used during the display
     * of the page if the submit has failed. must be a key=>value array.
     * It will be injected into the template.
     * @var array
     */
    protected $errors = array();
    
    /**
     * @param array $confParameters the content of the configuration
     * @param array $locales the content of the locales file
     */
    function __construct($confParameters, $locales) {
        $this->config = $confParameters;
        $this->locales = $locales;
    }
    
    /**
     * action to display the page.
     * @param jTpl $tpl the template container which will be used
     * to display the page. The template should be store in the
     * same directory of the page class, with the same prefix.
     * @return void
     */
    function show ($tpl) {

    }

    /**
     * action to process the page after the submit. you can call
     * $_POST to retrieve the content of the form of your template.
     * It should return the index of the "next" step name stored
     * in the configuration.
     *
     * @return integer the index of the "next" step name
     */
    function process() {
        return 0;
    }

    /**
     * internal use.
     * @return array the content of $errors.
     */
    function getErrors() {
        return $this->errors;
    }
}
