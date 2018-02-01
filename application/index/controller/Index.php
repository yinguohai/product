<?php
namespace app\index\controller;

use app\index\model\Product;
use think\Request ;
use app\index\model\Vender;
use app\index\logical\IndexLogical;
use think\model\Collection;
use \think\Db;

class Index extends Mycontroller
{
    function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /*
     * 删除商品的图片
     * @return int
     */
    public function delphoto_gallery(){
        $photo_id = $this->request->post('photo_id');

        $productModel = new Product();
        $result = $productModel->del_photo_gallery($photo_id);
        echo $result?$result:0;
    }

    /*
     * 获取单个商品的多个图片
     * @return json
     */
    public function getProduct_photos(){
        //$admin_info = session('admin_info');
        //$admin_info['admin_id']
        $productModel = new Product();
        $pro_id = $this->request->post('pro_id');
        $where['use_index_id'] = $pro_id;
        $where['is_del'] = 0;
        $list =  $productModel->get_photo_gallery($where);
        echo $list?$list:'0';
    }

    /*
     * 上传多张产品图片
     */
    public function upload_image(){
        $imgPath=ROOT_PATH.'public'.DS.'uploads'.DS.date('Ymd',time()).DS;
        if(!file_exists($imgPath))
            mkdir($imgPath);
        //$admin_info = session('admin_info');
        $pro_id = $this->request->post('pro_id');
        $productModel = new Product();
        $filename               = upload($_FILES['file'],$imgPath);
        $data['photo_key']      = $filename;
        $data['create_user_id'] = 1;//$admin_info['admin_id']
        $data['use_index_id']   = $pro_id;
        $data['create_time']    = date('Y-m-d H:i:s');
        $id = $productModel->saveProduct_images($data);

        $old_ids =  $productModel->getProduct(['pro_id'=>$pro_id]);
        if($old_ids['data']['img_details']){
            $new_ids = $old_ids['data']['img_details'].','.$id;
        }else{
            $new_ids = $id;
        }

        $data2['pro_id'] = $pro_id;
        $data2['img_details'] = $new_ids;
        $productModel->saveProduct($data2,true);
    }

    public function load_images(){
        return view('popups/load_images');
    }
    //=================================以上为图片上传相关接口===========

    public function index()
    {
        return view('index');
    }

    /**
     * 显示产品
     * @param array $where
     * @return string
     */
    public function showProduct()
    {
        $extwhere = array();
        if (request()->file('productFile')) {
            $file = request()->file('productFile');
            $fileType = $file->getMime();
            if (strpos($fileType, 'image') === false) {
                $this->importProduct();
            } else {
                $extwhere = $this->searchImage();
            }
        }
        $index = new Product();
        $indexLogical = new IndexLogical();
        $o_where = $this->request->post();
        $where = $indexLogical->getProductWhere($this->request->post()); //获取条件
        if ($extwhere){
            $where = $extwhere;
            $o_where['pro_id'] = implode(',',$extwhere['p.pro_id'][1]);
        }

        if($this->request->isGet()){
            if($this->request->get()){
                if ($this->request->get('pro_id')) {
                    $where['p.pro_id'] = ['in',explode(',',$this->request->get('pro_id'))];
                }else{
                    $p_w = $this->request->get();
                    $where = $indexLogical->getProductWhere_get($p_w); //获取条件
                }
            }
        }

        $result = $index->getProducts($where);
        $details = getChild($result['content'], ['v_id', 'v_num', 'v_name', 'v_source', 'v_sort'], '@@');
        $this->assign('details', $details);
        $this->assign('list', $result['list']);
        $this->assign('condition', $o_where);
        return $this->view->fetch('showProduct');
    }

    public function showTable()
    {
        return view('showTable');
    }

