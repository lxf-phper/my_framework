<?php

namespace core;

/**
 * Class Socket
 * @package core
 */
class Socket
{
    //D:\phpstudy\PHPTutorial\php\php-7.0.12-nts\php.exe socket_server.php
    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * socket池
     * @var array
     */
    protected $socketPool = [];

    /**
     * 用户信息池
     * @var array
     */
    protected $userPool = [];

    /**
     * 服务器的socket
     * @var resource
     */
    protected $mainSocket;

    /**
     * Websocket constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        // 加载配置
        $this->loadConfig($options);
        // 初始化
        $this->init();
    }

    /**
     * 加载配置
     * @param $options
     */
    private function loadConfig($options)
    {
        $this->config = Config::get('websocket');
        if (!empty($options)) {
            $this->config = array_merge($this->config, $options);
        }
        if (!empty($this->config)) {
            $this->config = [
                'address'           => "127.0.0.1", //IP地址
                'port'              => 8081, //指定连接中需要监听的端口号
                'listen_socket_num' => 4, //最大连接数
            ];
        }
    }

    /**
     * 初始化
     */
    public function init()
    {
        // 创建一个套接字(通讯节点)
        $this->mainSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        // 设置socket选项(设置IP和端口重用,在重启服务器后能重新使用此端口)
        socket_set_option($this->mainSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        // 给套接字绑定名字(绑定 address 到 socket)
        socket_bind($this->mainSocket, $this->config['address'], $this->config['port']);
        // 监听套接字的连接
        socket_listen($this->mainSocket, $this->config['listen_socket_num']);
        $this->socketPool[guid()] = $this->mainSocket;

        pt_progress(
            'SERVER: ' . $this->mainSocket . ' started | ' .
            'LISTEN ON: ' . $this->config['address'] . ':' . $this->config['port'] . ' | ' .
            'PID: ' . getmypid()
        );
    }

    /**
     * 执行函数
     * @return null
     */
    public function run()
    {
        while (true) {
            dump([$this->socketPool,$this->userPool]);
            $socketPool = $this->getSockets();
            // 接受套接字数组并等待它们更改状态(阻塞进程,直到有socket接入)
            $readNum = socket_select($socketPool, $write, $except, null);
            if ($readNum == false) {
                $msg = "socket_select() failed: reason: " . socket_strerror(socket_last_error());
                pt_progress($msg);
                return null;
            }
            foreach ($socketPool as $uniqid => $socket) {
                if ($socket == $this->mainSocket) {
                    // 如果是服务器的socket,则处理连接逻辑
                    $this->handleServerSocketAccept($socket);
                } else {
                    // 如果可读的是其他已连接 socket ,则读取其数据,并处理应答逻辑
                    $this->handleClientSocketResponse($socket);
                }
            }
        }
    }

    /**
     * 服务端socket,处理客户端连接逻辑
     * @param $socket
     */
    public function handleServerSocketAccept($socket)
    {
        // 如果可读的是服务器的socket,则处理连接逻辑
        $resource = socket_accept($socket);
        if ($resource != false) {
            $this->setSockets($resource);
        } else {
            $msg = "socket_accept() failed: reason: " . socket_strerror(socket_last_error());
            pt_progress($msg);
        }
    }

    /**
     * 客户端socket,读取其数据,并处理应答逻辑
     * @param $socket
     * @return bool
     */
    public function handleClientSocketResponse($socket)
    {
        $uniqid = $this->searchSocket($socket);
        if (!$uniqid) return false;

        // 如果可读的是其他已连接 socket ,则读取其数据,并处理应答逻辑
        //$buffer = socket_read($socket, 8192, PHP_BINARY_READ);
        $len = socket_recv($socket, $buffer, 8192, 0);
        if ($this->userPool[$uniqid]['handshake'] == false) {
            // 握手
            $this->handshake($uniqid, $buffer);
        } else {
            // 广播信息
            $broadcastMsg = $this->handleMsg($uniqid, $buffer);
            $this->broadcast($broadcastMsg);
        }
        return true;
    }

    /**
     * 广播信息
     * @param $msg
     */
    public function broadcast($msg)
    {
        foreach ($this->userPool as $uniqid => $user) {
            if (isset($user['handshake']) && !empty($user['handshake']) && is_resource($user['socket'])) {
                socket_write($user['socket'], $msg, strlen($msg));
            }
        }
    }

    //处理客户端的消息
    public function handleMsg($uniqid, $buffer)
    {
        $msg = $this->decode($uniqid, $buffer);
        $broadcastMsg = '';
        $type = $msg['type'];
        $content = isset($msg['content']) ? $msg['content'] : '';
        unset($msg['type']);
        switch ($type) {
            case 'login' :
                $this->userPool[$uniqid]['username'] = $content['user_name'];
                $msg = [
                    'type'    => $type,
                    'content' => [
                        'user_name' => $content['user_name'],
                        'user_list' => array_column($this->userPool, 'username'),
                    ]
                ];
                $broadcastMsg = json_encode($msg);
                break;
            case 'draw' :
                $msg = [
                    'type'    => $type,
                    'content' => $content,
                ];
                $broadcastMsg = json_encode($msg);
                break;
            case 'clear' :
                $msg = [
                    'type' => $type
                ];
                $broadcastMsg = json_encode($msg);
                break;
            case 'dialog' :
                $msg = [
                    'type'    => $type,
                    'content' => $content
                ];
                $broadcastMsg = json_encode($msg);
                break;
        }
        return $this->undecode($broadcastMsg);
    }

    /**
     * websocket公共握手方法握手
     * @param $uniqid
     * @param $buffer
     * @return bool
     */
    public function handshake($uniqid, $buffer)
    {
        // WebSocket 客户端连接报文
        $key = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);
        $key = trim(substr($key, 0, strpos($key, "\r\n")));
        $key .= '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $upgrade_key = base64_encode(sha1($key, true));
        // WebSocket 服务端响应报文
        $response = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: " . $upgrade_key . "\r\n\r\n";

        $socket = $this->socketPool[$uniqid];
        socket_write($socket, $response, strlen($response)); // 向socket里写入升级信息
        $this->userPool[$uniqid]['handshake'] = true; //修改当前socket的握手状态
        // 向客户端发送握手成功信息，触发客户端发送用户信息
        $msg = [
            'type' => 'handshake',
        ];
        $msg = json_encode($msg);
        $msg = $this->undecode($msg);
        socket_write($socket, $msg, strlen($msg));

//        socket_getpeername($socket, $addr, $port);
//        pt_progress(
//            'CLIENT: ' . $socket . ' handshake | ' .
//            'CONNECT FROM: ' . $addr . ':' . $port . ' | ' .
//            'PID: ' . getmypid()
//        );
        return true;
    }

