<?php
namespace Yurun\NetTest\Http;

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\WaitGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yurun\NetTest\Http\Model\RequestResult;
use Yurun\NetTest\Http\Model\TestData;

class HttpCommand extends Command
{
    /**
     * 地址
     *
     * @var string
     */
    protected $url;

    /**
     * 协程数量
     *
     * @var int
     */
    protected $co;

    /**
     * 总请求次数
     *
     * @var int
     */
    protected $number;

    /**
     * 每个请求的结果通道
     *
     * @var \Swoole\Coroutine\Channel
     */
    private $channel;

    /**
     * 请求计数
     *
     * @var integer
     */
    private $requestCount = 0;

    /**
     * parse_url() 返回的数组
     *
     * @var array
     */
    private $urlInfo;

    /**
     * 测试结果数据
     *
     * @var \Yurun\NetTest\Http\Model\TestData
     */
    private $testData;

    /**
     * 进度
     *
     * @var int
     */
    private $progress = 0;

    /**
     * output
     *
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('http')
            ->setDescription('Http 压测')
            ->setHelp('Http 压测工具')
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED, '压测地址')
            ->addOption('co', 'c', InputOption::VALUE_OPTIONAL, '并发数（协程数量）', 100)
            ->addOption('number', null, InputOption::VALUE_OPTIONAL, '总请求次数', 100)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->url = $input->getOption('url');
        $this->co = (int)$input->getOption('co');
        $this->number = (int)$input->getOption('number');
        $this->urlInfo = parse_url($this->url);
        if(!$this->urlInfo)
        {
            $output->writeln(sprintf('Invalid url %s', $this->url));
        }
        $this->output = $output;
        \Co\run(function(){
            $this->testData = new TestData;
            // 请求结果通道
            $this->channel = new Channel($this->co);
            $tasksGroup = new WaitGroup;
            $parseGroup = new WaitGroup;
            $tasksGroup->add($this->co);
            $parseGroup->add(1);
            // 接收请求响应结果协程
            go(function() use($parseGroup){
                defer(function() use($parseGroup){
                    $parseGroup->done();
                });
                $this->parseResult();
            });
            // 请求工作协程
            for($i = 0; $i < $this->co; ++$i)
            {
                go(function() use($tasksGroup){
                    defer(function() use($tasksGroup){
                        $tasksGroup->done();
                    });
                    $this->testTask();
                });
            }
            $tasksGroup->wait();
            $this->channel->close();
            $parseGroup->wait();
        });
        return 0;
    }

    /**
     * 测试任务
     *
     * @return void
     */
    public function testTask()
    {
        $ssl = 'https' === $this->urlInfo['scheme'];
        if(isset($this->urlInfo['port']))
        {
            $port = $this->urlInfo['port'];
        }
        else if($ssl)
        {
            $port = 443;
        }
        else
        {
            $port = 80;
        }
        $path = $this->urlInfo['path'] ?? '/';
        $client = new Client($this->urlInfo['host'], $port, $ssl);
        $client->setMethod('GET');
        $client->set([
            'timeout'   =>  -1,
        ]);
        while($this->requestCount < $this->number)
        {
            ++$this->requestCount;
            $result = new RequestResult;
            $result->beginTime = microtime(true);
            $client->execute($path);
            $result->endTime = microtime(true);
            if($result->success = (200 === $client->statusCode))
            {
                $result->byteLength = strlen($client->body);
            }
            $this->channel->push($result);
        }
        $client->close();
    }

    /**
     * 处理结果
     *
     * @return void
     */
    public function parseResult()
    {
        $beginTime = microtime(true);
        /** @var RequestResult $item */
        while($item = $this->channel->pop())
        {
            if($item->success)
            {
                ++$this->testData->success;
            }
            else
            {
                ++$this->testData->failed;
            }
            ++$this->testData->totalRequest;
            $this->testData->totalTime += $item->endTime - $item->beginTime;
            $this->testData->totalByteLength += $item->byteLength;
            if ($this->testData->totalRequest >= $this->number * (($this->progress + 1) / 10))
            {
                ++$this->progress;
                $this->output->writeln(sprintf('Completed %d requests', $this->testData->totalRequest));
            }
        }
        $since = microtime(true) - $beginTime;
        $this->output->writeln("\nTest result:");
        $this->output->writeln(sprintf('Total requests: %d', $this->testData->totalRequest));
        $this->output->writeln(sprintf('Total time: %s s', $since));
        $this->output->writeln(sprintf('Success requests: %d', $this->testData->success));
        $this->output->writeln(sprintf('Failed requests: %d', $this->testData->failed));
        $this->output->writeln(sprintf('Transfer bytes: %d bytes', $this->testData->totalByteLength));
        $this->output->writeln(sprintf('Time per request: %s ms', $this->testData->totalTime / $this->testData->totalRequest * 1000));
        $this->output->writeln(sprintf('Transfer rate: %f Kb/s', $this->testData->totalByteLength / $since / 1024));
        $this->output->writeln(sprintf('Requests per second: %f/s', 1 / $since * $this->testData->totalRequest));
    }

}