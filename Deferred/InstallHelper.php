<?php

class SV_Utils_Deferred_InstallHelper extends XenForo_Deferred_Abstract
{
    public static function DeferSettingOptions($options)
    {
        if (empty($options))
        {
            return;
        }
        XenForo_Application::defer(__CLASS__, $options);
    }

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $options = XenForo_Application::getOptions();
        if (empty($data))
        {
            return false;
        }

        foreach($data as $key => $val)
        {
            $options->set($key, $val);
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Option', XenForo_DataWriter::ERROR_SILENT);
            if ($dw->setExistingData($key))
            {
                $dw->set('option_value', $val);
                $dw->save();
            }
        }

        return false;
    }

    public function canCancel()
    {
        return false;
    }
}
