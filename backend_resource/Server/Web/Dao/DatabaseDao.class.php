<?php

/**
 * @name eolinker open source，eolinker开源版本
 * @link https://www.eolinker.com
 * @package eolinker
 * @author www.eolinker.com 广州银云信息科技有限公司 ©2015-2016
 
 *  * eolinker，业内领先的Api接口管理及测试平台，为您提供最专业便捷的在线接口管理、测试、维护以及各类性能测试方案，帮助您高效开发、安全协作。
 * 如在使用的过程中有任何问题，欢迎加入用户讨论群进行反馈，我们将会以最快的速度，最好的服务态度为您解决问题。
 * 用户讨论QQ群：284421832
 *
 * 注意！eolinker开源版本仅供用户下载试用、学习和交流，禁止“一切公开使用于商业用途”或者“以eolinker开源版本为基础而开发的二次版本”在互联网上流通。
 * 注意！一经发现，我们将立刻启用法律程序进行维权。
 * 再次感谢您的使用，希望我们能够共同维护国内的互联网开源文明和正常商业秩序。
 *
 */
class DatabaseDao
{

    /**
     * 添加数据库
     * 
     * @param $dbName 数据库名            
     * @param $dbVersion 数据库版本，默认1.0            
     * @param $userID 用户ID            
     */
    public function addDatabase(&$dbName, &$dbVersion, &$userID)
    {
        $db = getDatabase();
        
        $db->beginTransaction();
        
        $db->prepareExecute('INSERT INTO eo_database (eo_database.dbName,eo_database.dbVersion,eo_database.dbUpdateTime) VALUE (?,?,?);', array(
            $dbName,
            $dbVersion,
            date('Y-m-d H:i:s', time())
        ));
        
        if ($db->getAffectRow() < 1) {
            $db->rollback();
            return FALSE;
        }
        $dbID = $db->getLastInsertID();
        
        // 生成数据库与用户的联系
        $db->prepareExecute('INSERT INTO eo_conn_database (eo_conn_database.dbID,eo_conn_database.userID) VALUES (?,?);', array(
            $dbID,
            $userID
        ));
        
        if ($db->getAffectRow() < 1) {
            $db->rollback();
            return FALSE;
        } else {
            $db->commit();
            return $dbID;
        }
    }

    /**
     * 检查数据库跟用户是否匹配
     * 
     * @param $dbID 数据库ID            
     * @param $userID 用户ID            
     */
    public function checkDatabasePermission(&$dbID, &$userID)
    {
        $db = getDatabase();
        
        $result = $db->prepareExecute('SELECT eo_conn_database.dbID FROM eo_conn_database WHERE eo_conn_database.dbID = ? AND eo_conn_database.userID = ?;', array(
            $dbID,
            $userID
        ));
        
        if (empty($result))
            return FALSE;
        else
            return $result['dbID'];
    }

    /**
     * 删除数据库
     * 
     * @param $dbID 数据库ID            
     */
    public function deleteDatabase(&$dbID)
    {
        $db = getDatabase();
        
        $db->prepareExecute('DELETE FROM eo_database WHERE eo_database.dbID = ?;', array(
            $dbID
        ));
        
        if ($db->getAffectRow() < 1) {
            return FALSE;
        } else
            return TRUE;
    }

    /**
     * 获取数据库列表
     * 
     * @param $userID 用户ID            
     */
    public function getDatabase(&$userID)
    {
        $db = getDatabase();
        
        $result = $db->prepareExecuteAll('SELECT eo_database.dbID,eo_database.dbName,eo_database.dbVersion,eo_database.dbUpdateTime,eo_conn_database.userType FROM eo_database INNER JOIN eo_conn_database ON eo_database.dbID = eo_conn_database.dbID WHERE eo_conn_database.userID = ?;', array(
            $userID
        ));
        
        if (empty($result))
            return FALSE;
        else
            return $result;
    }

    /**
     * 修改数据库
     * 
     * @param $dbID 数据库ID            
     * @param $dbName 数据库名            
     * @param $dbVersion 数据库版本            
     */
    public function editDatabase(&$dbID, &$dbName, &$dbVersion)
    {
        $db = getDatabase();
        
        $db->prepareExecute('UPDATE eo_database SET eo_database.dbName = ?,eo_database.dbVersion =?,eo_database.dbUpdateTime =? WHERE eo_database.dbID =?;', array(
            $dbName,
            $dbVersion,
            date('Y-m-d H:i:s', time()),
            $dbID
        ));
        
        if ($db->getAffectRow() < 1) {
            return FALSE;
        } else
            return TRUE;
    }

