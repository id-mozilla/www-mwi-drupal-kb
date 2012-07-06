<?php

function wikiplugin_pluginmanager_help() {
	return tra("Displays a list of plugins available in this wiki.").":<br />~np~{PLUGINMANAGER(info=>version|description|arguments)}{PLUGINMANAGER}~/np~";
}
/**
* Include the library {@link PluginsLib}
*/
require_once "lib/wiki/pluginslib.php";
/**
* Plugin Manager
* Displays a list of plugins available in this wiki.
*
* Params:
* <ul>
* <li>info (allows multiple columns, joined by '|') : version,description,arguments
*           . By default, selected all.
* </ul>
*
* @package Tikiwiki
* @subpackage TikiPlugins
* @author Claudio Bustos
* @version $Revision: 1.11 $
*/
class WikiPluginPluginManager extends PluginsLib {
    var $expanded_params = array("info");
    function getDefaultArguments() {
        return array('info' => "version|description|arguments");
    }
    function getName() {
        return "PluginManager";
    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
            "\$Revision: 1.11 $");
    }
    function getDescription() {
        return wikiplugin_pluginmanager_help();
    }
    function run($data, $params) {
        global $wikilib, $helpurl, $tikilib;
        if (!is_dir(PLUGINS_DIR)) {
            return $this->error("No plugins directory defined");
        }
        $params = $this->getParams($params);
        extract($params,EXTR_SKIP);
        $aPlugins = $wikilib->list_plugins();
        $aData=array();
        foreach($aPlugins as $sPluginFile) {
            preg_match("/wikiplugin_(.*)\.php/i", $sPluginFile, $match);
            $sPlugin= $match[1];
            include_once(PLUGINS_DIR.'/'.$sPluginFile);
            // First, locate the new format ;)
    			$infoPlugin = $tikilib->plugin_info($sPlugin);
            if (class_exists("WikiPlugin".$sPlugin)) {
                $sClassName="WikiPlugin".$sPlugin;
                $oClass=new $sClassName();
                if (method_exists($oClass,'getName')) {
                    $sPlugin=$oClass->getName();
                } elseif (isset($infoPlugin['name'])) {
    					$sPlugin=$infoPlugin['name'];
    				}
                if (method_exists($oClass,'getDescription')) {
    					$aData[$sPlugin]["description"]=$this->processDescription($oClass->getDescription());
    				} elseif (isset($infoPlugin['description'])) {
    					$aData[$sPlugin]["description"]=$infoPlugin['description'];
    				} else {
    					$aData[$sPlugin]["description"]="---";
    				}
                if (method_exists($oClass,'getVersion')) {
                    $aData[$sPlugin]["version"]=$oClass->getVersion();
                } else {
                    $aData[$sPlugin]["version"]=" -- ";
                }
                if (method_exists($oClass,'getDefaultArguments')) {
    					$aParams=$oClass->getDefaultArguments();
    				} else {
    					$aParams=array();
    				}
                    $aData[$sPlugin]["arguments"]="";
                    foreach ($aParams as $arg => $default) {
                    if (stristr($default, ' ')) {
                    $default = "'$default'";}
                    if ($default==="[pagename]") {
                        $default="[[pagename]";
                    }
                    $aData[$sPlugin]["arguments"].=$arg." => ".$default."<br \>";
                    }
                unset($oClass);
            } else {
                    $sFuncName="wikiplugin_".$sPlugin."_help";
                    if (function_exists($sFuncName)) {
                        $sDescription=$this->processDescription($sFuncName());
                    } else {
                        $sDescription= " --- ";
                    }
                    $aData[$sPlugin]["description"] =$sDescription;
                    $aData[$sPlugin]["version"] = tra("No version indicated");
                    $aData[$sPlugin]["arguments"] = tra("No arguments indicated");
                }
                $aData[$sPlugin]["plugin"] = "[".$helpurl."Plugin".ucfirst($sPlugin)."|". strtoupper($sPlugin)."]";
            } // Plugins Loop
        return PluginsLibUtil::createTable($aData,$info,array("field"=>"plugin","name"=>"Plugin"));
    }
    function processDescription($sDescription) {
        $sDescription=str_replace(",",", ",$sDescription);
        $sDescription=str_replace("|","| ",$sDescription);
        $sDescription=strip_tags(wordwrap($sDescription,35));
        return $sDescription;
    }
}

    function wikiplugin_pluginmanager_info() {
    	return array(
    		'name' => tra('Plugin Manager'),
    		'documentation' => 'PluginManager',
    		'description' => tra("Displays a list of plugins available in this wiki."),
    		'prefs' => array( 'wikiplugin_pluginmanager' ),
    		'params' => array(
    			'info' => array(
    				'required' => false,
    				'name' => tra('Information'),
    				'description' => 'version|description|arguments '.tra('Multiple values separated with | can be used.'),
    			),
    		),
    	);
    }

function wikiplugin_pluginmanager($data, $params) {
    $plugin = new WikiPluginPluginManager();
    return $plugin->run($data, $params);
}
