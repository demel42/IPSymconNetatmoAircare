<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/local.php';   // lokale Funktionen

class NetatmoAircareConfig extends IPSModule
{
    use NetatmoAircareCommonLib;
    use NetatmoAircareLocalLib;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('ImportCategoryID', 0);

        $this->ConnectParent('{070C93FD-9D19-D670-2C73-20104B87F034}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $refs = $this->GetReferenceList();
        foreach ($refs as $ref) {
            $this->UnregisterReference($ref);
        }
        $propertyNames = ['ImportCategoryID'];
        foreach ($propertyNames as $name) {
            $oid = $this->ReadPropertyInteger($name);
            if ($oid > 0) {
                $this->RegisterReference($oid);
            }
        }

        $this->SetStatus(IS_ACTIVE);
    }

    private function SetLocation()
    {
        $category = $this->ReadPropertyInteger('ImportCategoryID');
        $tree_position = [];
        if ($category > 0 && IPS_ObjectExists($category)) {
            $tree_position[] = IPS_GetName($category);
            $parent = IPS_GetObject($category)['ParentID'];
            while ($parent > 0) {
                if ($parent > 0) {
                    $tree_position[] = IPS_GetName($parent);
                }
                $parent = IPS_GetObject($parent)['ParentID'];
            }
            $tree_position = array_reverse($tree_position);
        }
        return $tree_position;
    }

    private function buildEntry($guid, $product_type, $product_id, $product_name, $product_category)
    {
        $instID = 0;
        $instIDs = IPS_GetInstanceListByModuleID($guid);
        foreach ($instIDs as $id) {
            $prodID = IPS_GetProperty($id, 'product_id');
            if ($prodID == $product_id) {
                $instID = $id;
                break;
            }
        }

        $entry = [
            'category'   => $this->Translate($product_category),
            'name'       => $product_name,
            'product_id' => $product_id,
            'instanceID' => $instID,
            'create'     => [
                'moduleID'       => $guid,
                'location'       => $this->SetLocation(),
                'info'           => $product_name,
                'configuration'  => [
                    'product_type' => $product_type,
                    'product_id'   => $product_id,
                ]
            ]
        ];

        return $entry;
    }

    private function getConfiguratorValues()
    {
        $entries = [];

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            $this->LogMessage('has no active parent instance', KL_WARNING);
            return $entries;
        }

        $SendData = ['DataID' => '{076043C4-997E-6AB3-9978-DA212D50A9F5}', 'Function' => 'LastData'];
        $data = $this->SendDataToParent(json_encode($SendData));
        $this->SendDebug(__FUNCTION__, 'data=' . $data, 0);

        if ($data != '') {
            $jdata = json_decode($data, true);
            if (isset($jdata['body']['devices'])) {
                $devices = $jdata['body']['devices'];
                foreach ($devices as $device) {
                    $this->SendDebug(__FUNCTION__, 'device=' . print_r($device, true), 0);
                    if (!isset($device['_id'])) {
                        continue;
                    }
                    $product_id = $device['_id'];
                    $product_name = $device['station_name'];
                    $product_type = $device['type'];
                    switch ($product_type) {
                        case 'NHC':
                            $guid = '{F3940032-CC4B-9E69-383A-6FFAD13C5438}';
                            $product_category = 'Room air sensor';
                            break;
                        default:
                            $guid = '';
                            break;
                    }
                    if ($guid == '') {
                        $this->SendDebug(__FUNCTION__, 'ignore camera ' . $camera['id'] . ': unsupported type ' . $camera['type']);
                        continue;
                    }

                    $entry = $this->buildEntry($guid, $product_type, $product_id, $product_name, $product_category);
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    private function GetFormElements()
    {
        $formElements = [];

        if ($this->HasActiveParent() == false) {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => 'Instance has no active parent instance',
            ];
        }

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'category for products to be created:'
        ];
        $formElements[] = [
            'type'    => 'SelectCategory',
            'name'    => 'ImportCategoryID',
            'caption' => 'category'
        ];

        $entries = $this->getConfiguratorValues();
        $formElements[] = [
            'type'    => 'Configurator',
            'name'    => 'products',
            'caption' => 'Products',

            'rowCount' => count($entries),

            'add'    => false,
            'delete' => false,
            'sort'   => [
                'column'    => 'name',
                'direction' => 'ascending'
            ],
            'columns' => [
                [
                    'caption' => 'Category',
                    'name'    => 'category',
                    'width'   => '200px',
                ],
                [
                    'caption' => 'Name',
                    'name'    => 'name',
                    'width'   => 'auto'
                ],
                [
                    'caption' => 'Id',
                    'name'    => 'product_id',
                    'width'   => '200px'
                ]
            ],
            'values' => $entries,
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        return $formActions;
    }
}
