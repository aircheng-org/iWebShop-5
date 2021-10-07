<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file log_inte.php
 * @brief 日志接口文件
 * @author nswe
 * @date 2016/6/6 22:28:37
 * @version 4.5
 */
/**
 * @brief ILog接口文件
 * @class ILog interface
 */
interface ILog
{
    /**
     * @brief 实现日志的写操作接口
     * @param string $logs 日志的内容
     */
    public function write($logs = "");
}