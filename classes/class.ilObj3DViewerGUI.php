<?php

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");
require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Form/classes/class.ilTextInputGUI.php");
require_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");
require_once("./Services/Tracking/classes/class.ilLearningProgress.php");
require_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
require_once("./Services/Tracking/classes/status/class.ilLPStatusPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/classes/class.il3DViewerPlugin.php");
require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Form/classes/class.ilNonEditableValueGUI.php");

/**
 * @ilCtrl_isCalledBy ilObj3DViewerGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObj3DViewerGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObj3DViewerGUI extends ilObjectPluginGUI
{
    const LP_SESSION_ID = 'x3dv_lp_session_state';
    /** @var  ilTemplate */
    public $tpl;
    /** @var  ilCtrl */
    protected $ctrl;
    /** @var  ilTabsGUI */
    protected $tabs;


    /**
     * Set tabs
     */
    function setTabs()
    {
        global $ilTabs, $ilCtrl, $ilAccess;

        // tab for the "show content" command
        $ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));

        // standard info screen tab
        $this->addInfoTab();

        // a "properties" tab
        $ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));

        // standard permission tab
        $this->addPermissionTab();
    }

    /**
     * Get type.
     */
    final function getType()
    {
        return il3DViewerPlugin::PLUGIN_ID;
    }

    /**
     * Handles all commmands of this class, centralizes permission checks
     */
    function performCommand($cmd)
    {
        switch ($cmd) {
            case "editProperties":
            case "updateProperties":
                $this->$cmd();
                break;
            case "showContent":
                $this->$cmd();
                break;
        }
    }

    /**
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd()
    {
        return "editProperties";
    }

    function getStandardCmd()
    {
        return "showContent";
    }

//
// DISPLAY TABS
//

    /**
     * Initialisation
     */
    protected function afterConstructor()
    {

    }

    /** TODO: Handle Properties (if needed)
     * Edit Properties. This commands uses the form class to display an input form.
     */
    protected function editProperties()
    {
        /*global $tpl, $ilTabs;

        $ilTabs->activateTab("properties");
        $this->initPropertiesForm();
        $this->getPropertiesValues();
        $tpl->setContent($this->form->getHTML());*/
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

        $form->setFormAction($this->ctrl->getFormAction($this, "saveProperties"));
        $form->addCommandButton("saveProperties", $this->plugin->txt("update"));

        return $form;
    }

    /** TODO: Handle Properties (if needed)
     * Get values for edit properties form
     */
    function getPropertiesValues()
    {
        //$values["title"] = $this->object->getTitle();
        //$values["desc"] = $this->object->getDescription();
        //$values["online"] = $this->object->getOnline();

        //$this->form->setValuesByArray($values);
    }

    /** TODO: Handle Properties (if needed)
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
        ));
    }

    /**
     * TODO: Handle Properties (if needed)
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
    }

    /* Author: Kai Niebes
     * - Set the 3D Viewers URL
     * this URL will be inserted into the HTML Template
     * - Load external JavaScript
     * for testing purposes, can later be used to issue requests to the 3D Viewer REST API
     * - Access information of the logged-in User
     */
    protected function showContent()
    {
        global $ilUser, $lng;


        $x3dv_url = 'https://blacklodge.hki.uni-koeln.de/builds/Kompakkt/live/';
        /** @var ilObj3DViewer $object */
        $this->tabs->activateTab("content");
        //$this->tpl->addCss("");
        $this->tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/js/il3DViewer.js");

        $tx3dv = new ilTemplate("tpl.x3dv.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer");
        $tx3dv->setVariable("USER_NAME", rawurlencode($ilUser->firstname . ' ' . $ilUser->lastname));
        $tx3dv->setVariable("LANGUAGE", $lng->getUserLanguage());
        $tx3dv->setVariable("X3DV_URL", $x3dv_url);
        $this->tpl->setContent($tx3dv->get());
    }
}

?>