<?php


/**
 * 敏感词类库.
 * User: wenbo.li@gmail.com
 */
class SensitiveHelper
{
    /**
     * 待检测语句长度
     *
     * @var int
     */
    protected $contentLength = 0;

    /**
     * 铭感词库树
     *
     * @var HashMap|null
     */
    protected $wordTree = null;
    /**
     * 存放待检测语句铭感词
     *
     * @var array|null
     */
    public $badWordList;

    /**
     * @var null 缓存文件地址
     */
    private $cacheFile = null;

    /**
     * @var int 缓存文件有效时间
     */
    private $cacheExpire = 0;

    /**
     * 构建铭感词树
     *
     * @param string $sensitiveWord
     * @return $this
     */
    public function setTree()
    {
        $this->wordTree = new HashMap();

        //获取缓存文件,缓存文件有效时间1小时
        if(!empty($this->cacheFile) && file_exists($this->cacheFile) && time() - filemtime($this->cacheFile) < $this->cacheExpire){
            $this->wordTree->setJsonString(file_get_contents($this->cacheFile));
            return $this;
        }

        //获取敏感词,构造索引树
        $sensitiveWords = Sensitive::getSensitiveWord();
        usort($sensitiveWords, function($a, $b){
            return strlen($a) < strlen($b);
        });
        if (empty($sensitiveWords)) {
            throw new \Exception('词库不能为空');
        }
        foreach ($sensitiveWords as $word) {
            $this->setTreeNode($word, $this->wordTree->hashTable);
        }

        //缓存索引树
        if($this->cacheFile){
            file_put_contents($this->cacheFile, $this->wordTree->getJsonString());
        }
    }

    /**
     * 递归给数组赋值
     * @param $content
     * @param $hashMap
     */
    private function setTreeNode($content, &$hashMap){

        if(empty($content)){ //到最后一个字符了,插入结束标志
            $hashMap['ending'] = true;return ;
        }

        $keyChar = mb_substr($content, 0, 1, 'utf-8');
        $nextContent = mb_substr($content, 1, null, 'utf-8');
        // 获取子节点树结构
        if (!empty($hashMap[$keyChar])) {
            $this->setTreeNode($nextContent, $hashMap[$keyChar]);return ;

        }

        //如果不存在，则直接创建
        $hashMap['ending'] = false;
        $hashMap[$keyChar] = array();
        $this->setTreeNode($nextContent, $hashMap[$keyChar]);
    }

    /**
     * 缓存索引结构
     * @param $cacheFile 缓存文件
     * @param $expire 缓存文件的有效时间
     */
    public function setCache($cacheFile, $expire){
        $this->cacheFile = $cacheFile;
        $this->cacheExpire = $expire;
    }

    /**
     * 检测文字中的敏感词
     *
     * @param string   $content    待检测内容
     * @param int      $matchType  匹配类型【1获取首个敏感词】
     * @param string   $replace 敏感词替换字符
     * @return array
     */
    public function getBadWord($content, $matchType = 1)
    {
        $index = md5($content);
        //之前已经解析过了
        if(!empty($this->badWordList[$index])){
            return $this->badWordList[$index];
        }

        //之前没有解析过，先解析
        $this->replace($content, $matchType);
        return (array)$this->badWordList[$index];
    }

    /**
     * 替换文字中的敏感词
     *
     * @param string   $content    待检测内容
     * @param int      $matchType  匹配类型【1获取首个敏感词】
     * @param string   $replace 敏感词替换字符
     * @return array
     */
    public function replace($content, $matchType = 1, $replace = '')
    {
        $orginContent = md5($content);
        if (empty($content)) {
            throw new \Exception('请填写检测的内容');
        }

        $this->clean(); // 清空之前的记录
        $this->contentLength = mb_strlen($content, 'utf-8');
        for ($length = 0; $length < $this->contentLength; $length++) {
            $matchFlag = 0;
            $flag = false;
            $tempMap = $this->wordTree->hashTable;
            for ($i = $length; $i < $this->contentLength; $i++) {
                $keyChar = mb_substr($content, $i, 1, 'utf-8');
                // 获取指定节点树
                $nowMap = $this->wordTree->get($keyChar, $tempMap);
                // 不存在节点树，直接返回
                if (empty($nowMap)) break;
                // 存在，则判断是否为最后一个
                $tempMap = $nowMap;
                // 找到相应key，偏移量+1
                $matchFlag ++;
                // 如果为最后一个匹配规则,结束循环，返回匹配标识数
                if (false == $this->wordTree->get('ending', $nowMap) ) continue;
                $flag = true;
                // 最小规则，直接退出
                if (1 === $matchType)  break;
            }
            if (! $flag) {
                $matchFlag = 0;
            }
            // 找到相应key
            if ($matchFlag <= 0) {
                continue;
            }

            //记录敏感词
            $this->badWordList[$orginContent][] = mb_substr($content, $length, $matchFlag, 'utf-8');
            //替换敏感词
            if(!empty($replace)){
                $s = mb_substr($content, 0, $length, 'utf-8');
                $e = mb_substr($content, $length + $matchFlag , null, 'utf-8');
                $content = $s . $replace . $e;
            }
            // 需匹配内容标志位往后移
            $length = $length + $matchFlag - 1;

        }
        return $content;
    }

    /**
     * 检测内容是否包含敏感词
     * 实现与getBadWord类似， 只要检测要有敏感词直接就退出了，不会搜索所有的敏感词
     * @param $content
     * @return bool
     */
    public function islegal($content)
    {
        $this->contentLength = mb_strlen($content, 'utf-8');
        for ($length = 0; $length < $this->contentLength; $length++) {
            $matchFlag = 0;
            $tempMap = $this->wordTree->hashTable;
            for ($i = $length; $i < $this->contentLength; $i++) {
                $keyChar = mb_substr($content, $i, 1, 'utf-8');
                // 获取指定节点树
                $nowMap = $this->wordTree->get($keyChar, $tempMap);
                // 不存在节点树，直接返回
                if (empty($nowMap)) break;
                // 存在，则判断是否为最后一个
                $tempMap = $nowMap;
                // 找到相应key，偏移量+1
                $matchFlag ++;
                // 如果为最后一个匹配规则,结束循环，返回匹配标识数
                if (false === $this->wordTree->get('ending', $nowMap) ) continue;
                return true;
            }
            // 找到相应key
            if ($matchFlag <= 0) {
                continue;
            }
            // 需匹配内容标志位往后移
            $length = $length + $matchFlag - 1;
        }

        return false;
    }

    /**
     * 清空所有属性
     */
    private function clean(){
        $this->badWordList = array();
        $this->contentLength = 0;
    }
}
