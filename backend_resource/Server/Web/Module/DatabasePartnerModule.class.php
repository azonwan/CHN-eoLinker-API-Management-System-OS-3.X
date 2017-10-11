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
class DatabasePartnerModule
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
        }
        return $result;
    }

    /**
     * 邀请协作人员
     * @param $dbID int 数据库ID
     * @param $inviteUserID int 邀请人ID
     * @return mixed
     */
    public function invitePartner(&$dbID, &$inviteUserID)
    {
        $dbDao = new DatabaseDao();
        if ($dbDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            $dbInfo = $dbDao->getDatabaseInfo($dbID);
            $summary = '您已被邀请加入数据库：' . $dbInfo['databaseInfo']['databaseName'] . '，开始您的高效协作之旅吧！';
            $msg = '<p>您好！亲爱的用户：</p><p>您已经被加入数据库：<b style="color:#4caf50">' . $dbInfo['databaseInfo']['databaseName'] . '</b>，现在你可以参与数据字典的开发协作工作。</p><p>如果您在使用的过程中遇到任何问题，欢迎前往<a href="http://blog.eolinker.com/#/bbs/"><b style="color:#4caf50">交流社区</b></a>反馈意见，谢谢！。</p>';

            //邀请协作人员
            $partnerDao = new DatabasePartnerDao;
            if ($connID = $partnerDao->invitePartner($dbID, $inviteUserID, $_SESSION['userID'])) {
                //给协作人员发送邀请信息
                $msgDao = new MessageDao;
                $msgDao->sendMessage($_SESSION['userID'], $inviteUserID, 1, $summary, $msg);
                return $connID;
            } else
                return FALSE;
        } else
            return FALSE;
    }

    /**
     * 移除协作人员
     * @param $dbID int 数据库ID
     * @param $connID int 连接ID
     * @return bool
     */
    public function removePartner($dbID, $connID)
    {
        $dbDao = new DatabaseDao();
        if ($dbDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            $dbInfo = $dbDao->getDatabaseInfo($dbID);
            $summary = '您已被移除出数据库：' . $dbInfo['databaseInfo']['databaseName'];
            $msg = '<p>您好！亲爱的用户：</p><p>您已经被移除出数据库：<b style="color:#4caf50">' . $dbInfo['databaseInfo']['databaseName'] . '</b>。</p><p>如果您在使用的过程中遇到任何问题，欢迎前往<a href="http://blog.eolinker.com/#/bbs/"><b style="color:#4caf50">交流社区</b></a>反馈意见，谢谢！。</p>';

            $partnerDao = new DatabasePartnerDao;
            $remotePartnerID = $partnerDao->getUserID($connID);
            if ($partnerDao->removePartner($dbID, $connID)) {
                //给协作人员发送邀请信息
                $msgDao = new MessageDao;
                $msgDao->sendMessage(0, $remotePartnerID, 1, $summary, $msg);
                return TRUE;
            } else
                return FALSE;
        } else
            return FALSE;

    }

    /**
     * 获取协作人员列表
     * @param $dbID int 数据库ID
     * @return array|bool
     */
    public function getPartnerList(&$dbID)
    {
        $dbDao = new DatabaseDao();
        if ($dbDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            $partnerDao = new DatabasePartnerDao;
            $list = $partnerDao->getPartnerList($dbID);
            foreach ($list as &$param) {
                if ($param['userID'] == $_SESSION['userID'])
                    $param['isNow'] = 1;
                else
                    $param['isNow'] = 0;
                unset($param['userID']);
            }
            return $list;
        } else
            return FALSE;
    }

    /**
     * 退出协作项目
     * @param $dbID int 数据库ID
     * @return bool
     */
    public function quitPartner(&$dbID)
    {
        $dbDao = new DatabaseDao();
        if ($dbDao->checkDatabasePermission($dbID, $_SESSION['userID'])) {
            $partnerDao = new DatabasePartnerDao;
            if ($partnerDao->quitPartner($dbID, $_SESSION['userID']))
                return TRUE;
            else
                return FALSE;
        } else
            return FALSE;
    }

    /**
     * 查询是否已经加入过项目
     * @param $dbID int 项目ID
     * @param $userName string 用户名
     * @return bool
     */
    public function checkIsInvited(&$dbID, &$userName)
    {
        $dao = new DatabasePartnerDao;
        return $dao->checkIsInvited($dbID, $userName);
    }

    /**
     * 修改协作成员的昵称
     * @param $dbID int 数据库ID
     * @param $conn_id int 连接ID
     * @param $nick_name string 昵称
     * @return bool
     */
    public function editPartnerNickName(&$dbID, &$conn_id, &$nick_name)
    {
        $dao = new DatabasePartnerDao();
        return $dao->editPartnerNickName($dbID, $conn_id, $nick_name);
    }

    /**
     * 修改协作成员的类型
     * @param $dbID int 数据库ID
     * @param $conn_id int 连接ID
     * @param $user_type int 用户类型
     * @return bool
     */
    public function editPartnerType(&$dbID, &$conn_id, &$user_type)
    {
        $dao = new DatabasePartnerDao();
        return $dao->editPartnerType($dbID, $conn_id, $user_type);
    }

}

?>