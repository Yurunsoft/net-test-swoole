<?php
namespace Yurun\NetTest\Http\Model;

/**
 * 请求结果
 */
class RequestResult
{
    /**
     * 请求开始时间
     *
     * @var int
     */
    public $beginTime;

    /**
     * 请求结束时间
     *
     * @var int
     */
    public $endTime;

    /**
     * 是否成功
     *
     * @var bool
     */
    public $success;

    /**
     * 数据长度
     *
     * @var int
     */
    public $byteLength = 0;

}
