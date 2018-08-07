<?php

require_once("./Services/Export/classes/class.ilXmlImporter.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/classes/class.ilObj3DViewer.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/3DViewer/classes/class.il3DViewerPlugin.php");

/**
 * Class il3DViewerImporter
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class il3DViewerImporter extends ilXmlImporter
{

    /**
     * Import xml representation
     *
     * @param    string        entity
     * @param    string        target release
     * @param    string        id
     * @return    string        xml string
     */
    public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)
    {
        $xml = simplexml_load_string($a_xml);
        $pl = new il3DViewerPlugin();
        $entity = new ilObj3DViewer();
        $entity->setTitle((string)$xml->title . " " . $pl->txt("copy"));
        $entity->setDescription((string)$xml->description);
        $entity->setOnline((string)$xml->online);
        $entity->setImportId($a_id);
        $entity->create();
        $new_id = $entity->getId();
        $a_mapping->addMapping("Plugins/TestObjectRepository", "xtst", $a_id, $new_id);
        return $new_id;
    }
}