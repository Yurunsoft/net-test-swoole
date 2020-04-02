# Swoole 压测工具

这是宇润使用 Swoole 语言开发的一个 Http 压测工具。

Go 版本：<https://github.com/Yurunsoft/net-test-go>

压测命令：`php net-test.php http -u http://127.0.0.1:8080 -c 100 --number 100000`

参数说明：

```shell
-u, --url=URL          压测地址
-c, --co[=CO]          并发数（协程数量） [default: 100]
    --number[=NUMBER]  总请求次数 [default: 100]
```
