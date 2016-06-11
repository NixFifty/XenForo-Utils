<?php

class SV_Utils_ElasticSearch
{
    public static function _verifyMapping($actualMappingObj, array $expectedMapping)
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

    public static function getOptimizableMappings(array $mappingTypes, $mappings, array $extraMappings)
    {
        $optimizable = array();

        foreach ($mappingTypes AS $type)
        {
            if (!$mappings || !isset($mappings->$type)) // no index or no mapping
            {
                $optimize = true;
            }
            else
            {
                $optimizedGenericMapping = XenES_Model_Elasticsearch::$optimizedGenericMapping;
                if (!empty($extraMappings[$type]))
                {
                    $optimizedGenericMapping = array_merge($optimizedGenericMapping, $extraMappings[$type]);
                }
                $optimize = static::_verifyMapping($mappings->$type, $optimizedGenericMapping);
            }

            if ($optimize)
            {
                $optimizable[] = $type;
            }
        }

        return $optimizable;
    }
}