    // database import function for data dictionary
    //
    //
    public function importDatabase(&$dbID, &$tableList)
    {
        $db = getDatabase();
        try {
            $db->beginTransaction();
            
            foreach ($tableList as $table) {
                $db->prepareExecute('INSERT INTO eo_database_table (eo_database_table.dbID,eo_database_table.tableName,eo_database_table.tableDescription) VALUES (?,?,?);', array(
                    $dbID,
                    $table['tableName'],
                    NULL
                ));
                
                if ($db->getAffectRow() < 1) {
                    throw new \PDOException("add databaseTable error");
                }
                
                $tableID = $db->getLastInsertID();
                
                foreach ($table['fieldList'] as $field) {
                    $db->prepareExecute('INSERT INTO eo_database_table_field (eo_database_table_field.tableID,eo_database_table_field.fieldName,eo_database_table_field.fieldType,eo_database_table_field.fieldLength,eo_database_table_field.isNotNull,eo_database_table_field.isPrimaryKey,eo_database_table_field.fieldDescription) VALUES (?,?,?,?,?,?,?);', array(
                        $tableID,
                        $field['fieldName'],
                        $field['fieldType'],
                        $field['fieldLength'],
                        $field['isNotNull'],
                        $field['isPrimaryKey'],
                        NULL
                    ));
                    
                    if ($db->getAffectRow() < 1) {
                        throw new \PDOException("add databaseTableField error");
                    }
                }
            }
        } catch (\PDOException $e) {
            $db->rollBack();
            return FALSE;
        }
        $db->commit();
        return TRUE;
    }

    // 得到数据库的信息方便文件导出
    public function getDatabaseInfo(&$dbID)
    {
        $db = getDatabase();
        $dumpJson = array();
        // 获取数据库信息
        $dumpJson['databaseInfo'] = $db->prepareExecute("SELECT eo_database.dbName AS databaseName,eo_database.dbVersion AS databaseVersion FROM eo_database WHERE eo_database.dbID = ?;", array(
            $dbID
        ));
        // 获取数据库表信息
        $table_list = $db->prepareExecuteAll("SELECT tableID,tableName AS tableName, tableDescription AS tableDesc FROM eo_database_table WHERE eo_database_table.dbID = ?;", array(
            $dbID
        ));
        $dumpJson['tableList'] = array();
        $i = 0;
        foreach ($table_list as $table) {
            $dumpJson['tableList'][$i] = $table;
            // 获取字段信息
            $field_list = $db->prepareExecuteAll("SELECT fieldName,fieldType,fieldLength,isNotNull,isPrimaryKey,fieldDescription AS fieldDesc,defaultValue FROM eo_database_table_field WHERE eo_database_table_field.tableID = ?;", array(
                $table['tableID']
            ));
            
            $dumpJson['tableList'][$i]['fieldList'] = array();
            $j = 0;
            foreach ($field_list as $field_list) {
                $dumpJson['tableList'][$i]['fieldList'][$j] = $field_list;
                ++ $j;
            }
            ++ $i;
        }
        if (! empty($dumpJson))
            return $dumpJson;
        else
            return FALSE;
    }

    /**
     * 更新数据库更新时间
     * 
     * @param $dbID 数据库ID            
     */
    public function updateDatabaseUpdateTime(&$dbID)
    {
        $db = getDatabase();
        
        $db->prepareExecute('UPDATE eo_database SET eo_database.dbUpdateTime =? WHERE eo_database.dbID =?;', array(
            date('Y-m-d H:i:s', time()),
            $dbID
        ));
    }

    // 导入eolinker格式数据库
    public function importDatabaseByJson(&$userID, &$data)
    {
        $db = getDatabase();
        
        try {
            // 开始事务
            $db->beginTransaction();
            // 生成数据库
            $db->prepareExecute("INSERT INTO eo_database(eo_database.dbName,eo_database.dbVersion,eo_database.dbUpdateTime)VALUES(?,?,?);", array(
                $data['databaseInfo']['databaseName'],
                $data['databaseInfo']['databaseVersion'],
                date('Y-m-d H:i:s', time())
            ));
            if ($db->getAffectRow() < 1) {
                throw new \PDOException('insert database error');
            }
            $dbID = $db->getLastInsertID();
            // 生成数据库与用户的联系
            $db->prepareExecute('INSERT INTO eo_conn_database (eo_conn_database.dbID,eo_conn_database.userID) VALUES (?,?);', array(
                $dbID,
                $userID
            ));
            if ($db->getAffectRow() < 1) {
                throw new \PDOException('insert conn error');
            }
            foreach ($data['tableList'] as $table) {
                // 生成数据库表
                $db->prepareExecute("INSERT INTO eo_database_table(eo_database_table.dbID,eo_database_table.tableName,eo_database_table.tableDescription)VALUES(?,?,?);", array(
                    $dbID,
                    $table['tableName'],
                    $table['tableDesc']
                ));
                if ($db->getAffectRow() < 1) {
                    throw new \PDOException('insert table error');
                }
                
                $table_id = $db->getLastInsertID();
                foreach ($table['fieldList'] as $field) {
                    // 生成字段表
                    $db->prepareExecute("INSERT INTO eo_database_table_field(eo_database_table_field.fieldName,eo_database_table_field.fieldType,eo_database_table_field.fieldLength,eo_database_table_field.isNotNull,eo_database_table_field.isPrimaryKey,eo_database_table_field.fieldDescription,eo_database_table_field.tableID,eo_database_table_field.defaultValue)VALUES(?,?,?,?,?,?,?,?);", array(
                        $field['fieldName'],
                        $field['fieldType'],
                        $field['fieldLength'],
                        $field['isNotNull'],
                        $field['isPrimaryKey'],
                        $field['fieldDesc'],
                        $table_id,
                        $field['defaultValue']
                    ));
                    
                    if ($db->getAffectRow() < 1) {
                        throw new \PDOException('insert field error');
                    }
                }
            }
        } catch (\PDOException $e) {
            // 出错回滚
            $db->rollBack();
            return FALSE;
        }
        // 提交更改
        $db->commit();
        return TRUE;
    }
}
?>