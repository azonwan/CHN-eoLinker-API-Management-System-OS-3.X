<?php
/**
 * @name eolinker open source，eolinker开源版本
 * @link https://www.eolinker.com
 * @package eolinker
 * @author www.eolinker.com 广州银云信息科技有限公司 ©2015-2016
 * eolinker，业内领先的Api接口管理及测试平台，为您提供最专业便捷的在线接口管理、测试、维护以及各类性能测试方案，帮助您高效开发、安全协作。
 * 如在使用的过程中有任何问题，欢迎加入用户讨论群进行反馈，我们将会以最快的速度，最好的服务态度为您解决问题。
 * 用户讨论QQ群：284421832
 *
 * 注意！eolinker开源版本仅供用户下载试用、学习和交流，禁止“一切公开使用于商业用途”或者“以eolinker开源版本为基础而开发的二次版本”在互联网上流通。
 * 注意！一经发现，我们将立刻启用法律程序进行维权。
 * 再次感谢您的使用，希望我们能够共同维护国内的互联网开源文明和正常商业秩序。
 *
 */
class EnvDao
{
    /**
     * 获取环境列表
     */
    public function getEnvList(&$prjectID)
    {
        $db = getDatabase();

        $envList = $db->prepareExecuteAll("SELECT eo_api_env.envID,eo_api_env.envName FROM eo_api_env WHERE eo_api_env.projectID = ? ORDER BY eo_api_env.envID DESC;", array($prjectID));

        if (is_array($envList)) {
            foreach ($envList as &$env) {
                $env['frontURIList'] = $db->prepareExecuteAll("SELECT eo_api_env_front_uri.applyProtocol,eo_api_env_front_uri.uri,eo_api_env_front_uri.uriID FROM eo_api_env_front_uri WHERE eo_api_env_front_uri.envID = ?;", array($env['envID']));
                $env['headerList'] = $db->prepareExecuteAll("SELECT eo_api_env_header.applyProtocol,eo_api_env_header.headerName,eo_api_env_header.headerValue,eo_api_env_header.headerID FROM eo_api_env_header WHERE eo_api_env_header.envID = ?;", array($env['envID']));
                $env['paramList'] = $db->prepareExecuteAll("SELECT eo_api_env_param.paramKey,eo_api_env_param.paramValue,eo_api_env_param.paramID FROM eo_api_env_param WHERE eo_api_env_param.envID = ?;", array($env['envID']));
            }

        }

        if (empty($envList))
            return FALSE;
        else
            return $envList;
    }

    /**
     * 添加环境
     * @param int $projectID 项目ID
     * @param string $envName 环境名称
     * @param string $front_uri 前置URI
     * @param array $headers 请求头部
     * @param array $params 全局变量
     * @param int $apply_protocol 应用的请求类型,[-1]=>[所有请求类型]
     */
    public function addEnv(&$projectID, &$envName, &$front_uri, &$headers, &$params, $apply_protocol)
    {
        $db = getDatabase();
        try {
            $db->beginTransaction();
            //新建环境
            $db->prepareExecute("INSERT INTO eo_api_env (eo_api_env.envName,eo_api_env.projectID) VALUES (?,?);", array(
                $envName,
                $projectID
            ));
            $env_id = $db->getLastInsertID();
            if (empty($env_id))
                throw new \PDOException('addEnv Error');
            if ($front_uri) {
                //新建前置URI
                $db->prepareExecute("INSERT INTO eo_api_env_front_uri (eo_api_env_front_uri.envID,eo_api_env_front_uri.applyProtocol,eo_api_env_front_uri.uri) VALUES (?,?,?);", array(
                    $env_id,
                    $apply_protocol,
                    $front_uri
                ));
                if ($db->getAffectRow() < 1)
                    throw new \PDOException('addFrontURI Error');
            }
            if (!empty($headers)) {
                foreach ($headers as $k => $v) {
                    //新建请求头部
                    $db->prepareExecute("INSERT INTO eo_api_env_header (eo_api_env_header.envID,eo_api_env_header.applyProtocol,eo_api_env_header.headerName,eo_api_env_header.headerValue) VALUES (?,?,?,?);", array(
                        $env_id,
                        $apply_protocol,
                        $k,
                        $v
                    ));
                    if ($db->getAffectRow() < 1)
                        throw new \PDOException('addHeader Error');
                }
            }
            if (!empty($params)) {
                foreach ($params as $k => $v) {
                    //新建全局变量
                    $db->prepareExecute("INSERT INTO eo_api_env_param (eo_api_env_param.envID,eo_api_env_param.paramKey,eo_api_env_param.paramValue) VALUES (?,?,?);", array(
                        $env_id,
                        $k,
                        $v
                    ));
                    if ($db->getAffectRow() < 1)
                        throw new \PDOException('addParam Error');
                }
            }
            $db->commit();
            $db->close();
            return $env_id;
        } catch (\PDOException $e) {
            $db->rollback();
            return FALSE;
        }
    }

