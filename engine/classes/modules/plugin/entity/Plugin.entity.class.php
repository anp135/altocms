<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 */

/**
 * Class ModulePlugin_EntityPlugin
 *
 * @method bool GetIsActive()
 *
 * @method setNum($iParam)
 *
 * @method int getNum()
 */
class ModulePlugin_EntityPlugin extends Entity {

    /** @var SimpleXMLElement */
    protected $oXml = null;

    /**
     * Constructor of entity
     *
     * @param bool $aParams
     */
    public function __construct($aParams = false) {

        parent::__construct();
        if (!is_array($aParams)) {
            // передан ID плагина
            $aParams = array(
                'id' => (string)$aParams,
            );
        }

        $this->setProps($aParams);

        if(empty($aParams['manifest']) && !empty($aParams['id'])) {
            $aParams['manifest'] = E::ModulePlugin()->getPluginManifestFile($aParams['id']);
        }
        if(!empty($aParams['manifest'])) {
            $this->LoadFromXmlFile($aParams['manifest'], $aParams);
        }
        $this->init();
        if (!$this->getNum()) {
            $this->setNum(-1);
        }
    }

    /**
     * Load data from XML file
     *
     * @param string $sPluginXmlFile
     * @param array  $aData
     */
    public function LoadFromXmlFile($sPluginXmlFile, $aData = null) {

        $sPluginXmlString = E::ModulePlugin()->getPluginManifestFrom($sPluginXmlFile);
        $this->loadFromXml($sPluginXmlString, $aData);
    }

    /**
     * Load data from XML string
     *
     * @param string $sPluginXmlString
     * @param array  $aData
     */
    public function loadFromXml($sPluginXmlString, $aData = null) {

        if ($this->oXml = @simplexml_load_string($sPluginXmlString)) {
            if (is_null($aData)) {
                $aData = array(
                    'priority' => 0,
                );
            }

            if ($sId = (string)$this->oXml->id) {
                $aData['id'] = $sId;
            }
            $sPriority = trim($this->oXml->priority);
            if ($sPriority) {
                if (is_numeric($sPriority)) {
                    $sPriority = intval($sPriority);
                } else {
                    $sPriority = strtolower($sPriority);
                }
            } else {
                $sPriority = 0;
            }
            $aData['priority'] = $sPriority;
            $aData['property'] = $this->oXml;

            $this->setProps($aData);
        }
    }

    /**
     * Получает значение параметра из XML на основе языковой разметки
     *
     * @param SimpleXMLElement $oXml         - XML узел
     * @param string           $sProperty    - Свойство, которое нужно вернуть
     * @param string|array     $xLang        - Название языка
     * @param bool             $bHtml        - HTML или текст.
     *                                              Если предполагается, что в свойстве текст, то текст преобразуются
     *                                              htmlentites(). Таким образом, чтобы можно просто вывести текст без всяких фильтров, чтобы
     *                                              увидеть html-сущности в виде текста. Либо можно использовать htmlspecialchars перед выводом, чтобы
     *                                              отобразить текст как он написан внутри свойства.
     *
     */
    protected function _xlang($oXml, $sProperty, $xLang, $bHtml = false) {

        $sProperty = trim($sProperty);

        $aData = array();
        if (is_array($xLang)) {
            foreach ($xLang as $sLang) {
                if (count($aData = $oXml->xpath("{$sProperty}/lang[@name='{$sLang}']"))) {
                    break;
                }
                if (!count($aData)) {
                    $aData = $oXml->xpath("{$sProperty}/lang[@name='default']");
                }
            }
        } else {
            if (!count($aData = $oXml->xpath("{$sProperty}/lang[@name='{$xLang}']"))) {
                $aData = $oXml->xpath("{$sProperty}/lang[@name='default']");
            }
        }

        $sText = trim((string)array_shift($aData));
        if ($sText) {
            $oXml->$sProperty->data = ($bHtml ? E::ModuleText()->Parser($sText) : htmlentities($sText, ENT_QUOTES, 'UTF-8'));
        } else {
            $oXml->$sProperty->data = '';
        }
    }

    /**
     * @param null|string $sProp
     *
     * @return array
     */
    protected function _getXmlProperty($sProp = null) {

        if (is_null($sProp)) {
            return $this->_aData['property'];
        } else {
            return $this->_aData['property']->$sProp;
        }
    }

    /**
     * @param string $sName
     *
     * @return string
     */
    protected function _getXmlLangProperty($sName) {

        $sResult = $this->getProp($sName);
        if (is_null($sResult)) {
            $aLangs = E::ModuleLang()->getLangAliases(true);
            $this->_xlang($this->oXml, $sName, $aLangs);
            $xProp = $this->_getXmlProperty($sName);
            if ($xProp->data) {
                $sResult = (string)$xProp->data;
            } else {
                $sResult = (string)$xProp->lang;
            }
            $this->setProp($sName, $sResult);
        }
        return $sResult;
    }

    /**
     * @param bool $bEncode
     *
     * @return string
     */
    public function getId($bEncode = false) {

        $sResult = $this->getProp('id');
        if ($bEncode) {
            $sResult = E::ModulePlugin()->encodeId($sResult);
        }

        return $sResult;
    }

