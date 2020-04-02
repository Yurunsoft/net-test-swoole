<?php
namespace Yurun\NetTest\Http\Model;

/**
 * 测试数据
 */
class TestData
{
    /**
     * 总请求次数
     *
     * @var integer
     */
    public $totalRequest = 0;

    /**
     * 请求总耗时
     *
     * @var integer
     */
    public $totalTime = 0;
    
    /**
     * 总的数据长度
     *
     * @var integer
     */
    public $totalByteLength = 0;

    /**
     * 成功数量
     *
     * @var integer
     */
    public $success = 0;

    /**
     * 失败数量
     *
     * @var integer
     */
    public $failed = 0;

}
