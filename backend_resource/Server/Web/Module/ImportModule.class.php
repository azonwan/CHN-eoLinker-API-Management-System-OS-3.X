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
class ImportModule
{
    function __construct()
    {
        @session_start();
    }

    /**
     * 导入eoapi
     * @param $data 从eoapi导出的Json格式数据
     */
    public function eoapiImport(&$data)
    {
        $dao = new ImportDao;
        return $dao->importEoapi($data, $_SESSION['userID']);
    }

    /**
     * 导入DHC
     * @param $data 从DHC导出的Json格式数据
     */
    public function importDHC(&$data)
    {
        try {
            $projectInfo = array('projectName' => $data['nodes'][0]['name'], 'projectType' => 0, 'projectVersion' => 1.0);

            //生成分组信息
            $groupInfoList[] = array('groupName' => 'DHC导入', 'id' => $data['nodes'][0]['id']);
            if (is_array($data['nodes'])) {
                foreach ($data['nodes'] as $element) {
                    if ($element['type'] == 'Service') {
                        $groupInfoList[] = array('groupName' => $element['name'], 'id' => $element['id']);
                    }
                }
            }

            if (is_array($groupInfoList)) {
                foreach ($groupInfoList as &$groupInfo) {
                    $apiList = array();
                    if (is_array($data['nodes'])) {
                        foreach ($data['nodes'] as $element) {
                            if ($element['type'] != 'Request' || $element['parentId'] != $groupInfo['id']) {
                                continue;
                            }

                            $apiInfo['baseInfo']['apiName'] = $element['name'];
                            $apiInfo['baseInfo']['apiURI'] = $element['uri']['path'];
                            $apiInfo['baseInfo']['apiProtocol'] = ($element['uri']['scheme']['name'] == 'http') ? 0 : 1;
                            $apiInfo['baseInfo']['apiStatus'] = 0;
                            $apiInfo['baseInfo']['starred'] = 0;
                            $apiInfo['baseInfo']['apiSuccessMock'] = '';
                            $apiInfo['baseInfo']['apiFailureMock'] = '';
                            $apiInfo['baseInfo']['apiRequestParamType'] = 0;
                            $apiInfo['baseInfo']['apiRequestRaw'] = '';
                            $apiInfo['baseInfo']['apiNoteType'] = 0;
                            $apiInfo['baseInfo']['apiNote'] = '';
                            $apiInfo['baseInfo']['apiNoteRaw'] = '';
                            $apiInfo['baseInfo']['apiUpdateTime'] = date("Y-m-d H:i:s", time());
                            switch ($element['method']['name']) {
                                case 'POST' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 0;
                                    break;
                                case 'GET' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 1;
                                    break;
                                case 'PUT' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 2;
                                    break;
                                case 'DELETE' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 3;
                                    break;
                                case 'HEAD' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 4;
                                    break;
                                case 'OPTIONS' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 5;
                                    break;
                                case 'PATCH' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 6;
                                    break;
                            }

                            $headerInfo = array();

                            if (is_array($element['headers'])) {
                                foreach ($element['headers'] as $header) {
                                    $headerInfo[] = array('headerName' => $header['name'], 'headerValue' => $header['value']);
                                }
                            }
                            $apiInfo['headerInfo'] = $headerInfo;
                            unset($headerInfo);

                            $apiRequestParam = array();
                            if ($element['method']['requestBody']) {
                                $items = $element['body']['formBody']['items'];
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        $param['paramKey'] = $item['name'];
                                        $param['paramValue'] = $item['value'];
                                        $param['paramType'] = ($item['type'] == 'Text') ? 0 : 1;
                                        $param['paramNotNull'] = $item['enabled'] ? 0 : 1;
                                        $param['paramName'] = '';
                                        $param['paramLimit'] = '';
                                        $param['paramValueList'] = array();
                                        $apiRequestParam[] = $param;
                                        unset($param);
                                    }
                                }
                            }
                            $apiInfo['requestInfo'] = $apiRequestParam;
                            unset($apiRequestParam);
                            $apiInfo['resultInfo'] = array();

                            $apiList[] = $apiInfo;
                            unset($apiInfo);
                        }
                    }
                    $groupInfo['apiList'] = $apiList;
                    unset($apiList);
                }
            }
            $dao = new ImportDao;
            return $dao->importOther($projectInfo, $groupInfoList, $_SESSION['userID']);
        } catch (\PDOException $e) {
            return FALSE;
        }
    }

    /**
     * 导入V1版本postman
     * @param $data 从Postman V1版本导出的Json格式数据
     */
    public function importPostmanV1(&$data)
    {
        try {
            $projectInfo = array('projectName' => $data['name'], 'projectType' => 0, 'projectVersion' => 1.0);

            $groupInfoList[] = array('groupName' => 'PostMan导入');

            $apiList = array();
            if (is_array($groupInfoList)) {
                foreach ($groupInfoList as &$groupInfo) {
                    if (is_array($data['requests'])) {
                        foreach ($data['requests'] as $request) {
                            $apiInfo['baseInfo']['apiName'] = $request['name'];
                            $apiInfo['baseInfo']['apiURI'] = $request['url'];
                            $apiInfo['baseInfo']['apiProtocol'] = (strpos($request['url'], 'https') !== 0) ? 0 : 1;
                            $apiInfo['baseInfo']['apiStatus'] = 0;
                            $apiInfo['baseInfo']['starred'] = 0;
                            $apiInfo['baseInfo']['apiSuccessMock'] = '';
                            $apiInfo['baseInfo']['apiFailureMock'] = '';
                            $apiInfo['baseInfo']['apiRequestParamType'] = 0;
                            $apiInfo['baseInfo']['apiRequestRaw'] = '';
                            $apiInfo['baseInfo']['apiNoteType'] = 0;
                            $apiInfo['baseInfo']['apiNote'] = '';
                            $apiInfo['baseInfo']['apiNoteRaw'] = '';
                            $apiInfo['baseInfo']['apiUpdateTime'] = date("Y-m-d H:i:s", time());
                            switch ($request['method']) {
                                case 'POST' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 0;
                                    break;
                                case 'GET' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 1;
                                    break;
                                case 'PUT' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 2;
                                    break;
                                case 'DELETE' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 3;
                                    break;
                                case 'HEAD' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 4;
                                    break;
                                case 'OPTIONS' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 5;
                                    break;
                                case 'PATCH' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 6;
                                    break;
                            }

                            $headerInfo = array();
                            $header_rows = array_filter(explode(chr(10), $request['headers']), "trim");

                            if (is_array($header_rows)) {
                                foreach ($header_rows as $row) {
                                    $keylen = strpos($row, ':');
                                    if ($keylen) {
                                        $headerInfo[] = array('headerName' => substr($row, 0, $keylen), 'headerValue' => trim(substr($row, $keylen + 1)));
                                    }
                                }
                            }
                            $apiInfo['headerInfo'] = $headerInfo;
                            unset($headerInfo);

                            $apiRequestParam = array();
                            $items = $request['data'];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    $param['paramKey'] = $item['key'];
                                    $param['paramValue'] = $item['value'];
                                    $param['paramType'] = ($item['type'] == 'text') ? 0 : 1;
                                    $param['paramNotNull'] = $item['enabled'] ? 0 : 1;
                                    $param['paramName'] = '';
                                    $param['paramLimit'] = '';
                                    $param['paramValueList'] = array();
                                    $apiRequestParam[] = $param;
                                    unset($param);
                                }
                            }
                            $apiInfo['requestInfo'] = $apiRequestParam;
                            unset($apiRequestParam);
                            $apiInfo['resultInfo'] = array();

                            $apiList[] = $apiInfo;
                            unset($apiInfo);
                        }
                    }
                    $groupInfo['apiList'] = $apiList;
                    unset($apiList);
                }
            }
            $dao = new ImportDao;
            return $dao->importOther($projectInfo, $groupInfoList, $_SESSION['userID']);
        } catch (\PDOException $e) {
            return FALSE;
        }
    }

    /**
     * 导入V2版本postman
     * @param $data 从Postman V2版本导出的Json格式数据
     */
    public function importPostmanV2(&$data)
    {
        try {
            $projectInfo = array('projectName' => $data['info']['name'], 'projectType' => 0, 'projectVersion' => 1.0);

            $groupInfoList[] = array('groupName' => 'PostMan导入');

            $apiList = array();

            if (is_array($groupInfoList)) {
                foreach ($groupInfoList as &$groupInfo) {
                    if (is_array($data['item'])) {
                        foreach ($data['item'] as $item) {
                            $apiInfo['baseInfo']['apiName'] = $item['name'];
                            $apiInfo['baseInfo']['apiURI'] = $item['request']['url'];
                            $apiInfo['baseInfo']['apiProtocol'] = (strpos($item['request']['url'], 'https') !== 0) ? 0 : 1;
                            $apiInfo['baseInfo']['apiStatus'] = 0;
                            $apiInfo['baseInfo']['starred'] = 0;
                            $apiInfo['baseInfo']['apiSuccessMock'] = '';
                            $apiInfo['baseInfo']['apiFailureMock'] = '';
                            $apiInfo['baseInfo']['apiRequestParamType'] = 0;
                            $apiInfo['baseInfo']['apiRequestRaw'] = '';
                            $apiInfo['baseInfo']['apiNoteType'] = 0;
                            $apiInfo['baseInfo']['apiNote'] = '';
                            $apiInfo['baseInfo']['apiNoteRaw'] = '';
                            $apiInfo['baseInfo']['apiUpdateTime'] = date("Y-m-d H:i:s", time());
                            switch ($item['request']['method']) {
                                case 'POST' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 0;
                                    break;
                                case 'GET' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 1;
                                    break;
                                case 'PUT' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 2;
                                    break;
                                case 'DELETE' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 3;
                                    break;
                                case 'HEAD' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 4;
                                    break;
                                case 'OPTIONS' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 5;
                                    break;
                                case 'PATCH' :
                                    $apiInfo['baseInfo']['apiRequestType'] = 6;
                                    break;
                            }

                            $headerInfo = array();
                            if (is_array($item['request']['header'])) {
                                foreach ($item['request']['header'] as $header) {
                                    $headerInfo[] = array('headerName' => $header['key'], 'headerValue' => $header['value']);
                                }
                            }
                            $apiInfo['headerInfo'] = $headerInfo;
                            unset($headerInfo);

                            $apiRequestParam = array();
                            if ($item['request']['body']['mode'] == 'formdata') {
                                $parameters = $item['request']['body']['formdata'];
                                if (is_array($parameters)) {
                                    foreach ($parameters as $parameter) {
                                        $param['paramKey'] = $parameter['key'];
                                        $param['paramValue'] = $parameter['value'];
                                        $param['paramType'] = ($parameter['type'] == 'text') ? 0 : 1;
                                        $param['paramNotNull'] = $parameter['enabled'] ? 0 : 1;
                                        $param['paramName'] = '';
                                        $param['paramLimit'] = '';
                                        $param['paramValueList'] = array();
                                        $apiRequestParam[] = $param;
                                        unset($param);
                                    }
                                }
                            }
                            $apiInfo['requestInfo'] = $apiRequestParam;
                            unset($apiRequestParam);

                            $apiInfo['resultInfo'] = array();

                            $apiList[] = $apiInfo;
                            unset($apiInfo);
                        }
                    }
                    $groupInfo['apiList'] = $apiList;
                    unset($apiList);
                }
            }
            $dao = new ImportDao;
            return $dao->importOther($projectInfo, $groupInfoList, $_SESSION['userID']);
        } catch (\PDOException $e) {
            return FALSE;
        }
    }

    /**
     * 导入swagger
     * @param string $content 内容
     * @author 李业昌
     */
    public function importSwagger(&$content)
    {
        $user_id = $_SESSION['userID'];
        $swagger = json_decode($content, TRUE);
        $project_info = $swagger['info'];
        //项目类型默认web
        $project_type = '0';
        //新建一个默认的状态码分组
        $group_name = '默认分组';
        $request_type = array('POST' => '0', 'GET' => '1', 'PUT' => '2', 'DELETE' => '3', 'HEAD' => '4', 'OPTIONS' => '5', 'PATCH' => '6');
        //请求协议数组
        $protocol = array('HTTP' => '0', 'HTTPS' => '1');
        //请求参数类型数组
        $param_type = array('text' => '0', 'file' => '1', 'json' => '2', 'int' => '3', 'float' => '4', 'double' => '5', 'date' => '6', 'datetime' => '7', 'boolean' => '8', 'byte' => '9', 'short' => '10', 'long' => '11', 'array' => '12', 'object' => '13');
        //获取请求协议
        $api_protocol = $protocol[strtoupper($swagger['schemes'][0])];
        if (empty($api_protocol)) {
            $api_protocol = '0';
        }
        //如果项目描述为空，默认为title
        if (empty($project_info['description'])) {
            $project_info['description'] = $project_info['title'];
        }
        //项目信息
        $project_info = array('projectName' => $project_info['title'], 'projectType' => $project_type, 'projectVersion' => $project_info['version'], 'projectDesc' => $project_info['description']);
        $host_path = $swagger['host'] . $swagger['basePath'];
        $apiList = $swagger['paths'];
        $api_list = array();
        $apiList = array();
        $j = 0;
        //拆分多条api接口信息
        foreach ($apiList as $api_uri => $api_info_list) {
            //拆分详细api接口信息
            foreach ($api_info_list as $api_request_type => $api_info) {
                if (empty($api_info['summary'])) {
                    //如果接口名不存在跳过
                    $api_info['summary'] = $api_info['operationId'];
                }
                //获取接口名称
                $api_list[$j]['api_name'] = $api_info['summary'];
                $api_list[$j]['group_name'] = $api_info['tags'][0];
                //获取请求路径
// 	            if(strpos($uri, '{'))
// 	            {
// 	                $api_uri = preg_replace('/\{.*\}/', $api_info['operationId'], $uri);
// 	            }
// 	            else
// 	            {
// 	                $api_uri = $uri;
// 	            }
                //获取路径
                $api_list[$j]['api_uri'] = $host_path . $api_uri;
                //接口状态默认启用
                $api_list[$j]['api_status'] = '0';
                //接口请求参数的类型
                $api_list[$j]['api_request_param_type'] = '0';
                //星标状态
                $api_list[$j]['starred'] = '0';
                //接口备注的类型
                $api_list[$j]['api_note_type'] = '0';
                //获取请求方式
                $api_list[$j]['api_request_type'] = $request_type[strtoupper($api_request_type)];
                //请求头部
                $api_list[$j]['api_header'] = array();
                if ($api_info['consumes']) {
                    for ($i = 0; $i < count($api_info['consumes']); $i++) {
                        $api_list[$j]['api_header'][$i] = array('headerName' => 'Content-Type', 'headerValue' => $api_info['consumes'][$i]);
                    }
                }
                if ($api_info['produces']) {
                    for ($i = 0; $i < count($api_info['produces']); $i++) {
                        $api_list[$j]['api_header'][] = array('headerName' => 'Accept', 'headerValue' => $api_info['produces'][$i]);
                    }
                }
                //获取请求参数
                $api_request_param = array();
                if ($api_info['parameters']) {
                    $i = 0;
                    foreach ($api_info['parameters'] as $param) {

                        //获取请求参数名称
                        $api_request_param[$i]['paramKey'] = $param['name'];
                        //获取请求参数类型
                        switch ($param['type']) {
                            case "integer":
                                $api_request_param[$i]['paramType'] = $param_type['int'];
                                break;
                            case "string":
                                $api_request_param[$i]['paramType'] = $param_type['text'];
                                break;
                            case 'long':
                                $api_request_param[$i]['paramType'] = $param_type['long'];
                                break;
                            case 'float':
                                $api_request_param[$i]['paramType'] = $param_type['float'];
                                break;
                            case 'double':
                                $api_request_param[$i]['paramType'] = $param_type['double'];
                                break;
                            case 'byte':
                                $api_request_param[$i]['paramType'] = $param_type['byte'];
                                break;
                            case 'file':
                                $api_request_param[$i]['paramType'] = $param_type['file'];
                                break;
                            case 'date':
                                $api_request_param[$i]['paramType'] = $param_type['date'];
                                break;
                            case 'dateTime':
                                $api_request_param[$i]['paramType'] = $param_type['dateTime'];
                                break;
                            case 'boolean':
                                $api_request_param[$i]['paramType'] = $param_type['boolean'];
                                break;
                            case 'array':
                                $api_request_param[$i]['paramType'] = $param_type['array'];
                                break;
                            case 'json':
                                $api_request_param[$i]['paramType'] = $param_type['json'];
                                break;
                            case 'object':
                                $api_request_param[$i]['paramType'] = $param_type['object'];
                                break;
                            default:
                                $api_request_param[$i]['paramType'] = $param_type['text'];

                        }
                        //获取参数说明
                        $api_request_param[$i]['paramName'] = $param['description'];
                        //获取是否可以为空
                        $api_request_param[$i]['paramNotNull'] = $param['required'] ? 0 : 1;
                        //设置参数值示例
                        $api_request_param[$i]['paramValue'] = '';
                        ++$i;
                    }
                }
                $api_list[$j]['api_request_param'] = $api_request_param;
                //返回结果
                $api_result_param = array();
                if ($api_info['responses']) {
                    $k = 0;
                    foreach ($api_info['responses'] as $paramKey => $respon) {
                        $api_result_param[$k]['paramType'] = '';
                        //获取返回参数类型
                        switch ($respon['schema']['type']) {
                            case "integer":
                                $api_result_param[$k]['paramType'] = $param_type['int'];
                                break;
                            case "string":
                                $api_result_param[$k]['paramType'] = $param_type['text'];
                                break;
                            case 'long':
                                $api_result_param[$k]['paramType'] = $param_type['long'];
                                break;
                            case 'float':
                                $api_result_param[$k]['paramType'] = $param_type['float'];
                                break;
                            case 'double':
                                $api_result_param[$k]['paramType'] = $param_type['double'];
                                break;
                            case 'byte':
                                $api_result_param[$k]['paramType'] = $param_type['byte'];
                                break;
                            case 'file':
                                $api_result_param[$k]['paramType'] = $param_type['file'];
                                break;
                            case 'date':
                                $api_result_param[$k]['paramType'] = $param_type['date'];
                                break;
                            case 'dateTime':
                                $api_result_param[$k]['paramType'] = $param_type['dateTime'];
                                break;
                            case 'boolean':
                                $api_result_param[$k]['paramType'] = $param_type['boolean'];
                                break;
                            case 'array':
                                $api_result_param[$k]['paramType'] = $param_type['array'];
                                break;
                            case 'json':
                                $api_result_param[$k]['paramType'] = $param_type['json'];
                                break;
                            case 'object':
                                $api_result_param[$k]['paramType'] = $param_type['object'];
                                break;
                            default:
                                $api_result_param[$k]['paramType'] = $param_type['text'];
                        }
                        //获取返回参数名
                        $api_result_param[$k]['paramKey'] = $paramKey;
                        //获取返回参数说明
                        $api_result_param[$k]['paramName'] = $respon['description'];
                        //获取返回值
                        $api_result_param[$k]['paramNotNull'] = '0';
                        ++$k;
                    }
                }
                $api_list[$j]['api_result_param'] = $api_result_param;
                $api_list[$j]['api_mock_success'] = '';
                $api_list[$j]['api_mock_failure'] = '';
                $api_list[$j]['api_note_md'] = '';
                $api_list[$j]['api_note_rich'] = '';
                $api_list[$j]['api_request_raw'] = '';
                //生成缓存数据
                $api_info = array();
                $api_info['baseInfo']['apiName'] = $api_list[$j]['api_name'];
                $api_info['baseInfo']['apiURI'] = $api_list[$j]['api_uri'];
                $api_info['baseInfo']['apiProtocol'] = intval($api_protocol);
                $api_info['baseInfo']['apiSuccessMock'] = $api_list[$j]['api_mock_success'];
                $api_info['baseInfo']['apiFailureMock'] = $api_list[$j]['api_mock_failure'];
                $api_info['baseInfo']['apiRequestType'] = intval($api_list[$j]['api_request_type']);
                $api_info['baseInfo']['apiStatus'] = intval($api_list[$j]['api_status']);
                $api_info['baseInfo']['starred'] = intval($api_list[$j]['starred']);
                $api_info['baseInfo']['apiNoteType'] = intval($api_list[$j]['api_note_type']);
                $api_info['baseInfo']['apiNoteRaw'] = $api_list[$j]['api_note_md'];
                $api_info['baseInfo']['apiNote'] = $api_list[$j]['api_note_rich'];
                $api_info['baseInfo']['apiRequestParamType'] = intval($api_list[$j]['api_request_param_type']);
                $api_info['baseInfo']['apiRequestRaw'] = $api_list[$j]['api_request_raw'];
                $update_time = date("Y-m-d H:i:s", time());
                $api_info['baseInfo']['apiUpdateTime'] = $update_time;
                $api_info['headerInfo'] = $api_list[$j]['api_header'];
                $api_info['requestInfo'] = $api_request_param;
                $api_info['resultInfo'] = $api_result_param;
                $apiList[] = $api_info;
                ++$j;
            }
        }
        $group_info_list[] = array('groupName' => $group_name, 'apiList' => $apiList);
        $dao = new ImportDao;
        $result = $dao->importOther($project_info, $group_info_list, $user_id);
        if ($result) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

?>