    /**
     * @return string|null
     */
    public function getManifestFile() {

        return $this->getProp('manifest');
    }

    /**
     * @return string
     */
    public function getName() {

        return $this->_getXmlLangProperty('name');
    }

    /**
     * @return string
     */
    public function getDescription() {

        return $this->_getXmlLangProperty('description');
    }

    /**
     * @return string
     */
    public function getAuthor() {

        return $this->_getXmlLangProperty('author');
    }

    /**
     * @return string
     */
    public function getPluginClass() {

        return Plugin::GetPluginClass($this->getId());
    }

    /**
     * @return null|string
     */
    public function getPluginClassFile() {

        $sManifest = $this->getManifestFile();
        $sClassName = $this->getPluginClass();
        if ($sManifest && $sClassName) {
            return dirname($sManifest) . '/' . $sClassName . '.class.php';
        }
        return null;
    }

    /**
     * @return string
     */
    public function getAdminClass() {

        $aAdminPanel = $this->getProp('adminpanel');
        if (isset($aAdminPanel['class']))
            return $aAdminPanel['class'];
        else {
            return 'Plugin' . ucfirst($this->getId()) . '_ActionAdmin';
        }
    }

    /**
     * @return bool
     */
    public function hasAdminpanel() {

        $sClass = $this->getAdminClass();
        try {
            if (class_exists($sClass, true)) {
                return true;
            }
        } catch (Exception $e) {
            //if (class_exists())
        }
        return false;
    }

    /**
     * @return array|bool
     */
    public function getAdminMenuEvents() {

        if ($this->isActive()) {
            $aEvents = array();
            $sPluginClass = $this->getPluginClass();
            $aProps = (array)(new $sPluginClass);
            if (isset($aProps['aAdmin']) && is_array($aProps['aAdmin']) && isset($aProps['aAdmin']['menu'])) {
                foreach ((array)$aProps['aAdmin']['menu'] as $sEvent => $sClass) {
                    if (substr($sClass, 0, 1) == '_') {
                        $sClass = $sPluginClass . $sClass;
                    }
                    if (!preg_match('/Plugin([A-Z][a-z0-9]+)_(\w+)/', $sClass)) {
                        // nothing
                    }
                    $aEvents[$sEvent] = $sClass;
                }
            }
            return $aEvents;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getVersion() {

        return (string)$this->_getXmlProperty('version');
    }

    /**
     * @return string
     */
    public function getHomepage() {

        $sResult = $this->getProp('homepage');
        if (is_null($sResult)) {
            $sResult = E::ModuleText()->Parser((string)$this->_getXmlProperty('homepage'));
            $this->setProp('homepage', $sResult);
        }
        return $sResult;
    }

    /**
     * @return string
     */
    public function getSettings() {

        $sResult = $this->getProp('settings');
        if (is_null($sResult)) {
            $sResult = preg_replace('/{([^}]+)}/', R::GetLink('$1'), $this->oXml->settings);
            $this->setProp('settings', $sResult);
        }
        return $sResult;
    }

    /**
     * @return string
     */
    public function getDirname() {

        $sResult = (string)$this->_getXmlProperty('dirname');
        return $sResult ? $sResult : $this->getId();
    }

    /**
     * @return string
     */
    public function getEmail() {

        return (string)$this->_getXmlProperty('author')->email;
    }

    /**
     * @param $bFlag
     *
     * @return Entity
     */
    public function setActive($bFlag) {

        return $this->setProp('is_active', (bool)$bFlag);
    }
    /**
     * @return bool
     */
    public function isActive() {

        return (bool)$this->getProp('is_active');
    }

    /**
     * @return bool
     */
    public function isTop() {

        return ($sVal = $this->getPriority()) && strtolower($sVal) == 'top';
    }

    /**
     * @return array
     */
    public function requires() {

        return $this->_getXmlProperty('requires');
    }

    /**
     * @return string
     */
    public function requiredAltoVersion() {

        $oRequires = $this->requires();
        $sAltoVersion = (string)$oRequires->alto->version;
        if (!$sAltoVersion) {
            $sAltoVersion = (string)$oRequires->alto;
        }
        return $sAltoVersion;
    }

    /**
     * @return string
     */
    public function requiredPhpVersion() {

        $oRequires = $this->requires();
        if ($oRequires->system && $oRequires->system->php) {
            return (string)$oRequires->system->php;
        }
        return '';
    }

    /**
     * @return array|SimpleXmlElement
     */
    public function requiredPlugins() {

        $oRequires = $this->requires();
        if ($oRequires->plugins) {
            return $oRequires->plugins->children();
        }
        return array();
    }

    /**
     * @return bool
     */
    public function engineCompatible() {

        $oRequires = $this->requires();

        $sLsVersion = (string)$oRequires->livestreet;
        $sAltoVersion = (string)$oRequires->alto->version;
        if (!$sAltoVersion)
            $sAltoVersion = (string)$oRequires->alto;

        if ($sAltoVersion) {
            return version_compare($sAltoVersion, ALTO_VERSION, '<=');
        } else {
            return version_compare($sLsVersion, LS_VERSION, '<=');
        }
    }
}

// EOF