    public function editSource()
    {
        $proinfo = $this->request->get('vid');
        $type = $this->request->get('type');
        $vender = new Vender();
        $where['v_id'] = ['in', $proinfo];
        $vinfo = $vender->listVender($where);
        $list = \think\Config::get('source_sort');
        $this->assign('proinfo', ['vlist' => $vinfo, 'type' => $type, 'source_sort' => $list]);
        return view('editSource');
    }

    public function addSource()
    {
        $data['pro_id'] = $this->request->get('pro_id');
        $data['type'] = $this->request->get('type');
        $data['source_sort'] = \think\Config::get('source_sort');
        $this->assign('proinfo', $data);
        return $this->view->fetch('editSource');
    }

    /**
     * 保存产品来源信息
     */
    public function saveSource()
    {
        $venderinfo = $this->request->post();
        $venderinfo['v_edittime'] = time();
        $vender = new Vender();
        if ($venderinfo['type'] == 'add') {
            //添加产品源
            $result = $vender->addVender($venderinfo);
            if (!empty($result)) {
                outputJson(1, '添加成功');
            }
            outputJson(2, '添加失败');
        } else {
            $venderdata = getmultiarray($venderinfo, ['v_id', 'v_name', 'v_num', 'source_href', 'v_sort']);
            //更新产品源
            $result = $vender->editVender($venderdata);
            if (!empty($result)) {
                outputJson(1, '编辑成功');
            }
            outputJson(2, '编辑失败');
        }
    }

    public function deleteSource()
    {
        $vids = $this->request->post();
        $vender = new Vender();
        $result = $vender->deleteVender($vids['v_id']);
        if ($result['status'] == 2) {
            outputJson(2, $result['msg']);
        }
        outputJson(1, $result['msg']);
    }

    public function listVender()
    {
        //获取指定产品
        $pro_id = $this->request->post()['pro_id'];
        if (empty($pro_id))
            outputJson(2, '获取失败');
        $vender = new Vender();
        $content = $vender->listVender(['pro_id' => ['eq', $pro_id]]);
        if (empty($content))
            outputJson(2, '没有查找到相关记录');
        outputJson(1, '查询成功', count($content), $content);

    }

    public function deleteProduct()
    {
        $proid = $this->request->post();
        if (!isset($proid['pro_id']) || empty($proid['pro_id']))
            outputJson(2, '请选择要删除的产品');

        $product = new Product();
        $where['pro_id'] = ['in', $proid['pro_id']];
        $result = $product->deleteProduct($where);
        if ($result)
            outputJson(1, '删除成功');
        outputJson(2, '删除失败');
    }

    public function addProduct()
    {
        $list = \think\Config::get('source_sort');
        $type = $this->request->request('type');
        $this->assign('proinfo', ['type' => $type, 'source_sort' => $list]);
        return $this->view->fetch('editProduct');
    }

    public function saveProduct()
    {
        $content = $this->request->request();
        $fileinfo = uploadFile('img');
        if (isset($fileinfo['path']) && !empty($fileinfo['path'])) {
            $content['img'] = $fileinfo['path'];
        }
        $content['addtime'] = time();

        $product = new Product();
        $product->startTrans();
        if ($content['type'] == 'add') {
            $result = $product->saveProduct($content);
        } else {
            $result = $product->saveProduct($content, true);
        }

        if (empty($result)) {
            //保存失败，如果有上传的图片，则删除上传的图片
            if (isset($fileinfo['path']) && !empty($fileinfo['path'])) {
                $dirpath = ROOT_PATH . 'public' . DS . 'uploads';
                unlink($dirpath . '/' . $content['img']);
            }
            $product->rollback();
            outputJson(-2, '产品保存失败');
        } elseif ($content['type'] == 'add') {
            //判断是否是添加产品，是则保存一条产品源信息
            $vender = new Vender();
            $content['pro_id'] = $result;
            $content['v_edittime'] = time();
            $venderRresult = $vender->addVender($content);
            if (empty($venderRresult)) {
                $product->rollback();
                outputJson(-2, '产品保存失败[产品源保存错误]');
            }
        } elseif ($content['type'] == 'edit') {
            //成功，删除之前存在的图片，
            if (!empty($content['oldimg']))
                unlink(ROOT_PATH . 'public' . $content['oldimg']);
        }
        $product->commit();
        outputJson(1, '产品保存成功');
    }

