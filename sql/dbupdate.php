<#1>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'is_online' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    ),
    'repository_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => false
    ),
    'repository_passcode' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => false
    )
);
if (!$ilDB->tableExists("rep_robj_x3dv_data")) {
    $ilDB->createTable("rep_robj_x3dv_data", $fields);
    $ilDB->addPrimaryKey("rep_robj_x3dv_data", array("id"));
}
?>