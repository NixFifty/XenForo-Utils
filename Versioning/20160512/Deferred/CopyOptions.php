<?php

class SV_Utils_Versioning_20160512_CopyOptions extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        if (empty($data))
        {
            return false;
        }

        foreach($data as $key => $value)
        {
            $options->set($key, $value);
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
