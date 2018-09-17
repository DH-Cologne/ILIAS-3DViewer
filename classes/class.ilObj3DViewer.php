<?php

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
require_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/classes/class.ilObj3DViewerGUI.php");

/**
 */
class ilObj3DViewer extends ilObjectPlugin implements ilLPStatusPluginInterface
{
    /**
     * Constructor
     *
     * @access        public
     * @param int $a_ref_id
     */
    function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
    }

    /**
     * Set type.
     */
    final function initType()
    {
        $this->setType(il3DViewerPlugin::PLUGIN_ID);
    }

    /**
     * Create object
     */
    function doCreate()
    {
        global $ilDB;

		// Prepare SQL ID Integer Object for identification and empty text objects for repository data
		$temp_id = $ilDB->quote($this->getId(), "integer");
		$temp_online = $ilDB->quote(0, "integer");
		$temp_text = $ilDB->quote("", "text");
		
		$query = "
		INSERT INTO rep_robj_x3dv_data
		            (id,
		             is_online,
		             repository_id,
		             repository_passcode)
		VALUES      ({$temp_id},
		             {$temp_online},
		             {$temp_text},
		             {$temp_text})
		;";
		
        // Run Query
        $ilDB->manipulate($query);
    }

    /**
     * Read data from db
     */
    function doRead()
    {
        global $ilDB;

        $query = "
        SELECT *
        FROM   rep_robj_x3dv_data
        WHERE  id = {$ilDB->quote($this->getId(), "integer")}  
        ";

        $set = $ilDB->query($query);
        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setOnline($rec["is_online"]);
        }
    }

    /**
     * Set online
     *
     * @param        boolean                online
     */
    function setOnline($a_val)
    {
        $this->online = $a_val;
    }

    /**
     * Update data
     */
    function doUpdate()
    {
        global $ilDB;

        $query = "
        UPDATE rep_robj_x3dv_data
        SET    is_online = {$ilDB->quote($this->isOnline(), "integer")}
        WHERE  id = {$ilDB->quote($this->getId(), "integer")}  
        ";

        $ilDB->manipulate($query);
    }

    /**
     * Get online
     *
     * @return        boolean                online
     */
    function isOnline()
    {
        return $this->online;
    }

    /**
     * Delete data from db
     */
    function doDelete()
    {
        global $ilDB;

        $query = "
        DELETE FROM rep_robj_x3dv_data
        WHERE id = {$ilDB->quote($this->getId(), "integer")}  
        ";

        $ilDB->manipulate($query);
    }

    /**
     * Do Cloning
     */
    function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
    {
        global $ilDB;

        $new_obj->setOnline($this->isOnline());
        $new_obj->setRepositoryID($this->getRepositoryIDString());
        $new_obj->setRepositoryPasscode($this->getRepositoryPasscodeString());
        $new_obj->update();
    }

    /**
     * Get all user ids with LP status completed
     *
     * @return array
     */
    public function getLPCompleted()
    {
        return array();
    }

    /**
     * Get all user ids with LP status not attempted
     *
     * @return array
     */
    public function getLPNotAttempted()
    {
        return array();
    }

    /**
     * Get all user ids with LP status failed
     *
     * @return array
     */
    public function getLPFailed()
    {
        return array(6);
    }

    /**
     * Get all user ids with LP status in progress
     *
     * @return array
     */
    public function getLPInProgress()
    {
        return array();
    }

    /**
     * Get current status for given user
     *
     * @param int $a_user_id
     * @return int
     */
    public function getLPStatusForUser($a_user_id)
    {
        global $ilUser;
        if ($ilUser->getId() == $a_user_id)
            return $_SESSION[ilObj3DViewerGUI::LP_SESSION_ID];
        else
            return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
    }

    /**
     * Get 3D Viewer Repository ID
     * @return ilDBStatement Result ID 
     */
    public function getRepositoryID() {
        global $ilDB;

        $query = "
        SELECT repository_id
        FROM   rep_robj_x3dv_data
        WHERE  id = {$ilDB->quote($this->getId(), "integer")}  
        ";

        return $ilDB->query($query);
    }

    /**
     * Sets 3D Viewer Repository ID
     * @param string $repository_id ID of the repository
     */
    public function setRepositoryID($repository_id) {
        global $ilDB;

        $query = "
        UPDATE  rep_robj_x3dv_data
        SET     repository_id = {$ilDB->quote($repository_id,"text")}
        WHERE   id = {$ilDB->quote($this->getId(), "integer")}
        ";

        $ilDB->manipulate($query);
    }

    /**
     * Gets 3D Viewer Repository Passcode
     * @return ilDBStatement Result Passcode
     */
    public function getRepositoryPasscode() {
        global $ilDB;

        $query = "
        SELECT repository_passcode
        FROM   rep_robj_x3dv_data
        WHERE  id = {$ilDB->quote($this->getId(), "integer")}
        ";

        return $ilDB->query($query);
    }

    /**
     * Sets 3D Viewer Repository Passcode
     * @param string $repository_passcode Passcode of the repository
     */
    public function setRepositoryPasscode($repository_passcode) {
        global $ilDB;

        $query = "
        UPDATE  rep_robj_x3dv_data
        SET     repository_passcode = {$ilDB->quote($repository_passcode,"text")}
        WHERE   id = {$ilDB->quote($this->getId(), "integer")}
        ";

        $ilDB->manipulate($query);
    } 

    /**
     * Gets the ID as String
     * @return string ID Result
     */
    public function getRepositoryIDString() {
        global $ilDB;

        return $ilDB->fetchAssoc($this->getRepositoryID())["repository_id"];
    }

    /**
     * Gets the Passcode as String
     * @return string Passcode Result
     */
    public function getRepositoryPasscodeString() {
        global $ilDB;

        return $ilDB->fetchAssoc($this->getRepositoryPasscode())["repository_passcode"];
    }
}

?>
