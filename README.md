# php_sensitive
This is developed using PHP, which is used to filter sensitive words in text。
This project is simple but strong。
use index tree to speeding up large text sensitive word filtering at millisecond level。


#php version
PHP >= 5.4(TEST IN PHP5.4.41)

#INSTALL
clone the code to your local project

#how to use 

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
 * 1、设置自带的敏感词来源，请直接重写Sensitive类的 getSensitiveWord方法
 *
 * 2、设置敏感词索引缓存位置和缓存时间, 可直接赋值, 如果设置缓存文件为空则不缓存
    Sensitive::$CACHE_TIME = 7200;
    Sensitive::$CACHE_FILE = '/www/xx.dat';
 */

#Matters needing attention
本过滤默认开启了词库缓存,有效期为3600 S。
本项目中tree node并没有使用对象，因为对象不方便缓存到本次磁盘文件中。