    /**
     * 删除环境
     */
    public function deleteEnv(&$projectID, &$env_id)
    {
        $db = getDatabase();
        $db->beginTransaction();
        $result = $db->prepareExecute('SELECT * FROM eo_api_env_front_uri WHERE eo_api_env_front_uri.envID = ?;', array(
            $env_id
        ));
        if (!empty($result)) {
            //删除旧的前置URI
            $db->prepareExecute("DELETE FROM eo_api_env_front_uri WHERE eo_api_env_front_uri.envID = ?;", array(
                $env_id
            ));
            if ($db->getAffectRow() < 1) {
                $db->rollback();
                return FALSE;
            }
        }
        $result = $db->prepareExecute('SELECT * FROM eo_api_env_header WHERE eo_api_env_header.envID = ?;', array(
            $env_id
        ));
        if (!empty($result)) {
            //删除旧的请求头部
            $db->prepareExecute("DELETE FROM eo_api_env_header WHERE eo_api_env_header.envID = ?;", array(
                $env_id
            ));
            if ($db->getAffectRow() < 1) {
                $db->rollback();
                return FALSE;
            }
        }
        $result = $db->prepareExecute('SELECT * FROM eo_api_env_param WHERE eo_api_env_param.envID = ?;', array(
            $env_id
        ));
        if (!empty($result)) {
            //删除旧的全局变量
            $db->prepareExecute("DELETE FROM eo_api_env_param WHERE eo_api_env_param.envID = ?;", array(
                $env_id
            ));
            if ($db->getAffectRow() < 1) {
                $db->rollback();
                return FALSE;
            }
        }
        //删除环境
        $db->prepareExecute("DELETE FROM eo_api_env WHERE eo_api_env.envID = ? AND eo_api_env.projectID = ?;", array(
            $env_id,
            $projectID
        ));
        if ($db->getAffectRow() > 0) {
            $db->commit();
            return TRUE;
        } else {
            $db->rollback();
            return FALSE;
        }

    }

