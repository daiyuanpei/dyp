<?php

namespace App\Model;

use App\Librarys\ArrayUtil;
use App\Model\ShowSchedule;
use App\Model\OrderTicket;
use App\Model\Hall;

class Order extends Base
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tb_order';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['order_num','order_price','pay_price','order_status','order_type','come_from','operat_id','pay_way','product_id'];

    /*
     * 生成订单编号
     * */
    public static function getOrderNum(){
        $orderNum =  strtoupper(uniqid());
        return $orderNum;
    }

    // 支付模式
    public static function getPayWays(){
        $statuses = [
            ['pay_way'=>1000,'pay_way_text'=>'现金支付'],
            ['pay_way'=>1001,'pay_way_text'=>'银行卡'],
            ['pay_way'=>1002,'pay_way_text'=>'微信支付'],
            ['pay_way'=>1003,'pay_way_text'=>'支付宝'],
            ['pay_way'=>1004,'pay_way_text'=>'ApplePay'],
        ];
        return $statuses;
    }
    public static function getOrderPayWayText($type){
        switch ($type){
            case 1000:
                $text = '现金支付';
                break;
            case 1001:
                $text = '银行卡';
                break;
            case 1002:
                $text = '微信支付';
                break;
            case 1003:
                $text = '支付宝';
                break;
            case 1004:
                $text = 'ApplePay';
                break;
            default:
                $text = '未定义';
        }
        return $text;
    }
    // 订单状态
    public static function getOrderStatuses(){
        $statuses = [
            ['order_status'=>0,'order_status_text'=>'待付款'],
            ['order_status'=>1,'order_status_text'=>'已支付'],
            ['order_status'=>2,'order_status_text'=>'已失效'],
            ['order_status'=>3,'order_status_text'=>'已删除'],
            ['order_status'=>4,'order_status_text'=>'退款中（用户原因）'],
            ['order_status'=>5,'order_status_text'=>'退款中（系统原因）'],
            ['order_status'=>6,'order_status_text'=>'退款成功'],
            ['order_status'=>7,'order_status_text'=>'代发货'],
            ['order_status'=>8,'order_status_text'=>'待收货'],
            ['order_status'=>9,'order_status_text'=>'已完成'],
            ['order_status'=>10,'order_status_text'=>'退款失败'],
            ['order_status'=>11,'order_status_text'=>'支付失败'],
        ];
        return $statuses;
    }

    public static function getOrderStatusText($type){
        switch ($type){
            case 0:
                $text = '未支付';
                break;
            case 1:
                $text = '已支付';
                break;
            case 2:
                $text = '已失效';
                break;
            case 3:
                $text = '已删除';
                break;
            case 4:
                $text = '退款中（用户原因）';
                break;
            case 5:
                $text = '退款中（系统原因）';
                break;
            case 6:
                $text = '退款成功';
                break;
            case 7:
                $text = '代发货';
                break;
            case 8:
                $text = '待收货';
                break;
            case 9:
                $text = '已完成';
                break;
            case 10:
                $text = '退款失败';
                break;
            case 11:
                $text = '支付失败';
                break;
            default:
                $text = '未定义';
        }
        return $text;
    }



    /*@@@@@@@@@@@@@@@@@@@ api @@@@@@@@@@@@@@@@@@@*/

    /**
     * @param $params
     * @return array
     */
    public static function getShowOrdersByUserId($params){
        $userId = $params['user_id'];
        $where = [
            ['user_id', $userId],
            ['order_type', 1002],
            ['order_status', [1, 9]]
        ];
        $page = $params['page'];
        $orders = Order::select(
            'order_num', 'product_id', 'product_name', 'bug_quantity'
        )
            ->baseWhere($where)
            ->basePage($page)
            ->get()
            ->toArray();
        $totalSize = Order::baseWhere($where)->count();
        foreach ($orders as $key=>$value){
            $orderNum = $value['order_num'];
            $orderTicket = OrderTicket::getOrderTicketByWhere([
                ['order_num', $orderNum]
            ]);
            $timeDesc = '';
            $theatreName = '';
            $theatreAddress = '';
            $theatreLat = '';
            $theatreLng = '';
            $ticketDesc = '';
            $ticketType = '';
            $ticketTypeText = '';
            $isEnd = 0;
            if(!empty($orderTicket)){
                $scheduleId = $orderTicket['schedule_id'];
                $schedule = ShowSchedule::getScheduleById($scheduleId);
                if(!empty($schedule)){
                    $startTime = $schedule['start_time'];
                    $endTime = $schedule['end_time'];
                    $timeDesc = date('Y-m-d H:i:s', $startTime);
                    if($startTime < time()){
                        $isEnd = 1;
                    }
                    if($orderTicket['ticket_id'] == 0){
                        $ticketType = 1; 
                        $ticketTypeText = '直播';
                    }else{
                        $ticketType = 2;
                        $ticketTypeText = '演出';
                    }
                    if($ticketType == 1){
                        if($startTime < time() && time() < $endTime){
                            $ticketDesc = '直播中';
                        }
                        if(time() > $endTime){
                            $ticketDesc = '已结束';
                        }
                        if($startTime > time()){
                            $ticketDesc = '未开始，敬请期待';
                        }
                    }else if($ticketType == 2){
                        $hallId = $schedule['hall_id'];
                        $hall = Hall::getHallByWhere([['id', $hallId]]);
                        if(empty($hall)){
                            $hall['hall_name'] = '';
                        }
                        $ticketDesc = $value['bug_quantity'].'张'.' | '.$hall['hall_name'];
                    }
                    $theatreId = $schedule['theatre_id'];
                    $theatre = Theatre::getTheatreByWhere([['id', $theatreId]]);
                    if(!empty($theatre)){
                        $theatreName = $theatre['theatre_name'];
                        $theatreAddress = $theatre['address'];
                        $theatreLat = $theatre['lat'];
                        $theatreLng = $theatre['lng'];
                    }
                }
            }
            $orders[$key] = [
                'order_num'=>$orderNum,
                'product_id'=>$value['product_id'],
                'product_name'=>$value['product_name'],
                'bug_quantity_text'=>$value['bug_quantity'].'张',
                'time_desc'=>$timeDesc,
                'theatre_name'=>$theatreName,
                'theatre_address'=>$theatreAddress,
                'theatre_lat'=>$theatreLat,
                'theatre_lng'=>$theatreLng,
                'ticket_desc'=>$ticketDesc,
                'ticket_type'=>$ticketType, // 1 直播 2 演出
                'ticket_type_text'=>$ticketTypeText,
                'is_end'=>$isEnd,
            ];
        }
        return [
            'orders'=>$orders,
            'page_info'=>Order::makePageInfo($page, $totalSize)
        ];
    }














    /**
     * @param $params
     * @return array
     * 获取列表
     */
    public static function getOrders($params){
        $default = [
            'where'=>[],
            'order_by'=>[],
            'page'=>[]
        ];
        $params = array_merge($default, $params);
        $where = $params['where'];
        $orderBy = $params['order_by'];
        $page = $params['page'];
        $dataRet = Order::select(
            'tb_order.id as order_id','tb_order.order_num','tb_order.order_price','tb_order.pay_price','tb_order.order_status','tb_order.come_from','tb_order.operat_id','tb_order.pay_way')
            ->baseWhere($where)
            ->baseOrderBy($orderBy)
            ->basePage($page)
            ->get()
            ->toArray();
        $totalSize = Order::baseWhere($where)
            ->count();
        foreach ($dataRet as $key=>$value){
            foreach ($value as $k=>$v){
                if(is_null($v)){
                    $value[$k] = '';
                }
            }
            $value['order_status_text'] = Order::getOrderStatusText($value['order_status']);
            $value['pay_way_text'] = Order::getOrderPayWayText($value['pay_way']);
            $dataRet[$key] = $value;
        }
        return [
            'data'=>$dataRet,
            'page_info'=>self::makePageInfo($page, $totalSize)
        ];
    }

   //单一订单
    public static function getOrderByWhere($params){
        $default = [
            'where'=>[],
            'order_by'=>[],
            'page'=>[]
        ];
        $params = array_merge($default, $params);
        $where = $params['where'];
        $orderBy = $params['order_by'];
        $page = $params['page'];
        $dataRet = Order::select(
            'tb_order.id as order_id','tb_order.order_num','tb_order.order_price','tb_order.pay_price','tb_order.order_status',
            'tb_order.come_from','tb_order.operat_id','tb_order.pay_way','tb_order.order_type','tb_order.product_id')
            ->baseWhere($where)
            ->baseOrderBy($orderBy)
            ->basePage($page)
            ->first();
        if(empty($dataRet)){
            $dataRet = [];
        }else{
            $dataRet = $dataRet->toArray();
        }
        foreach ($dataRet as $key=>$value){
            if($key == 'order_status'){
                $dataRet['order_status_text'] = Order::getOrderStatusText($value);
            }
            if($key == 'pay_way'){
                $dataRet['pay_way_text'] = Order::getOrderPayWayText($value);
            }
            if($key == 'order_price'){
                $dataRet['order_price'] = intval($dataRet['order_price']);
            }
        }
        return $dataRet;
    }

    /**
     * 批量插入
     */
    public static function addBatch($data, $mergeData=[]){
        $nowAt = date('Y-m-d H:i:s');
        foreach ($data as $key=>$value){
            $mergeData['created_at'] = $nowAt;
            $mergeData['updated_at'] = $nowAt;
            $data[$key] = array_merge($value, $mergeData);
        }
        return DB::table('tb_order')->insert($data);
    }

    /**
     * 插入
     */
    public static function insertData($data){
        return Order::create($data);
    }

    /**
     * 删除
     */
    public static function delById($id){
        return Order::where('id',$id)->delete();
    }

    /**
     * 修改
     */
    public static function updateById($orderId, $data){
        return Order::where('id', $orderId)->update($data);
    }




}