    /**
     * 解析数据帧(1Byte=8bit)
     * 一个英文字母（不分大小写）占一个字节的空间，一个中文汉字占两个字节的空间。一个二进制数字序列，在计算机中作为一个数字单元，一般为8位二进制数，换算为十进制。最小值0，最大值255。如一个ASCII码就是一个字节。
     * @param $uniqid
     * @param $buffer
     * @return mixed|null
     */
    public function decode($uniqid, $buffer)
    {
        $opcode = ord(substr($buffer, 0, 1)) & 0x0F; //opcode标识数据类型,如果收到一个未知的操作码，接收端点必须_失败WebSocket连接
        $payloadlen = ord(substr($buffer, 1, 1)) & 0x7F; //PayloadLen表示数据部分的长度
        $ismask = (ord(substr($buffer, 1, 1)) & 0x80) >> 7; //MASK标识这个数据帧的数据是否使用掩码，定义payload数据是否进行了掩码处理，如果是1表示进行了掩码处理。Masking-key域的数据即是掩码密钥，用于解码PayloadData。客户端发出的数据帧需要进行掩码处理，所以此位是1。
        $maskkey = null;
        $oridata = null;
        $decodedata = null;

        // 关闭socket连接
        if ($ismask != 1 || $opcode == 0x8) {
            $this->disconnect($uniqid);
            return null;
        }

        if ($payloadlen <= 125 && $payloadlen >= 0) {
            $maskkey = substr($buffer, 2, 4);
            $oridata = substr($buffer, 6);
        } else if ($payloadlen == 126) {
            $maskkey = substr($buffer, 4, 4);
            $oridata = substr($buffer, 8);
        } else if ($payloadlen == 127) {
            $maskkey = substr($buffer, 10, 4);
            $oridata = substr($buffer, 14);
        }

        $len = strlen($oridata);
        for ($i = 0; $i < $len; $i++) {
            $decodedata .= $oridata[$i] ^ $maskkey[$i % 4];
        }
        return json_decode($decodedata, true);
    }