    /**
     * 修改环境
     */
    public function editEnv(&$env_id, &$envName, &$front_uri, &$headers, &$params, $apply_protocol)
    {
        $db = getDatabase();
        try {
            $db->beginTransaction();
            $db->prepareExecute("UPDATE eo_api_env SET eo_api_env.envName = ? WHERE eo_api_env.envID = ?;", array(
                $envName,
                $env_id
            ));
            //删除旧的前置URI
            $db->prepareExecute("DELETE FROM eo_api_env_front_uri WHERE eo_api_env_front_uri.envID = ?;", array(
                $env_id
            ));

            //删除旧的请求头部
            $db->prepareExecute("DELETE FROM eo_api_env_header WHERE eo_api_env_header.envID = ?;", array(
                $env_id
            ));

            //删除旧的全局变量
            $db->prepareExecute("DELETE FROM eo_api_env_param WHERE eo_api_env_param.envID = ?;", array(
                $env_id
            ));

            if ($front_uri) {
                // 新建前置URI
                $db->prepareExecute("INSERT INTO eo_api_env_front_uri (eo_api_env_front_uri.envID,eo_api_env_front_uri.applyProtocol,eo_api_env_front_uri.uri) VALUES (?,?,?);", array(
                    $env_id,
                    $apply_protocol,
                    $front_uri
                ));
                if ($db->getAffectRow() < 1)
                    throw new \PDOException('addFrontURI Error');
            }
            if (!empty($headers)) {
                foreach ($headers as $k => $v) {
                    // 新建请求头部
                    $db->prepareExecute("INSERT INTO eo_api_env_header (eo_api_env_header.envID,eo_api_env_header.applyProtocol,eo_api_env_header.headerName,eo_api_env_header.headerValue) VALUES (?,?,?,?);", array(
                        $env_id,
                        $apply_protocol,
                        $k,
                        $v
                    ));
                    if ($db->getAffectRow() < 1)
                        throw new \PDOException('addHeader Error');
                }
            }
            if (!empty($params)) {
                foreach ($params as $k => $v) {
                    // 新建全局变量
                    $db->prepareExecute("INSERT INTO eo_api_env_param (eo_api_env_param.envID,eo_api_env_param.paramKey,eo_api_env_param.paramValue) VALUES (?,?,?);", array(
                        $env_id,
                        $k,
                        $v
                    ));
                    if ($db->getAffectRow() < 1)
                        throw new \PDOException('addParam Error');
                }
            }
            $db->commit();
            return TRUE;
        } catch (\PDOException $e) {
            $db->rollback();
            return FALSE;
        }
    }

    /**
     * 获取环境信息
     * @param int $env_id 环境ID
     */
    public function getEnvInfoFromDB(&$env_id)
    {
        $db = getDatabase();
        $env = $db->prepareExecute("SELECT eo_api_env.envID,eo_api_env.envName FROM eo_api_env WHERE eo_api_env.envID = ?;", array($env_id));
        $env['frontURIList'] = $db->prepareExecute("SELECT eo_api_env_front_uri.applyProtocol,eo_api_env_front_uri.uri FROM eo_api_env_front_uri WHERE eo_api_env_front_uri.envID = ?;", array($env_id));
        $env['headerList'] = $db->prepareExecuteAll("SELECT eo_api_env_header.applyProtocol,eo_api_env_header.headerName,eo_api_env_header.headerValue FROM eo_api_env_header WHERE eo_api_env_header.envID = ?;", array($env_id));
        $env['paramList'] = $db->prepareExecuteAll("SELECT eo_api_env_param.paramKey,eo_api_env_param.paramValue FROM eo_api_env_param WHERE eo_api_env_param.envID = ?;", array($env_id));
        if ($env)
            return $env;
        else
            return FALSE;
    }

    /**
     * 获取环境名称
     */
    public function getEnvName(&$envID)
    {
        $db = getDatabase();
        $result = $db->prepareExecute("SELECT eo_api_env.envName FROM eo_api_env WHERE eo_api_env.envID = ?;", array($envID));

        if (empty($result))
            return FALSE;
        else
            return $result['envName'];
    }

    /**
     * 检查项目环境权限
     * @param $envID
     * @param $userID
     * @return bool
     */
    public function checkEnvPermission(&$envID, &$userID)
    {
        $db = getDatabase();
        $result = $db->prepareExecute('SELECT eo_conn_project.projectID FROM eo_api_env LEFT JOIN eo_conn_project ON eo_api_env.projectID = eo_conn_project.projectID WHERE eo_api_env.envID = ? AND eo_conn_project.userID = ?;', array(
            $envID,
            $userID
        ));
        if (empty($result)) {
            return FALSE;
        } else {
            return $result['projectID'];
        }
    }
}