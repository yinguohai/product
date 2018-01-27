<?php
namespace app\index\controller;

class ImageHash
{
    /**取样倍率 1~10
     * @access public
     * @staticvar int
     * */
    public static $rate = 2;

    /**相似度允许值 0~64
     * @access public
     * @staticvar int
     * */
    public static $similarity = 80;

    /**图片类型对应的开启函数
     * @access private
     * @staticvar string
     * */
    private static $_createFunc = array(
        IMAGETYPE_GIF =>'imageCreateFromGIF',
        IMAGETYPE_JPEG=>'imageCreateFromJPEG',
        IMAGETYPE_PNG =>'imageCreateFromPNG',
        IMAGETYPE_BMP =>'imageCreateFromBMP',
        IMAGETYPE_WBMP=>'imageCreateFromWBMP',
        IMAGETYPE_XBM =>'imageCreateFromXBM',
    );

    /**从文件建立图片
     * @param string $filePath 文件地址路径
     * @return resource 当成功开启图片则传递图片 resource ID，失败则是 false
     * */
    public static function createImage($filePath){
        if(!file_exists($filePath)){ return false; }

        /*判断文件类型是否可以开启*/
        $type = exif_imagetype($filePath);
        if(!array_key_exists($type,self::$_createFunc)){ return false; }

        $func = self::$_createFunc[$type];
        if(!function_exists($func)){ return false; }

        return $func($filePath);
    }

    /**hash 图片
     * @param resource $src 图片 resource ID
     * @return string 图片 hash 值，失败则是 false
     * */
    public static function hashImage($src){
        if(!$src){ return false; }

        /*缩小图片尺寸*/
        $delta = 8 * self::$rate;
        $img = imageCreateTrueColor($delta,$delta);
        imageCopyResized($img,$src, 0,0,0,0, $delta,$delta,imagesX($src),imagesY($src));

        /*计算图片灰阶值*/
        $grayArray = array();
        for ($y=0; $y<$delta; $y++){
            for ($x=0; $x<$delta; $x++){
                $rgb = imagecolorat($img,$x,$y);
                $col = imagecolorsforindex($img, $rgb);
                $gray = intval(($col['red']+$col['green']+$col['blue'])/3)& 0xFF;

                $grayArray[] = $gray;
            }
        }
        imagedestroy($img);

        /*计算所有像素的灰阶平均值*/
        $average = array_sum($grayArray)/count($grayArray);

        /*计算 hash 值*/
        $hashStr = '';
        foreach ($grayArray as $gray){
            $hashStr .= ($gray>=$average) ? '1' : '0';
        }
        return $hashStr;
    }

    /**hash 图片文件
     * @param string $filePath 文件地址路径
     * @return string 图片 hash 值，失败则是 false
     * */
    public static function hashImageFile($filePath){
        $src = self::createImage($filePath);
        $hashStr = self::hashImage($src);
        imagedestroy($src);

        return $hashStr;
    }

    /**比较两个 hash 值，是不是相似
     * @param string $aHash A图片的 hash 值
     * @param string $bHash B图片的 hash 值
     * @return bool 当图片相似则传递 true，否则是 false
     * */
    public static function isHashSimilar($aHash, $bHash){
        $aL = strlen($aHash); $bL = strlen($bHash);
        if ($aL !== $bL){ return false; }

        /*计算容许落差的数量*/
        $allowGap = $aL*(100-self::$similarity)/100;

        /*计算两个 hash 值的汉明距离*/
        $distance = 0;
        for($i=0; $i<$aL; $i++){
            if ($aHash{$i} !== $bHash{$i}){ $distance++; }
        }

        return ($distance<=$allowGap) ? true : false;
    }

    /**比较两个图片文件，是不是相似
     * @param string $aHash A图片的路径
     * @param string $bHash B图片的路径
     * @return bool 当图片相似则传递 true，否则是 false
     * */
    public static function isImageFileSimilar($aPath, $bPath){
        $aHash = ImageHash::hashImageFile($aPath);
        $bHash = ImageHash::hashImageFile($bPath);
        return ImageHash::isHashSimilar($aHash, $bHash);
    }
}