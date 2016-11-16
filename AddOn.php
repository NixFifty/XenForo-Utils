<?php
/*
 * Standardized utilities that XenForo Addons use.
 * https://github.com/Xon/XenForo-Utils
 * Original code copyright 2016 Xon
 * Released under the MIT license.
 */

class SV_Utils_AddOn
{
    public static function addOnIsActive($addOnId)
    {
        if (XenForo_Application::isRegistered('addOns')) 
        {
            return array_key_exists($addOnId, XenForo_Application::get('addOns'));
        }
        return false;
    }

    public static function removeOldAddOns($addonsToUninstall, $disableUninstaller)
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
                if ($disableUninstaller)
                {
                    $dw->set('uninstall_callback_class','');
                    $dw->set('uninstall_callback_method','');
                }
                $dw->delete();
            }
        }
    }
}
