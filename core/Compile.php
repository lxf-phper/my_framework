<?php
namespace core;

//模板编译类

class Compile
{
    public $data;
    //标签库
    protected $tag = [
        'php'        => ['attr' => ''],
        'volist'     => ['attr' => 'name,id,key'],
        'foreach'    => ['attr' => 'name,item,key'],
    ];
    protected $pattern = [
        '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/',
        /*'/\{volist[\s]*name="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"[\s]*id="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"[\s]*key="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"[\s]*\}/',
        '/\{\/(volist|foreach)\}/'*/
    ];
    protected $replace = [
        "<?php echo \$this->data['\\1']; ?>",
        /*"<?php foreach (\$this->data['\\1'] as \$\\3 => \$\\2) {",
        "<?php } ?>"*/
    ];

    //编译缓存文件
    public function compileFile($content, $cacheFile)
    {
        $content = $this->parseTags($content);
        $content = $this->parseVar($content);
        file_put_contents($cacheFile, $content);
    }

    //解析变量
    protected function parseVar($content)
    {
        $search = '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/';
        /*$replace = "<?php echo \$this->data['\\1']; ?>";*/
        $replace = "<?php echo \$\\1; ?>";
        $content = preg_replace($search, $replace, $content);
        return $content;
    }

    //解析标签库
    protected function parseTags($content)
    {
        $tags = array_keys($this->tag);
        $tagName = implode('|', $tags);
        $pattern = '/\{(?:(' . $tagName . ')\b(?>[^\}]*)|\/(' . $tagName . '))\}/is';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $right = [];
            foreach ($matches as $match) {
                if ('' == $match[1][0]) {
                    $name = strtolower($match[2][0]);
                    $nodes[] = [
                        'name' => $name,
                        'begin' => $right[$name],
                        'end' => $match[0]
                    ];
                } else {
                    $right[strtolower($match[1][0])] = $match[0];
                }
            }
            unset($right,$matches);
        }
        foreach ($nodes as $key => $val) {
            $name = $val['name'];
            //解析标签属性
            $attr = $this->parseAttr($val['begin'][0]);
            switch ($name) {
                case 'volist':
                case 'foreach':
                    $content = $this->parseLoop($name, $attr, $val, $content);break;
                case 'php':
                    $content = $this->parsePhp($name, $attr, $val, $content);break;
            }
            //halt($content);
        }
        return $content;
    }


    //获取标签属性
    protected function parseAttr($str)
    {
        $pattern = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $result = '';
        if (preg_match_all($pattern, $str, $match)) {
            $name = $match['name'];
            $value = $match['value'];
            $result = array_combine($name, $value);
        }
        return $result;
    }

    /**
     * 解析循环标签
     * @param string $name 标签名称
     * @param array $attr 标签属性
     * @param array $val 正则匹配的标签内容
     * @param string $content 解析内容
     * @return mixed
     */
    protected function parseLoop($name, $attr, $val, $content)
    {
        $arr = $attr['name'];
        $key = $attr['key'] ?? 'key';
        $id = $name == 'volist' ? $attr['id'] : $attr['item'];
        //组装替换语句
        $search = [
            $val['begin'][0],
            $val['end'][0],
        ];
        $replace = [
            '<?php foreach ($'.$arr.' as $'.$key.' => $'.$id.') { ?>',
            '<?php } ?>'
        ];
        //匹配变量
        preg_match_all('/\{\$'.$id.'\.([^\}]+)+\}?/',$content,$matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $k => $v) {
            array_push($search, $v[0][0]);
            $param = "$".$id."['".$v[1][0]."']";
            array_push($replace, "<?php echo ".$param."; ?>");
        }
        $content = str_replace($search, $replace, $content);
        return $content;
    }

    /**
     * 解析php标签
     * @param string $name 标签名称
     * @param array $attr 标签属性
     * @param array $val 正则匹配的标签内容
     * @param string $content 解析内容
     * @return mixed
     */
    protected function parsePhp($name, $attr, $val, $content)
    {
        $search = [
            $val['begin'][0],
            $val['end'][0],
        ];
        $replace = [
            '<?php',
            '?>'
        ];
        $content = str_replace($search, $replace, $content);
        return $content;
    }

}