    //编辑产品
    public function editProduct()
    {
        $type = $this->request->request('type');
        $pro_id = $this->request->request('pro_id');
        $product = new Product();
        $result = $product->getProduct(['pro_id' => $pro_id]);
        $this->assign('proinfo', ['type' => $type, 'pro_id' => $pro_id, 'info' => $result['data']]);
        if ($result['status'] != 1) {
            outputJson(-1, '请选择产品');
        }
        return $this->view->fetch('editProduct');
    }

    public function getReaderExcel($ext)
    {
        if ($ext == 'xls') {
            //读取xls文件
            vendor("PHPExcel.PHPExcel.Reader.Excel5");
            $obj = new \PHPExcel_Reader_Excel5();
        } elseif ($ext == 'xlsx') {
            //读取 xlsx文件
            vendor("PHPExcel.PHPExcel.Reader.Excel2007");
            $obj = new \PHPExcel_Reader_Excel2007();
        } else {
            $obj = null;
        }
        return $obj;
    }

    public function importFile()
    {
        $file = request()->file('productFile');
        $fileType = $file->getMime();
        if (strpos($fileType, 'image') === false) {
            //非图片
            $this->importProduct();
        } else {
            //图片,则图片搜索
           return $this->searchImage();
        }

    }

    private function searchImage()
    {
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'tmp' . DS;
        $pathDir = ROOT_PATH . 'public';
        //上传文件
        $fileInfo = uploadFile('productFile', $path, ['jpeg', 'png', 'jpg', 'gif']);
        //获取数据库中所有图片
        $product = new Product();
        $productInfo = $product->getProductImgs();

        $result = [];
        foreach ($productInfo as $k => $v) {
            //比较结果
            $flag = ImageHash::isImageFileSimilar($fileInfo['path'], $pathDir . $v['img']);
            if(!$flag && $v['photo_key'])
                $flag = ImageHash::isImageFileSimilar($fileInfo['path'], $pathDir . $v['photo_key']);
            if ($flag) {
                array_push($result, $v);
            }
        }
        //删除  上传的搜索图片
        if(file_exists($fileInfo['path']))
            unlink($fileInfo['path']);
        $where['p.pro_id'] = ['in', array_unique(array_column($result, 'pro_id'))];
        return $where;
    }

