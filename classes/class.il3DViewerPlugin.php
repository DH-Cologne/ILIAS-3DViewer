<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 */
class il3DViewerPlugin extends ilRepositoryObjectPlugin
{
    const ID = "x3dv";
    const PLUGIN_NAME = "3DViewer";

    // must correspond to the plugin subdirectory
    function getPluginName()
    {
        return self::PLUGIN_NAME;
    }

    protected function uninstallCustom()
    {
        // TODO: Nothing to do here.
    }
}

?>