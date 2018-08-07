<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 */
class il3DViewerPlugin extends ilRepositoryObjectPlugin
{
    const ID = "x3dv";

    // must correspond to the plugin subdirectory
    function getPluginName()
    {
        return "3DViewer";
    }

    protected function uninstallCustom()
    {
        // TODO: Nothing to do here.
    }
}

?>