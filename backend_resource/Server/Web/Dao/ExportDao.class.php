<?php
/**
 * 数据导出[数据库操作] 开源版本
 * @copyright 广州银云信息科技有限公司
 * @author 梁佳宝
 */
class ExportDao
{
    /**
     * 获取项目数据
     */
    public function getProjectData(&$projectID)
    {
        $db = getdatabase();
        
        $dumpJson = array();
        
        //获取项目信息
        $dumpJson['projectInfo'] = $db -> prepareExecute("SELECT projectName,projectType,projectUpdateTime,projectDesc,projectVersion FROM eo_api_project WHERE eo_api_project.projectID = ?;", array($projectID));
        
        //获取接口父分组信息
        $api_group_list = $db -> prepareExecuteAll("SELECT * FROM eo_api_group WHERE eo_api_group.projectID = ? AND eo_api_group.isChild = ?;", array($projectID, 0));
        $i = 0;
        foreach ($api_group_list as $api_group)
        {
            $dumpJson['apiGroupList'][$i] = $api_group;
            
            //获取接口信息
            $apiList = $db -> prepareExecuteAll("SELECT eo_api_cache.apiJson,eo_api_cache.starred FROM eo_api_cache INNER JOIN eo_api ON eo_api.apiID = eo_api_cache.apiID WHERE eo_api_cache.projectID = ? AND eo_api_cache.groupID = ? AND eo_api.removed = 0;", array(
                $projectID,
                $api_group['groupID']
            ));
            $dumpJson['apiGroupList'][$i]['apiList'] = array();
            $j = 0;
            foreach ($apiList as $api)
            {
                $dumpJson['apiGroupList'][$i]['apiList'][$j] = json_decode($api['apiJson'], TRUE);
                $dumpJson['apiGroupList'][$i]['apiList'][$j]['baseInfo']['starred'] = $api['starred'];
                ++$j;
            }
            $api_group_clild_list = $db -> prepareExecuteAll("SELECT * FROM eo_api_group WHERE eo_api_group.parentGroupID = ? AND eo_api_group.isChild = ?;", array($api_group['groupID'], 1));
            $k = 0;
            if($api_group_clild_list)
            {
                foreach ($api_group_clild_list as $api_group_clid)
                {
                    $dumpJson['apiGroupList'][$i]['apiGroupChildList'][$k] = $api_group_clid;
                    
                    //获取接口信息
                    $apiList = $db -> prepareExecuteAll("SELECT eo_api_cache.apiJson,eo_api_cache.starred FROM eo_api_cache INNER JOIN eo_api ON eo_api.apiID = eo_api_cache.apiID WHERE eo_api_cache.projectID = ? AND eo_api_cache.groupID = ? AND eo_api.removed = 0;", array(
                        $projectID,
                        $api_group_clid['groupID']
                    ));
                    $dumpJson['apiGroupList'][$i]['apiGroupChildList'][$k]['apiList'] = array();
                    $x = 0;
                    foreach ($apiList as $api)
                    {
                        $dumpJson['apiGroupList'][$i]['apiGroupChildList'][$k]['apiList'][$x] = json_decode($api['apiJson'], TRUE);
                        $dumpJson['apiGroupList'][$i]['apiGroupChildList'][$k]['apiList'][$x]['baseInfo']['starred'] = $api['starred'];
                        ++$x;
                    }
                    ++$k;
                }
            }
            ++$i;
        }
        
        //获取状态码分组信息
        $statusCodeGroupList = $db -> prepareExecuteAll("SELECT * FROM eo_api_status_code_group WHERE eo_api_status_code_group.projectID = ? AND eo_api_status_code_group.isChild = ?;", array($projectID, 0));
        
        $i = 0;
        foreach ($statusCodeGroupList as $statusCodeGroup)
        {
            $dumpJson['statusCodeGroupList'][$i] = $statusCodeGroup;
            
            //获取状态码信息
            $statusCodeList = $db -> prepareExecuteAll("SELECT * FROM eo_api_status_code WHERE eo_api_status_code.groupID = ?;", array($statusCodeGroup['groupID']));
            
            $j = 0;
            foreach ($statusCodeList as $statusCode)
            {
                $dumpJson['statusCodeGroupList'][$i]['statusCodeList'][$j] = $statusCode;
                ++$j;
            }
            $statusCodeGroupList_child = $db -> prepareExecuteAll("SELECT * FROM eo_api_status_code_group WHERE eo_api_status_code_group.parentGroupID = ? AND eo_api_status_code_group.isChild = ? ;", array($statusCodeGroup['groupID'], 1));
            $k = 0;
            if($statusCodeGroupList_child)
            {
                foreach ($statusCodeGroupList_child as $statusCodeGroup_child)
                {
                    $dumpJson['statusCodeGroupList'][$i]['statusCodeGroupChildList'][$k] = $statusCodeGroup_child;
                    $statusCodeList = $db -> prepareExecuteAll("SELECT * FROM eo_api_status_code WHERE eo_api_status_code.groupID = ?;", array($statusCodeGroup_child['groupID']));
                    $x= 0;
                    foreach ($statusCodeList as $statusCode)
                    {
                        $dumpJson['statusCodeGroupList'][$i]['statusCodeGroupChildList'][$k]['statusCodeList'][$x] = $statusCode;
                        ++$x;
                    }
                    ++$k;
                }
            }
            ++$i;
        }
        if (empty($dumpJson))
            return FALSE;
            else
                return $dumpJson;
    }
    
}
?>