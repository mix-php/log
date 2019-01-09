<?php

namespace Mix\Log;

use Mix\Core\DIObject;
use Mix\Helpers\FileSystemHelper;

/**
 * FileHandler类
 * @author LIUJIAN <coder.keda@gmail.com>
 */
class FileHandler extends DIObject implements HandlerInterface
{

    /**
     * 轮转规则
     */
    const ROTATE_HOUR = 0;
    const ROTATE_DAY = 1;
    const ROTATE_WEEKLY = 2;

    /**
     * 日志目录
     * @var string
     */
    public $dir = 'logs';

    /**
     * 日志轮转类型
     * @var int
     */
    public $rotate = self::ROTATE_DAY;

    /**
     * 最大文件尺寸
     * @var int
     */
    public $maxFileSize = 0;

    /**
     * 写入日志
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     */
    public function write($level, $message, array $context = [])
    {
        $file    = $this->getFile($level);
        $message = $this->getMessage($message, $context);
        return error_log($message . PHP_EOL, 3, $file);
    }

    /**
     * 获取要写入的文件
     * @param $level
     * @return string
     */
    protected function getFile($level)
    {
        // 生成文件名
        $logDir = $this->dir;
        if (!FileSystemHelper::isAbsolute($logDir)) {
            $logDir = \Mix::$app->getRuntimePath() . DIRECTORY_SEPARATOR . $this->dir;
        }
        switch ($this->rotate) {
            case self::ROTATE_HOUR:
                $subDir     = date('Ymd');
                $timeFormat = date('YmdH');
                break;
            case self::ROTATE_DAY:
                $subDir     = date('Ym');
                $timeFormat = date('Ymd');
                break;
            case self::ROTATE_WEEKLY:
                $subDir     = date('Y');
                $timeFormat = date('YW');
                break;
        }
        $filename = $logDir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . "{$level}_{$timeFormat}";
        $file     = "{$filename}.log";
        // 创建目录
        $dir = dirname($file);
        is_dir($dir) or mkdir($dir, 0777, true);
        // 尺寸轮转
        $number = 0;
        while (file_exists($file) && $this->maxFileSize > 0 && filesize($file) >= $this->maxFileSize) {
            $file = "{$filename}_" . ++$number . '.log';
        }
        // 返回
        return $file;
    }

    /**
     * 获取要写入的消息
     * @param $message
     * @param array $context
     * @return string
     */
    protected function getMessage($message, array $context = [])
    {
        // 替换占位符
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }
        $message = strtr($message, $replace);
        // 增加时间
        $time    = date('Y-m-d H:i:s');
        $message = "[time] {$time} [message] {$message}";
        return $message;
    }

}
