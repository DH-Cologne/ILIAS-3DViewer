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

    public function executeCommand()
    {
        global $tpl;


        $next_class = $this->ctrl->getNextClass($this);
        switch ($next_class) {
            case 'ilexportgui':
                // only if plugin supports it?
                $tpl->setTitle($this->object->getTitle());
                $tpl->setTitleIcon(ilObject::_getIcon($this->object->getId()));
                $this->setLocator();
                $tpl->getStandardTemplate();
                $this->setTabs();
                include_once './Services/Export/classes/class.ilExportGUI.php';
                $this->tabs->activateTab("export");
                $exp = new ilExportGUI($this);
                $exp->addFormat('xml');
                $this->ctrl->forwardCommand($exp);
                $tpl->show();
                return;
                break;
        }

        $return_value = parent::executeCommand();

        return $return_value;
    }

    /**
     * Set tabs
     */
    function setTabs()
    {
        global $ilCtrl, $ilAccess;

        // tab for the "show content" command
        if ($ilAccess->checkAccess("read", "", $this->object->getRefId())) {
            $this->tabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
        }

        // standard info screen tab
        $this->addInfoTab();

        // a "properties" tab
        if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
            $this->tabs->addTab("export", $this->txt("export"), $ilCtrl->getLinkTargetByClass("ilexportgui", ""));
        }

        // standard permission tab
        $this->addPermissionTab();
        $this->activateTab();
    }

    /**
     * We need this method if we can't access the tabs otherwise...
     */
    private function activateTab()
    {
        $next_class = $this->ctrl->getCmdClass();

        switch ($next_class) {
            case 'ilexportgui':
                $this->tabs->activateTab("export");
                break;
        }

        return;
    }

    /**
     * Get type.
     */
    final function getType()
    {
        return il3DViewerPlugin::ID;
    }

    /**
     * Handles all commmands of this class, centralizes permission checks
     */
    function performCommand($cmd)
    {
        switch ($cmd) {
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
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd()
    {
        return "editProperties";
    }

//
// DISPLAY TABS
//

    /**
     * Initialisation
     */
    protected function afterConstructor()
    {
        global $ilCtrl, $ilTabs, $tpl;
        $this->ctrl = $ilCtrl;
        $this->tabs = $ilTabs;
        $this->tpl = $tpl;
    }

    /**
     * Edit Properties. This commands uses the form class to display an input form.
     */
    protected function editProperties()
    {
        $this->tabs->activateTab("properties");
        $form = $this->initPropertiesForm();
        $this->addValuesToForm($form);
        $this->tpl->setContent($form->getHTML());
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

        $description = new ilTextInputGUI($this->plugin->txt("description"), "description");
        $form->addItem($description);

        $online = new ilCheckboxInputGUI($this->plugin->txt("online"), "online");
        $form->addItem($online);

        $form->setFormAction($this->ctrl->getFormAction($this, "saveProperties"));
        $form->addCommandButton("saveProperties", $this->plugin->txt("update"));

        return $form;
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
     *
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

    protected function showContent()
    {
        $this->tabs->activateTab("content");

        /** @var ilObj3DViewer $object */
        $object = $this->object;

        $form = new ilPropertyFormGUI();
        $form->setTitle($object->getTitle());

        $i = new ilNonEditableValueGUI($this->plugin->txt("title"));
        $i->setInfo($object->getTitle());
        $form->addItem($i);

        $i = new ilNonEditableValueGUI($this->plugin->txt("description"));
        $i->setInfo($object->getDescription());
        $form->addItem($i);

        $i = new ilNonEditableValueGUI($this->plugin->txt("online_status"));
        $i->setInfo($object->isOnline() ? "Online" : "Offline");
        $form->addItem($i);

        global $ilUser;
        $progress = new ilLPStatusPlugin($this->object->getId());
        $status = $progress->determineStatus($this->object->getId(), $ilUser->getId());
        $i = new ilNonEditableValueGUI($this->plugin->txt("lp_status"));
        $i->setInfo($this->plugin->txt("lp_status_" . $status));
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo("<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToCompleted") . "'> " . $this->plugin->txt("set_completed"));
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo("<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToNotAttempted") . "'> " . $this->plugin->txt("set_not_attempted"));
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo("<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToFailed") . "'> " . $this->plugin->txt("set_failed"));
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo("<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToInProgress") . "'> " . $this->plugin->txt("set_in_progress"));
        $form->addItem($i);

        $i = new ilNonEditableValueGUI($this->plugin->txt("important"));
        $i->setInfo($this->plugin->txt("lp_status_info"));
        $form->addItem($i);

        $this->tpl->setContent($form->getHTML());
    }

    protected function showExport()
    {
        require_once("./Services/Export/classes/class.ilExportGUI.php");
        $export = new ilExportGUI($this);
        $export->addFormat("xml");
        $ret = $this->ctrl->forwardCommand($export);

    }

    protected function setStatusToFailed()
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_FAILED_NUM);
    }

    protected function setStatusToInProgress()
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
    }

    protected function setStatusToNotAttempted()
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
    }

    private function setStatusToCompleted()
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_COMPLETED_NUM);
    }

    private function setStatusAndRedirect($status)
    {
        global $ilUser;
        $_SESSION[self::LP_SESSION_ID] = $status;
        ilLPStatusWrapper::_updateStatus($this->object->getId(), $ilUser->getId());
        $this->ctrl->redirect($this, $this->getStandardCmd());
    }

    /**
     * Get standard command
     */
    function getStandardCmd()
    {
        return "showContent";
    }
}

?>