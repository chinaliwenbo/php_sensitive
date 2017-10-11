<?php
/**
 * 敏感词过滤
 * php实现基于确定有穷自动机算法的铭感词过滤
 * 可以重写获取敏感词的方法，因为有一些敏感词是去数据库等地方获取的
 *
 * User: Lwb
 * Date: 2017/10/10
 * Time: 19:28
 */

class Sensitive{

    /**
     * @var string 索引缓存文件， 设置为空将不会缓存索引
     */
    public static $CACHE_FILE =  './sensitive/cache_tree.dat';

    /**
     * @var int 索引缓存时间，默认1个小时
     */
    public static $CACHE_TIME = 3600;


    public static $_ACTION_GETWORD = 1;
    public static $_ACTION_REPLACE = 2;
    public static $_ACTION_ISLEGAL = 3;

    /**
     * 获取敏感词库，可以自己写，比如去数据库获取敏感词等等
     * 这里默认从本类库自带的文本中读取,是测试用的敏感词
     * @return array 敏感词库数组
     */
    public static function getSensitiveWord(){
        $wordPool = file_get_contents(__DIR__ . '/sensitive/keyWord.txt');
        return explode(',', $wordPool);
    }

    /**
     * 对于敏感词的操作
     * @param $content  需要处理的内容
     * @param int $type 操作类型， 1-查找敏感词，返回数组 array 2-替换敏感词，返回替换后的内容 string  3-返回是否包含敏感词 bool
     * @param string $replaceStr  如果是替换操作，可以把敏感词替换成该字符
     * @param int $matchType 匹配或替换敏感词的个数
     * @throws Exception
     */
    public static function filter($content, $type = 1, $replaceChar = '', $matchType = 1){
        //初始化
        require_once __DIR__ . "/sensitive/SensitiveHelper.php";
        require_once __DIR__ . "/sensitive/HashMap.php";
        $instance = new SensitiveHelper();
        //设置缓存文件和缓存时长
        $instance->setCache(self::$CACHE_FILE, self::$CACHE_TIME);
        //生成关键字查询索引
        $instance->setTree( self::getSensitiveWord() );

        //进行操作
        switch ($type){
            case 2: //直接替换敏感字符，返回替换后的字符串
                return $instance->replace($content, $replaceChar , '', '', $matchType);
            case 3: //查找是否包含有，返回bool
                return $instance->islegal($content);
            case 1: //查找出敏感字符，返回敏感字符数组
            default:
                return $instance->getBadWord($content, $matchType);
        }
    }
}

/**
 * 基本调用方法示例
    //获取内容中的所有敏感词， 返回查出来的敏感词数组
    Sensitive::filter("这是一段电狗测资料泄试语句", Sensitive::$_ACTION_GETWORD);
    //指定用'*'号替换内容中的敏感词 , 返回替换后的内容
    Sensitive::filter("这是一段电狗测资料泄试语句", Sensitive::$_ACTION_REPLACE, '*');
    //判断内容中是否包含有敏感词 ，返回bool类型
    Sensitive::filter("这是一段电狗测资料泄试语句", Sensitive::$_ACTION_ISLEGAL);
 *
 *
 *
 * 其他功能
 * 1、设置自带的敏感词来源，请直接重写该类的 getSensitiveWord方法
 *
 * 2、设置敏感词索引缓存位置和缓存时间, 可直接赋值, 如果设置缓存文件为空则不缓存
    Sensitive::$CACHE_TIME = 7200;
    Sensitive::$CACHE_FILE = '/www/xx.dat';
 */
