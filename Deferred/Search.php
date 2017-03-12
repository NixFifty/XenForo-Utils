<?php

class NixFifty_Utils_Deferred_Search extends XenForo_Deferred_Abstract
{
    public static function SchemaUpdates(array $requireIndexing = null)
    {
        if (XenForo_Application::getOptions()->enableElasticsearch && $XenEs = XenForo_Model::create('XenES_Model_Elasticsearch'))
        {

            XenForo_Application::defer(__CLASS__, array('requireIndexing' => $requireIndexing));
        }
    }

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $options = XenForo_Application::getOptions();
        if (empty($data))
        {
            return false;
        }
        $requireIndexing = $data['requireIndexing'];

        if(!XenForo_Application::getOptions()->enableElasticsearch )
        {
            return false;
        }
        if(!($XenEs = XenForo_Model::create('XenES_Model_Elasticsearch')))
        {
            return false;
        }

        $optimizable = $XenEs->getOptimizableMappings();
        foreach ($optimizable AS $type)
        {
            $XenEs->optimizeMapping($type, false);
            $requireIndexing[$type] = true;
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

        return false;
    }

    public function canCancel()
    {
        return false;
    }
}