    /**
     * 把发送信息组成websocket数据帧
     * @param string $msg
     * @param int $opcode
     * @return string|null
     */
    public function undecode($msg = "", $opcode = 0x1)
    {
        //control bit, default is 0x1(text data)
        $firstByte = 0x80 | $opcode;
        $encodedata = null;
        $len = strlen($msg);

        if (0 <= $len && $len <= 125) {
            $encodedata = chr(0x81) . chr($len) . $msg;
        } else if (126 <= $len && $len <= 0xFFFF) {
            $low = $len & 0x00FF;
            $high = ($len & 0xFF00) >> 8;
            $encodedata = chr($firstByte) . chr(0x7E) . chr($high) . chr($low) . $msg;
        }

        return $encodedata;
    }

    /**
     * 记录socket连接
     * @param $resource
     */
    public function setSockets($resource)
    {
        $uid = guid();
        $this->socketPool[$uid] = $resource;
        $this->userPool[$uid] = [
            'socket'  => $resource,
            'username'  => '',
            'handshake' => false
        ];

        socket_getpeername($resource, $addr, $port);
        pt_progress(
            'CLIENT: ' . $resource . ' connect | ' .
            'CONNECT FROM: ' . $addr . ':' . $port . ' | ' .
            'PID: ' . getmypid()
        );
    }

    /**
     * 获取socket数组
     * @return array
     */
    public function getSockets()
    {
        foreach ($this->socketPool as $uniqid => $socket) {
            if (!is_resource($socket)) {
                $this->disconnect($uniqid);
            }
        }
        return $this->socketPool;
    }

    /**
     * 根据socket资源符查找socket的id
     * @param $resource
     * @return int|string|null 唯一的id
     */
    public function searchSocket($resource)
    {
        foreach ($this->socketPool as $uniqid => $socket) {
            if ($resource == $socket) {
                return $uniqid;
            }
        }
        return null;
    }

    /**
     * 关闭socket连接
     * @param $uniqid
     */
    public function disconnect($uniqid)
    {
        $socket = $this->socketPool[$uniqid];
        socket_getpeername($socket, $addr, $port);
        pt_progress(
            'CLIENT: ' . $socket . ' close | ' .
            'CONNECT FROM: ' . $addr . ':' . $port . ' | ' .
            'PID: ' . getmypid()
        );
        socket_close($socket);
        unset($this->socketPool[$uniqid], $this->userPool[$uniqid]);
    }

    // 记录日志
    public function writeLog($msg)
    {
        if (is_array($msg)) {
            return;
        }
        $message = '[ ' . date('Y-m-d H:i:s') . " ]  " . $msg . "\n";
        // 路径
        $path = str_replace('\\', '/', '../runtime/drawLog/' . date('Ym') . '/');
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        $filePath = $path . date('Y-m-d') . '-log.txt';
        file_put_contents($filePath, $message, FILE_APPEND);
    }
}
