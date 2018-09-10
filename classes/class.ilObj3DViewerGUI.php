<?php

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");
require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Form/classes/class.ilTextInputGUI.php");
require_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");
require_once("./Services/Tracking/classes/class.ilLearningProgress.php");
require_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
require_once("./Services/Tracking/classes/status/class.ilLPStatusPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/classes/class.il3DViewerPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/classes/class.ilObj3DViewer.php");
require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Form/classes/class.ilNonEditableValueGUI.php");

/**
 * @ilCtrl_isCalledBy ilObj3DViewerGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObj3DViewerGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObj3DViewerGUI extends ilObjectPluginGUI
{    
    /**
     * Initialisation
     */
    protected function afterConstructor()
    {

    }
    /**
     * Handles all commmands of this class, centralizes permission checks
     */
    function performCommand($cmd)
    {
        switch ($cmd)
        {
            case "editProperties":   // list all commands that need write permission here
            case "updateProperties":
            case "saveProperties":
            case "showExport":
                $this->checkPermission("write");
                $this->$cmd();
                break;
 
            case "showContent":   // list all commands that need read permission here
            case "setStatusToCompleted":
            case "setStatusToFailed":
            case "setStatusToInProgress":
            case "setStatusToNotAttempted":
                $this->checkPermission("read");
                $this->$cmd();
                break;
        }
    }
 
    /**
     * Get type.
     */
    final function getType()
    {
        return "x3dv";
    }
    /**
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd()
    {
        return "editProperties";
    }
    /**
     * Get standard command
     */
    function getStandardCmd()
    {
        return "showContent";
    }    
//
// DISPLAY TABS
//
    /** TODO: Handle Properties (if needed)
     * Edit Properties. This commands uses the form class to display an input form.
     */
    protected function editProperties()
    {
        global $tpl, $ilTabs;

        $ilTabs->activateTab("properties");
        $form = $this->initPropertiesForm();
        $this->addValuesToForm($form);
        $tpl->setContent($form->getHTML());
    }

    /**
     * @return ilPropertyFormGUI
     */
    protected function initPropertiesForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->plugin->txt("obj_x3dv"));

        $title = new ilTextInputGUI($this->plugin->txt("title"), "title");
        $title->setRequired(true);
        $form->addItem($title);

        $description = new ilTextInputGUI($this->plugin->txt("description"), "desc");
        $form->addItem($description);

        $online = new ilCheckboxInputGUI($this->plugin->txt("online"), "online");
        $form->addItem($online);

        $repository_id = new ilTextInputGUI($this->plugin->txt("repository_id"), "repository_id");
        $form->addItem($repository_id);

        $repository_passcode = new ilTextInputGUI($this->plugin->txt("repository_passcode"), "repository_passcode");
        $form->addItem($repository_passcode);

        $form->setFormAction($this->ctrl->getFormAction($this, "saveProperties"));
        $form->addCommandButton("saveProperties", $this->plugin->txt("update"));

        return $form;
    }

    /**
     * Update properties
     */
    public function updateProperties()
    {
        global $tpl, $lng, $ilCtrl;

        $this->initPropertiesForm();
        if ($this->form->checkInput()) {
            $this->object->setTitle($this->form->getInput("title"));
            $this->object->setDescription($this->form->getInput("desc"));
            $this->object->setOnline($this->form->getInput("online"));

            $this->object->setRepositoryID($this->form->getInput("repository_id"));
            $this->object->setRepositoryPasscode($this->form->getInput("repository_passcode"));

            $this->object->update();
            $ilCtrl->redirect($this, "editProperties");
        }

        $this->form->setValuesByPost();
        $tpl->setContent($this->form->getHtml());
    }

    /**
     * @param $form ilPropertyFormGUI
     */
    protected function addValuesToForm(&$form)
    {
        $form->setValuesByArray(array(
            "title" => $this->object->getTitle(),
            "description" => $this->object->getDescription(),
            "online" => $this->object->isOnline(),
            "repository_id" => $this->object->getRepositoryIDString(),
            "repository_passcode" => $this->object->getRepositoryPasscodeString()
        ));
    }

    /**
     * Save Properties
     */
    protected function saveProperties()
    {
        $form = $this->initPropertiesForm();
        $form->setValuesByPost();
        if ($form->checkInput()) {
            $this->fillObject($this->object, $form);
            $this->object->update();
            ilUtil::sendSuccess($this->plugin->txt("update_successful"), true);
            $this->ctrl->redirect($this, "editProperties");
        }
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * @param $object ilObj3DViewer
     * @param $form ilPropertyFormGUI
     */
    private function fillObject($object, $form)
    {
        $object->setTitle($form->getInput('title'));
        $object->setDescription($form->getInput('description'));
        $object->setOnline($form->getInput('online'));
        $object->setRepositoryID($form->getInput('repository_id'));
        $object->setRepositoryPasscode($form->getInput('repository_passcode'));
    }

    /**
     * - Displays the 3D Viewer using an HTML Template
     * - Inserts the correct URL, depending on whether
     *     - User wants to load no specific repository (Case: No ID or Passcode)
     *     - User wants to load specific public repository (Case: No Passcode)
     *     - USer wants to load specific private repository (Case: Both ID and Passcode)
     * - Shows the user the 3D Viewer iframe
     */
    protected function showContent()
    {
        // Get ID  and Passcode as String
        $temp_id = $this->object->getRepositoryIDString();
        $temp_passcode = $this->object->getRepositoryPasscodeString();

        // Set vanilla URL
        $base_url = "https://blacklodge.hki.uni-koeln.de/builds/Kompakkt/live/";

        // Find correct case
        if (strlen($temp_passcode) > 0 && strlen($temp_id) > 0) {        
            $x3dv_url = $base_url . "object/" . $temp_id . "/" . $temp_passcode;
        }
        else if (strlen($temp_id) > 0) {
            $x3dv_url = $base_url . "object/" . $temp_id;
        }
        else {
            $x3dv_url = $base_url;
        }

        // Display content and insert HTML
        $this->tabs->activateTab("content");
        $tx3dv = new ilTemplate("tpl.x3dv.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer");
        $tx3dv->setVariable("X3DV_URL", $x3dv_url);
        $this->tpl->setContent($tx3dv->get());
    }    
}

?>