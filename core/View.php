<?php

namespace core;

use core\Router;
use core\Compile;

/**
 * 模板引擎
 * Class View
 * @package core
 */
class View
{
    protected $data = [];//模板变量
    //模板引擎配置
    protected $config = [
        'tpl_cache' => true, //是否开启模板编译缓存
        'view_suffix' => 'html', //模板文件后缀
        'cache_suffix' => 'php', //缓存文件后缀
        'compile_dir' => 'runtime/temp', //编译文件的保存目录
        'cache_time' => 1, // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
    ];
    private static $instance;//模板实例
    protected $compile;//编译编译类的实例

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->compile = new Compile();
    }

    /**
     * 初始化模板
     * @return View
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self([]);
        }
        return self::$instance;
    }

    /**
     * 模板变量赋值 assign('username','admin') 或 assign(['user_id'=>1,'username'=>'admin'])
     * @param array|string $key 变量名
     * @param string|null $value 变量值
     * @return $this
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {//如果是数组
            foreach ($key as $k => $v) {
                $this->data[$k] = $v;
            }
        } else if (is_string($key)) {//如果是字符串
            $this->data[$key] = $value;
        }
        return $this;
    }

    /**
     * 模板渲染输出
     * fetch('',['user_id'=>1,'username'=>'admin']) 或 fetch('login/login')
     * @param string $template
     * @param array $data
     */
    public function fetch($template = '', $data = [])
    {
        //模板变量赋值
        if (!empty($data) && is_array($data)) {
            foreach ($data as $k => $v) {
                $this->data[$k] = $v;
            }
        }
        $template = $this->getTemplatePath($template);
        if ($template) {
            //文件路径
            if (!is_file($template)) {//没有文件，报错
                exit('没有模板文件'.$template);
            } else {
                $cacheFile = $this->getCachePath($template);
                //查询是否需要编译
                if ($this->checkCache($cacheFile, $this->config['cache_time'])) {
                    //过期，重新编译缓存文件
                    $content = file_get_contents($template);
                    $this->compile->data = $this->data;
                    $this->compile->compileFile($content, $cacheFile);//编译缓存文件
                }
                ob_start();
                extract($this->data, EXTR_OVERWRITE);
                include $cacheFile;
                $content = ob_get_clean();
            }
        }
        echo $content;
    }


    /**
     * 是否开启模板编译缓存
     * @return bool
     */
    protected function needCache()
    {
        return $this->config['tpl_cache'];
    }

    /**
     * 获取模板文件的路径(绝对路径)
     * 用法：当前模块getTemplatePath('login/login')；跨模块：getTemplatePath('admin@login/login')
     * @param string $template
     * @return string
     */
    protected function getTemplatePath($template = '')
    {
        if (!empty($template)) {
            if (strpos($template, '@')) {
                list($module, $template) = explode('@', $template);
            }
            if (strpos($template, '/')) {
                list($controller, $action) = explode('/', $template);
            }
        }
        $router = new Router();
        $module = isset($module) ? $module : $router->getModule();
        $controller = isset($controller) ? $controller : $router->getController();
        $action = isset($action) ? $action : $router->getAction();
        $path = APP_PATH . $module . DS . 'view' . DS . $controller . DS . $action . '.' . $this->config['view_suffix'];
        return $path;
    }

    //获取缓存文件路径
    protected function getCachePath($cache = '')
    {
        $cache = ROOT_PATH . $this->config['compile_dir'] . DS . md5($cache) . '.' . $this->config['cache_suffix'];
        return $cache;
    }

    /**
     * 检查是否需要编译缓存文件(是否开启缓存,是否过期)
     * @param $cacheFile
     * @param $cacheTime
     * @return bool
     */
    protected function checkCache($cacheFile, $cacheTime)
    {
        if (!$this->needCache()) {
            return true;
        }
        if (!is_file($cacheFile)) {
            return true;
        }
        if ($cacheTime != 0 || $_SERVER['REQUEST_TIME'] > filemtime($cacheFile) + $cacheTime) {
            return true;
        }
        return false;
    }
}