    //导入产品表
    public function importProduct()
    {
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'excel' . DS;
        $imgPath = ROOT_PATH . 'public' . DS . 'uploads' . DS . date('Ymd', time()) . DS;
        if (!file_exists($imgPath))
            mkdir($imgPath);
        $descPath = DS . 'uploads' . DS . date('Ymd', time()) . DS;
        //文件上传，自定义的
        $fileInfo = uploadFile('productFile', $path, ['xls', 'xlsx']);
        if (empty($fileInfo['filename']))
            $this->error($fileInfo['msg']);
        //加载PHPExcel类,识别  xlsx后缀文件
        $obj = $this->getReaderExcel($fileInfo['ext']);
        //加载PHPExcel类，识别   xls后缀文件
        $objFile = $obj->load($fileInfo['path']);
        $sheet = $objFile->getSheet(0);
        $pictureFiles = $sheet->getDrawingCollection();
        //获取最后一行
        $rows = $sheet->getHighestRow();
        //获取最后一列
        $columns = $sheet->getHighestColumn();
        $dataRows = [];
        $result = [];

        foreach ($sheet->getDrawingCollection() as $k => $drawing) {
            $codata = $drawing->getCoordinates(); //得到单元数据 比如G2单元
            $filenameext = pathinfo($drawing->getPath(), PATHINFO_EXTENSION);
            $filename = md5(time() . $k) . '.' . $filenameext;  //文件名
            //创建文件
            $handle = fopen($imgPath . $filename, 'w+');
            fclose($handle);
            //复制文件使用copy函数
            copy($drawing->getPath(), $imgPath . $filename);
            $dataRows[$codata] = $descPath . $filename;
        }
        //获取图片的栏目代码
        $columnCode = preg_replace('/[0-9]/', '', $codata);
        //拼接数据
        vendor('PHPExcel.PHPExcel.Shared.Date.php');
        for ($row = 2; $row <= $rows; $row++) {
            for ($column = 'A'; $column <= $columns; $column++) {
                if ($column == $columnCode && isset($dataRows[$column . $row])) {
                    //如果是图片
                    $result[$row][] = $dataRows[$column . $row];
                } elseif ($column == 'A') {
                    //如果是时间，则转时间戳
                    $val = $sheet->getCell($column . $row)->getValue();
                    $result[$row][] = \PHPExcel_Shared_Date::ExcelToPHP($val);//excel时间格式转换为时间戳PHPExcel_Shared_Date::ExcelToPHP
                } else {
                    $result[$row][] = $sheet->getCell($column . $row)->getValue();
                }
            }
        }
        $result = getChild($result, [8, 9,10], '@', false);
        $result = getChild($result, [11, 12,13], '@', false, true);
        //入库操作
        $productData = [];
        $proFiled = ['addtime', 'company', 'encode', 'pro_name', 'pro_enname', 'zone', 'status', 'img'];
        $vendorData = [];
        $errorTotal = [];
        $vendorField = ['v_name', 'v_num', 'source_href'];

        $productModel = new Product();
        $vendorModel = new Vender();
        foreach ($result as $k => $v) {
            $productData = array_combine($proFiled, array_slice($v, 0, 8));
            //校验字段的长度

            $pro_id = $productModel->saveProduct($productData);

            if ( ! is_numeric($pro_id) || empty($pro_id) ) {
                //统计插入失败的产品
                $errorTotal[$pro_id['msg']][]=$pro_id['encode'];
                //删除上传了的图片
                if(file_exists($imgPath.basename($v[7])))
                    unlink($imgPath.basename($v[7]));
                //插入失败
            } else {
                foreach ($v['source'] as $vv) {
                    //判断来源连接是否为空
                    if (empty(end($vv)))
                        continue;
                    $tmp = array_combine($vendorField, $vv);
                    $tmp['pro_id'] = $pro_id;
                    $tmp['v_edittime'] = time();
                    $tmp['v_sort'] = 5;
                    $vendorData[] = $tmp;
                    $tmp = [];
                }
                $vendor_id = $vendorModel->addVender($vendorData, true, true);
                $vendorData = [];
                if (empty($vendor_id)) {
                    $errorTotal[] = $v;
                    $productModel->deleteProduct(['pro_id' => $pro_id], true);
                    $errorTotal['vender'][]=$pro_id;
                }
            }
        }
        //组合错误信息提示
        $msg = '产品总数为 '.count($result).' 个，其中导入失败总数为 : ' . (count($errorTotal,true)-count($errorTotal))."个<hr/>";

        foreach($errorTotal as $k => $v){
            $msg .= "<br/>".$k."  : ".count($v)."个<br/><br/>".implode(' , ',$v)."<br/><hr/>";
        }

        //删除上传的excel表格文件的文件与图片
        unlink($fileInfo['path']);
        $waitTime = empty(count($errorTotal)) ? 3 : 20;
        $this->success($msg, 'showProduct', '', 100);
    }

