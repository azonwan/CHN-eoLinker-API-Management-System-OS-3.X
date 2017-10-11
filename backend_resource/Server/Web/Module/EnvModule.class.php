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
class EnvModule
{
    public function __construct()
    {
        @session_start();
    }

    /**
     * 获取环境列表
     * @param $project_id 项目的数字ID
     */
    public function getEnvList(&$project_id)
    {
        $projectDao = new ProjectDao;
        if (!$projectDao->checkProjectPermission($project_id, $_SESSION['userID'])) {
            return FALSE;
        }
        $env_dao = new EnvDao;
        return $env_dao->getEnvList($project_id);
    }

    /**
     * 添加环境
     * @param $project_id 项目的数字ID
     * @param $user_id 用户的数字ID
     * @param $env_name 环境名称
     * @param $front_uri 前置URI
     * @param $headers 请求头部
     * @param $params 全局变量
     * @param $apply_protocol 应用的请求类型,[-1]=>[所有请求类型]
     */
    public function addEnv(&$project_id, &$env_name, &$front_uri, &$headers, &$params, $apply_protocol)
    {
        $env_dao = new EnvDao;
        $projectDao = new ProjectDao;
        if (!$projectDao->checkProjectPermission($project_id, $_SESSION['userID'])) {
            return FALSE;
        }
        $env_id = $env_dao->addEnv($project_id, $env_name, $front_uri, $headers, $params, $apply_protocol);
        if ($env_id) {
            return $env_id;
        } else {
            return FALSE;
        }
    }

    /**
     * 删除环境
     * @param $project_id 项目的数字ID
     * @param $env_id 环境的数字ID
     */
    public function deleteEnv(&$project_id, &$env_id)
    {
        $env_dao = new EnvDao;
        $projectDao = new ProjectDao;
        if ($projectDao->checkProjectPermission($project_id, $_SESSION['userID'])) {
            if (!$env_dao->checkEnvPermission($env_id, $_SESSION['userID'])) {
                return FALSE;
            }
            if ($env_dao->deleteEnv($project_id, $env_id)) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * 修改环境
     * @param $project_id 项目的数字ID
     * @param $user_id 用户的数字ID
     * @param $env_id 环境的数字ID
     * @param $env_name 环境名称
     * @param $front_uri 前置URI
     * @param $headers 请求头部
     * @param $params 全局变量
     * @param $apply_protocol 应用的请求类型,[-1]=>[所有请求类型]
     */
    public function editEnv(&$env_id, &$env_name, &$front_uri, &$headers, &$params, $apply_protocol)
    {
        $env_dao = new EnvDao;
        if (!$env_dao->checkEnvPermission($env_id, $_SESSION['userID'])) {
            return FALSE;
        }
        if ($env_dao->editEnv($env_id, $env_name, $front_uri, $headers, $params, $apply_protocol)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}