<?php
namespace app\api\controller;
use app\api\model\CzLists;
use app\api\service\Utils;
use app\lib\enum\ApiEnum;
use think\Db;
use think\Request;
use think\response\Json;

class Index extends Base 
{

    public function get_red_list(){
    	$list = model("Redpack")->select();
    	$data=[];
    	foreach ($list as $k => $val) {
    		$data[] = [
    			'id'=>$val['redpack_id'],
    			'longitude'=>$val['long'],
    			'latitude'=>$val['lat'],
    			'iconPath'=>'../../static/foot/redpack2.png'
    		];
    	}
    	// $list = [
    	// 	['id'=>1, 'longitude'=>113.800933,'latitude'=>34.794943,'iconPath'=>'../../static/foot/redpack2.png'],
    	// 	['id'=>1, 'longitude'=>113.800833,'latitude'=>34.794943,'iconPath'=>'../../static/foot/redpack2.png'],
    	// 	['id'=>1, 'longitude'=>113.800733,'latitude'=>34.794943,'iconPath'=>'../../static/foot/redpack2.png'],
    	// 	['id'=>1, 'longitude'=>113.800633,'latitude'=>34.794943,'iconPath'=>'../../static/foot/redpack2.png']
    	// ];
    	return json($data);
    }

    /**
     * 根据经纬度查询范围内的红包
     * @param Request $request
     * @return Json
     */
    public function seekBonus(Request $request)
    {
        $long = $request->param("long");
        $lat = $request->param("lat");
        $sql = "SELECT * FROM  (SELECT l.redpack_id,l.long,l.lat,l.status, ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-`lat`*PI()/180)/2),2)+COS(".$lat."*PI()/180)*COS(`lat`*PI()/180)*POW(SIN((".$long."*PI()/180-`long`*PI()/180)/2),2)))*1000) as`juli` FROM redpack as l ) as a WHERE a.juli<1000 and a.status = 1 LIMIT 10";
        $res = Db::query($sql);
        return json(Utils::jsonReturn($res),200);
    }

    /**
     * 根据经纬度查询城主是否存在
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function seekCz(Request $request)
    {
        $long = $request->param("long");
        $lat = $request->param("lat");
        $where = $this->getAreaBylongAndLat($long,$lat);
        $agent = CzLists::where($where)->find();
        $result = [];
        if(!empty($agent) && !empty($agent->user_id)){
            $result = $agent;
        }
        return json(Utils::jsonReturn($result),200);
    }

    protected function getAreaBylongAndLat($long,$lat) //   北京 直辖市
    {
        $url = sprintf(ApiEnum::GEOMAP,ApiEnum::GEOAK,$long,$lat);
            // 执行请求
        $where = 1 ;
        $content = file_get_contents($url);
        if(is_string($content) && !empty($content)) {
            $res = json_decode($content,true);
            $where =  $this->getDetailsByobject($res);
        }
        return $where;

    }

    protected function getDetailsByobject($res)
    {
        $address = [] ;
        if(isset($res['regeocode']['addressComponent'])){
            $address = [
                'province' => isset($res['regeocode']['addressComponent']['province'])?$res['regeocode']['addressComponent']['province']:'',
                'city' =>isset($res['regeocode']['addressComponent']['city'])?$res['regeocode']['addressComponent']['city']:'',
                'area' => isset($res['regeocode']['addressComponent']['district'])?$res['regeocode']['addressComponent']['district']:'',
            ];
        }

        if(isset($address['province']) && $address['city'] == []){
            unset($address['city']);
        }
        return $address ;
    }

}