    public function exportProduct()
    {
        $index = new Product();
        $indexLogical = new IndexLogical();
        //图片目录路径
        $imgDir = ROOT_PATH . 'public' . DS;
        //获取条件
        $where = $indexLogical->getProductWhere($this->request->post());
        $result = $index->getProducts($where);
        $details = getChild($result['content'], ['v_id', 'v_num', 'v_name', 'v_source', 'v_sort'], '@@');
        vendor('PHPExcel.PHPExcel');
        vendor('PHPExcel.PHPExcel.Writer.Excel2007');
        vendor('PHPExcel.PHPExcel.IOFactory');
        $objExcel = new \PHPExcel();
        $objWriter = new  \PHPExcel_Writer_Excel2007($objExcel);


        $objActSheet = $objExcel->getActiveSheet();
        foreach ($details as $row => $value) {
            $index = 65;
            //设置表格高度，需要一条一条设置
            $objActSheet->getRowDimension($row + 2)->setRowHeight(80);
            $objDrawing = new \PHPExcel_Worksheet_Drawing();
            $objActSheet->setCellValue(chr($index++) . ($row + 2), $value['pro_id']);
            $objActSheet->setCellValue(chr($index++) . ($row + 2), date('Y-m-d', $value['addtime']));
            $objActSheet->setCellValue(chr($index++) . ($row + 2), $value['encode']);
            $objActSheet->setCellValue(chr($index++) . ($row + 2), $value['pro_name']);
            $objActSheet->setCellValue(chr($index++) . ($row + 2), $value['pro_enname']);
            $objActSheet->setCellValue(chr($index++) . ($row + 2), $value['zone']);
            //设置图片
            if (file_exists($imgDir . $value['img'])) {
                //设置图片路径
                $objDrawing->setPath($imgDir . $value['img']);
                //设置图片高度和宽度
                $objDrawing->setHeight(80);
                $objDrawing->setWidth(80);
                //图片填充单元格
                $objDrawing->setCoordinates(chr($index++) . ($row + 2));
                //图片偏移距离
                $objDrawing->setOffsetX(12);
                $objDrawing->setOffsetY(12);
                $objDrawing->setWorksheet($objActSheet);
            } else {
                $objActSheet->setCellValue(chr($index++) . ($row + 2), '');
            }
            foreach ($value['source'] as $info) {
                $objActSheet->setCellValue(chr($index++) . (+$row + 2), $info['v_num']);
                $objActSheet->setCellValue(chr($index++) . (+$row + 2), $info['v_name']);
                $objActSheet->setCellValue(chr($index++) . (+$row + 2), $info['v_source']);
            }
        }
        //设置栏目名称
        $head = ['A' => '序号', 'B' => '时间', 'C' => '产品编号', 'D' => '中文名', 'E' => '英文名', 'F' => '投放区域', 'G' => '图片', 'H' => '货号1', 'I' => '厂家1', 'J' => '链接1', 'K' => '货号2', 'L' => '厂家2', 'M' => '链接2', 'N' => '货号3', 'O' => '厂家3', 'P' => '链接3'];
        $maxColumn = $objActSheet->getHighestColumn();
        foreach ($head as $k => $v) {
            if ($k > $maxColumn)
                break;
            $objActSheet->setCellValue($k . '1', $v);
            switch ($k) {
                case 'A':
                    $objActSheet->getColumnDimension($k)->setWidth(10);
                    break;
                case 'B':
                case 'C':
                    $objActSheet->getColumnDimension($k)->setWidth(12);
                    break;
                case 'H':
                case 'K':
                    $objActSheet->getColumnDimension($k)->setWidth(10);
                    break;
                case 'G':
                    $objActSheet->getColumnDimension($k)->setWidth(20);
                    break;
                case 'F':
                case 'I':
                case 'L':
                    $objActSheet->getColumnDimension($k)->setWidth(20);
                    break;
                case 'D':
                case 'E':
                case 'J':
                case 'M':
                case 'N':
                    $objActSheet->getColumnDimension($k)->setWidth(40);
                    break;
            }
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, "Excel2007");
        //下载表格
        ob_end_clean();    //关闭缓冲区之后再输出header头，在header设置之前，可能某个地方有了输出，导致Content-Type的类型为text/html,所以输出的表格后缀才会是html
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename='sss.xlsx'");
        $objWriter->save("php://output");

    }

}


