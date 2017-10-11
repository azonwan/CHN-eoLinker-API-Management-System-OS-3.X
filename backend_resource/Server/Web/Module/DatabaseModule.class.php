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
class DatabaseModule
{
    public function __construct()
    {
        @session_start();
    }

    /**
     * 获取数据字典用户类型
     * @param $dbID
     * @return bool|int
     */
    public function getUserType(&$dbID)
    {
        $dao = new AuthorizationDao();
        $result = $dao->getDatabaseUserType($_SESSION['userID'], $dbID);
        if ($result === FALSE) {
            return -1;
        } else {
            return $result;
        }
    }

    /**
     * 添加数据库
     * @param $dbName 数据库名
     * @param $dbVersion 数据库版本，默认1.0
     */
    public function addDatabase(&$dbName, &$dbVersion = 1.0)
    {
        $databaseDao = new DatabaseDao;
        return $databaseDao->addDatabase($dbName, $dbVersion, $_SESSION['userID']);
    }

    /**
     * 删除数据库
     * @param $dbID 数据库ID
     */
    public function deleteDatabase(&$dbID)
    {
        $databaseDao = new DatabaseDao;
        if ($dbID = $databaseDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            return $databaseDao->deleteDatabase($dbID);
        } else
            return FALSE;
    }

    /**
     * 获取数据库列表
     */
    public function getDatabase()
    {
        $databaseDao = new DatabaseDao;
        return $databaseDao->getDatabase($_SESSION['userID']);
    }

    /**
     * 修改数据库
     * @param $dbID 数据库ID
     * @param $dbName 数据库名
     * @param $dbVersion 数据库版本
     */
    public function editDatabase(&$dbID, &$dbName, &$dbVersion)
    {
        $databaseDao = new DatabaseDao;
        if ($dbID = $databaseDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            return $databaseDao->editDatabase($dbID, $dbName, $dbVersion);
        } else
            return FALSE;
    }

    /**
     * 导入数据表
     */
    public function importDatabase(&$dbID, &$tables)
    {
        $userID = $_SESSION['userID'];
        $databaseDao = new DatabaseDao;
        $databaseTableDao = new DatabaseTableDao;
        if ($dbID = $databaseDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            $tableList = array();
            foreach ($tables as $table) {
                $fieldList = array();
                //将各字段信息分割成一行一个
                preg_match_all('/.+?[\r\n]+/s', $table['tableField'], $fields);

                $primaryKeys = '';
                foreach ($fields[0] as $field) {
                    $field = trim($field);
                    //以'`'开头的是字段
                    if (strpos($field, '`') === 0) {
                        $fieldName = substr($field, 1, strpos(substr($field, 1), '`'));
                        //将字段类型和长度的混合提取出来
                        preg_match('/`\\s(.+?)\\s/', $field, $type);
                        if (!$type[1]) {
                            $type[1] = substr($field, strlen($fieldName) + 3, strpos(substr($field, strlen($fieldName) + 3), ','));
                        }
                        if (!$type[1]) {
                            $type[1] = substr($field, strlen($fieldName) + 3);
                        }
                        if (strpos($type[1], '(')) {
                            $fieldType = substr($type[1], 0, strpos($type[1], '('));
                            if (preg_match('/\([0-9]{1,10}/', $type[1], $match)) {
                                //长度用左括号右边第一个10位内数字表示
                                $fieldLength = substr($match[0], 1);
                            } else {
                                $fieldLength = '0';
                            }
                        } else {
                            $fieldType = $type[1];
                            //未注明长度，默认为0
                            $fieldLength = '0';
                        }

                        if (strpos($field, 'NOT NULL') !== FALSE) {
                            $isNotNull = 1;
                        } else
                            $isNotNull = 0;

                        $fieldList[] = array(
                            'fieldName' => $fieldName,
                            'fieldType' => $fieldType,
                            'fieldLength' => $fieldLength,
                            'isNotNull' => $isNotNull
                        );
                    }

                    //以PRIMARY KEY开头的是整个表中主键的集合
                    if (strpos($field, 'PRIMARY') !== FALSE) {
                        $table['primaryKey'] = $table['primaryKey'] . substr($field, strpos($field, '('));
                    }

                }

                //判断各字段是否为主键
                foreach ($fieldList as &$tableField) {
                    if (strpos($table['primaryKey'], $tableField['fieldName']) !== FALSE) {
                        $tableField['isPrimaryKey'] = 1;
                    } else {
                        $tableField['isPrimaryKey'] = 0;
                    }
                }
                $tableList[] = array(
                    'tableName' => $table['tableName'],
                    'fieldList' => $fieldList
                );
                unset($fieldList);
            }

            if (isset($tableList[0]))
                return $databaseDao->importDatabase($dbID, $tableList);
            else
                return FALSE;
        } else
            return FALSE;
    }

    // *导入数据字典界面数据库
    public function importDatabseByJson(&$data)
    {
        $user_id = $_SESSION['userID'];

        $service = new DatabaseDao;
        $result = $service->importDatabaseByJson($user_id, $data);
        if ($result)
            return TRUE;
        else
            return FALSE;
    }

    //数据表导出成为sql格式
    public function exportDatabase(&$dbID)
    {
        $userID = $_SESSION['userID'];
        $dao = new DatabaseDao;
        if ($dao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            $dumpJson = json_encode($dao->getDatabaseInfo($dbID));
            $fileName = 'eolinker_export_' . $_SESSION['userName'] . '_' . time() . '.export';
            if (file_put_contents(realpath('./dump') . DIRECTORY_SEPARATOR . $fileName, $dumpJson)) {
                return $fileName;
            } else {
                return FALSE;
            }
        } else
            return FALSE;
    }


}

?>