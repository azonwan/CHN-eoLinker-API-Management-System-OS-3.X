<?php
/**
 * @update:20160721
 * @author:rolealiu
 */
namespace Web\dao;
class ProjectLog
{
    public static $OP_TYPE_ADD = 0;
    public static $OP_TYPE_UPDATE = 1;
    public static $OP_TYPE_DELETE = 2;
    public static $OP_TYPE_OTHERS = 3;
    public static $OP_TARGET_PROJECT = 0;
    public static $OP_TARGET_API = 1;
    public static $OP_TARGET_API_GROUP = 2;
    public static $OP_TARGET_STATUS_CODE = 3;
    public static $OP_TARGET_STATUS_CODE_GROUP = 4;
    public static $OP_TARGET_ENVIRONMENT = 5;
    public static $OP_TARGET_PARTNER = 6;
    
    /**
     * 记录操作日志
     * @param $project_id 项目的数字ID
     * @param $user_id 发起操作的用户的ID
     * @param $op_type 操作类型,0新增/1修改/2删除/3其他
     * @param $op_target 操作目标，0项目/1接口/2接口分组/3状态码/4状态码分组/5项目环境/6团队协作
     * @param $op_desc 操作说明
     * @param $op_time 操作时间,格式为2016-10-25 12:23:34
     */
    public function addOperationLog(&$project_id, &$user_id, $op_target, &$op_targetID, $op_type, $op_desc, $op_time)
    {
        $db = getDatabase();
        $db -> prepareExecute('INSERT INTO eo_log_management_operation (eo_log_management_operation.opType,eo_log_management_operation.opUserID,eo_log_management_operation.opDesc,eo_log_management_operation.opTime,eo_log_management_operation.opProjectID,eo_log_management_operation.opTarget,eo_log_management_operation.opTargetID) VALUES (?,?,?,?,?,?,?);', array(
            $op_type,
            $user_id,
            $op_desc,
            $op_time,
            $project_id,
            $op_target,
            $op_targetID
        ));
        
        if ($db -> getAffectRow() > 0)
            return TRUE;
            else
                return FALSE;
    }
    
    /**
     * 获取操作日志
     * @param $project_id 项目的数字ID
     */
    public function getOperationLogList(&$project_id, &$page, &$page_size, $dayOffset)
    {
        $db = get_database();
        $result['logList'] = $db -> prepareExecuteAll('SELECT eo_log_management_operation.opTime,eo_log_management_operation.opType,eo_user_info.userNickName,eo_log_management_operation.opTarget,eo_log_management_operation.opDesc FROM eo_log_management_operation INNER JOIN eo_user_info ON eo_log_management_operation.opUserID = eo_user_info.userID WHERE eo_log_management_operation.opProjectID = ? AND eo_log_management_operation.opTime > DATE_SUB(NOW(),INTERVAL ? DAY) ORDER BY eo_log_management_operation.opTime DESC LIMIT ?,?;', array(
            $project_id,
            $dayOffset,
            ($page - 1) * $page_size,
            $page_size
        ));
        
        $log_count = $db -> prepareExecute('SELECT COUNT(eo_log_management_operation.opID) AS logCount FROM eo_log_management_operation WHERE eo_log_management_operation.opProjectID = ? AND eo_log_management_operation.opTime > DATE_SUB(NOW(),INTERVAL ? DAY)', array(
            $project_id,
            $dayOffset
        ));
        
        $result = array_merge($result, $log_count);
        
        if (empty($result))
            return FALSE;
            else
                return $result;
    }
    
    /**
     * 获取24小时之内操作日志以及数量
     * @param $project_id 项目的数字ID
     */
    public function getLogInADay(&$project_id)
    {
        $db = getDatabase();
        $result['logList'] = $db -> prepareExecuteAll('SELECT eo_log_management_operation.opTime,eo_user_info.userNickName,eo_log_management_operation.opDesc FROM eo_log_management_operation INNER JOIN eo_user_info ON eo_log_management_operation.opUserID = eo_user_info.userID WHERE eo_log_management_operation.opProjectID = ? AND eo_log_management_operation.opTime > DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY eo_log_management_operation.opTime DESC LIMIT 0,10;', array($project_id));
        
        $log_count = $db -> prepareExecute('SELECT COUNT(eo_log_management_operation.opID) AS logCount FROM eo_log_management_operation WHERE eo_log_management_operation.opProjectID = ? AND eo_log_management_operation.opTime > DATE_SUB(NOW(),INTERVAL 1 DAY) ', array($project_id));
        
        $result = array_merge($result, $log_count);
        
        if (empty($result))
            return FALSE;
            else
                return $result;
    }
    
}
