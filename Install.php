<?php
/*
 * Standardized utilities that XenForo Addons use.
 * https://github.com/Xon/XenForo-Utils
 * Original code copyright 2015 Xon
 * Released under the MIT license.
 */

class SV_Utils_Install
{
    protected static function _verifyMapping($actualMappingObj, array $expectedMapping)
    {
        foreach ($expectedMapping AS $name => $value)
        {
            if (!isset($actualMappingObj->$name))
            {
                return true;
            }

            if (is_array($value))
            {
                if (self::_verifyMapping($actualMappingObj->$name, $value))
                {
                    return true;
                }
            }
            else if ($value === 'yes')
            {
                if ($actualMappingObj->$name !== true && $actualMappingObj->$name !== 'yes')
                {
                    return true;
                }
            }
            else if ($value === 'no')
            {
                if ($actualMappingObj->$name !== false && $actualMappingObj->$name !== 'no')
                {
                    return true;
                }
            }
            else if ($actualMappingObj->$name !== $value)
            {
                return true;
            }
        }

        return false;
    }

    public static function getOptimizableMappings(XenES_Model_Elasticsearch $XenEs, array $mappingTypes)
    {
        $mappings = $XenEs->getMappings();

        $optimizable = array();

        foreach ($mappingTypes AS $type => $extra)
        {
            if (!$mappings || !isset($mappings->$type)) // no index or no mapping
            {
                $optimize = true;
            }
            else
            {
                $mapping = XenForo_Application::mapMerge(XenES_Model_Elasticsearch::$optimizedGenericMapping, $extra);
                $optimize = self::_verifyMapping($mappings->$type, $mapping);
            }

            if ($optimize)
            {
                $optimizable[] = $type;
            }
        }

        return $optimizable;
    }

    public static function updateXenEsMapping(array $requireIndexing, array $mappings)
    {
        if (XenForo_Application::get('options')->enableElasticsearch && $XenEs = XenForo_Model::create('XenES_Model_Elasticsearch'))
        {
            $optimizable = self::getOptimizableMappings($XenEs, $mappings);
            foreach ($optimizable AS $type)
            {
                if (isset($mappings[$type]))
                {
                    $XenEs->optimizeMapping($type, false, $mappings[$type]);
                    $requireIndexing[$type] = true;
                }
            }
        }

        if($requireIndexing)
        {
            $types = array();
            foreach($requireIndexing as $type => $null)
            {
                $types[] = new XenForo_Phrase($type);
            }

            XenForo_Error::logException(new Exception("Please rebuild the search index for the content types: " . implode(', ', $types) ), false);
        }
    }

    public static function removeOldAddons($addonsToUninstall)
    {
        $options = XenForo_Application::getOptions();
        $addonModel = XenForo_Model::create("XenForo_Model_AddOn");
        foreach($addonsToUninstall as $addonToUninstall => $keys)
        {
            $addon = $addonModel->getAddOnById($addonToUninstall);
            if (!empty($addon))
            {
                if(!empty($keys))
                foreach($keys as $old => $new)
                {
                    $val = $options->$old;
                    $options->set($new, $val);
                    $dw = XenForo_DataWriter::create('XenForo_DataWriter_Option', XenForo_DataWriter::ERROR_SILENT);
                    if ($dw->setExistingData($new))
                    {
                        $dw->set('option_value', $val);
                        $dw->save();
                    }
                }

                $dw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
                $dw->setExistingData($addonToUninstall);
                $dw->delete();
            }
        }
    }

    public static function modifyColumn($table, $column, $oldDefinition, $definition)
    {
        $db = XenForo_Application::get('db');
        $hasColumn = false;
        if (empty($oldDefinition))
        {
            $hasColumn = $db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column);
        }
        else
        {
            $hasColumn = $db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ? and Type = ?', array($column,$oldDefinition));
        }

        if($hasColumn)
        {
            $db->query('ALTER TABLE `'.$table.'` MODIFY COLUMN `'.$column.'` '.$definition);
            return true;
        }
        return false;
    }

    public static function dropColumn($table, $column)
    {
        $db = XenForo_Application::get('db');
        if ($db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column))
        {
            $db->query('ALTER TABLE `'.$table.'` drop COLUMN `'.$column.'` ');
            return true;
        }
        return false;
    }

    public static function addColumn($table, $column, $definition)
    {
        $db = XenForo_Application::get('db');
        if (!$db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column))
        {
            $db->query('ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$definition);
            return true;
        }
        return false;
    }

    public static function addIndex($table, $index, array $columns)
    {
        $db = XenForo_Application::get('db');
        if (!$db->fetchRow('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', $index))
        {
            $cols = '(`'. implode('`,`', $columns). '`)';
            $db->query('ALTER TABLE `'.$table.'` add index `'.$index.'` '. $cols);
            return true;
        }
        return false;
    }

    public static function dropIndex($table, $index)
    {
        $db = XenForo_Application::get('db');
        if ($db->fetchRow('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', $index))
        {
            $db->query('ALTER TABLE `'.$table.'` drop index `'.$index.'` ');
            return true;
        }
        return false;
    }

    public static function renameColumn($table, $old_name, $new_name, $definition)
    {
        $db = XenForo_Application::get('db');
        if ($db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $old_name) &&
            !$db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $new_name))
        {
            $db->query('ALTER TABLE `'.$table.'` CHANGE COLUMN `'.$old_name.'` `'.$new_name.'` '. $definition);
            return true;
        }
        return false;
    }
}
